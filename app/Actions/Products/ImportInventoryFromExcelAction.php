<?php

namespace App\Actions\Products;

use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductPresentation;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ImportInventoryFromExcelAction
{
    /**
     * @return array{
     *     products:int,
     *     brands:int,
     *     categories:int,
     *     presentations:int
     * }
     */
    public function handle(UploadedFile $file): array
    {
        $rows = $this->extractRows($file);

        if ($rows === []) {
            throw new RuntimeException('La hoja Inventario no contiene productos para importar.');
        }

        return DB::transaction(function () use ($rows): array {
            Product::query()->delete();
            ProductBrand::query()->delete();
            ProductCategory::query()->delete();
            ProductPresentation::query()->delete();

            $brands = [];
            $categories = [];
            $presentations = [];
            $products = 0;

            foreach ($rows as $row) {
                $brandName = $this->normalizeText($row['brand']);
                $categoryName = $this->normalizeText($row['category']);
                $productName = $this->normalizeText($row['product']);
                $presentationName = $this->normalizeText($row['presentation']);

                if ($presentationName === '') {
                    $presentationName = 'Sin presentacion';
                }

                $brandId = $brands[$brandName] ??= ProductBrand::query()->create([
                    'name' => $brandName,
                    'is_active' => true,
                ])->id;

                $categoryId = $categories[$categoryName] ??= ProductCategory::query()->create([
                    'name' => $categoryName,
                    'is_active' => true,
                ])->id;

                $presentationId = $presentations[$presentationName] ??= ProductPresentation::query()->create([
                    'name' => $presentationName,
                    'is_active' => true,
                ])->id;

                Product::query()->create([
                    'name' => $productName,
                    'barcode' => null,
                    'brand_id' => $brandId,
                    'category_id' => $categoryId,
                    'presentation_id' => $presentationId,
                    'public_sale_price' => $this->normalizeNumber($row['price'], $row['row']),
                    'current_stock' => $this->normalizeNumber($row['stock'], $row['row']),
                    'purchase_cost' => 0,
                    'internal_sale_price' => 0,
                    'sale_commission' => 0,
                    'commission_type' => 'percent',
                    'includes_tax' => false,
                    'description' => null,
                    'stock_alarm_enabled' => false,
                    'stock_alarm_limit' => null,
                    'stock_alarm_emails' => null,
                    'is_active' => true,
                ]);

                $products++;
            }

            return [
                'products' => $products,
                'brands' => count($brands),
                'categories' => count($categories),
                'presentations' => count($presentations),
            ];
        });
    }

    /**
     * @return array<int, array{
     *     row:int,
     *     brand:string,
     *     category:string,
     *     product:string,
     *     presentation:string,
     *     stock:mixed,
     *     price:mixed
     * }>
     */
    private function extractRows(UploadedFile $file): array
    {
        $sourcePath = $file->getRealPath() ?: $file->path();
        $extractPath = $this->extractWorkbookToTempDirectory($sourcePath);

        try {
            $worksheetPath = $this->resolveWorksheetPath($extractPath, 'Inventario');
            $sheetXml = $this->readWorkbookFile($extractPath, $worksheetPath);

            if (! is_string($sheetXml) || $sheetXml === '') {
                throw new RuntimeException('No se pudo leer la hoja Inventario del archivo Excel.');
            }

            $sharedStrings = $this->extractSharedStrings($extractPath);
            $sheetDocument = new DOMDocument();
            $sheetDocument->loadXML($sheetXml);
            $sheetXPath = new DOMXPath($sheetDocument);
            $sheetXPath->registerNamespace('ss', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $rowNodes = $sheetXPath->query('/ss:worksheet/ss:sheetData/ss:row');

            if ($rowNodes === false) {
                throw new RuntimeException('No se pudo recorrer la hoja Inventario del Excel.');
            }

            $headers = [];
            $rows = [];

            foreach ($rowNodes as $rowNode) {
                if (! $rowNode instanceof DOMElement) {
                    continue;
                }

                $rowNumber = (int) $rowNode->getAttribute('r');
                $cells = $this->extractDomCellValues($sheetXPath, $rowNode, $sharedStrings);

                if ($cells === []) {
                    continue;
                }

                if ($headers === []) {
                    $headers = $this->extractHeaders($cells);
                    $this->guardExpectedHeaders($headers);

                    continue;
                }

                $record = $this->mapRow($headers, $cells);

                if ($this->isEmptyRecord($record)) {
                    continue;
                }

                $rows[] = [
                    'row' => $rowNumber,
                    'brand' => $this->requireText($record['marca'] ?? null, 'Marca', $rowNumber),
                    'category' => $this->requireText($record['categoria'] ?? null, 'Categoria', $rowNumber),
                    'product' => $this->requireText($record['producto'] ?? null, 'Producto', $rowNumber),
                    'presentation' => $this->normalizeText($record['presentacion'] ?? '') !== ''
                        ? $this->normalizeText($record['presentacion'] ?? '')
                        : 'Sin presentacion',
                    'stock' => $record['stock'] ?? null,
                    'price' => $record['precio'] ?? null,
                ];
            }

            return $rows;
        } finally {
            $this->removeDirectory($extractPath);
        }
    }

    private function extractWorkbookToTempDirectory(string $sourcePath): string
    {
        if (! is_file($sourcePath)) {
            throw new RuntimeException('No se encontro el archivo Excel.');
        }

        $tempRoot = sys_get_temp_dir().DIRECTORY_SEPARATOR.'inventory-import-'.Str::uuid()->toString();
        $this->ensureDirectory($tempRoot);

        if (class_exists(\ZipArchive::class)) {
            $zip = new \ZipArchive();

            if ($zip->open($sourcePath) !== true) {
                $this->removeDirectory($tempRoot);
                throw new RuntimeException('No se pudo abrir el archivo Excel.');
            }

            try {
                if (! $zip->extractTo($tempRoot)) {
                    throw new RuntimeException('No se pudo descomprimir el archivo Excel.');
                }
            } finally {
                $zip->close();
            }

            return $tempRoot;
        }

        $tempZip = $tempRoot.'.zip';
        if (! copy($sourcePath, $tempZip)) {
            $this->removeDirectory($tempRoot);
            throw new RuntimeException('No se pudo preparar el archivo temporal del Excel.');
        }

        $escapedSource = escapeshellarg($tempZip);
        $escapedDestination = escapeshellarg($tempRoot);
        $command = sprintf(
            'powershell.exe -NoProfile -NonInteractive -ExecutionPolicy Bypass -Command "Expand-Archive -LiteralPath %s -DestinationPath %s -Force"',
            $escapedSource,
            $escapedDestination,
        );

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            @unlink($tempZip);
            $this->removeDirectory($tempRoot);
            throw new RuntimeException('No se pudo descomprimir el archivo Excel.');
        }

        @unlink($tempZip);

        return $tempRoot;
    }

    private function resolveWorksheetPath(string $extractPath, string $sheetName): string
    {
        $workbookXml = $this->readWorkbookFile($extractPath, 'xl/workbook.xml');
        $relationshipsXml = $this->readWorkbookFile($extractPath, 'xl/_rels/workbook.xml.rels');

        if (! is_string($workbookXml) || $workbookXml === '' || ! is_string($relationshipsXml) || $relationshipsXml === '') {
            throw new RuntimeException('El archivo Excel no tiene la estructura esperada.');
        }

        $workbookDocument = new DOMDocument();
        $relationshipsDocument = new DOMDocument();
        $workbookDocument->loadXML($workbookXml);
        $relationshipsDocument->loadXML($relationshipsXml);

        $workbookXPath = new DOMXPath($workbookDocument);
        $relationshipsXPath = new DOMXPath($relationshipsDocument);
        $workbookXPath->registerNamespace('ss', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $workbookXPath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
        $relationshipsXPath->registerNamespace('rels', 'http://schemas.openxmlformats.org/package/2006/relationships');

        $sheetNodes = $workbookXPath->query('/ss:workbook/ss:sheets/ss:sheet');

        if ($sheetNodes === false) {
            throw new RuntimeException('No se pudo leer la definicion de hojas del archivo Excel.');
        }

        foreach ($sheetNodes as $sheetNode) {
            $name = $sheetNode->attributes?->getNamedItem('name')?->nodeValue ?? '';

            if ($this->normalizeHeader($name) !== $this->normalizeHeader($sheetName)) {
                continue;
            }

            $relationshipId = $sheetNode->attributes?->getNamedItemNS(
                'http://schemas.openxmlformats.org/officeDocument/2006/relationships',
                'id',
            )?->nodeValue;

            if (! is_string($relationshipId) || $relationshipId === '') {
                break;
            }

            $target = $relationshipsXPath->evaluate(
                'string(/rels:Relationships/rels:Relationship[@Id="'.$relationshipId.'"]/@Target)',
            );

            if (! is_string($target) || $target === '') {
                break;
            }

            return str_starts_with($target, '/')
                ? ltrim($target, '/')
                : 'xl/'.ltrim($target, '/');
        }

        throw new RuntimeException('No se encontro la hoja Inventario dentro del archivo Excel.');
    }

    /**
     * @return array<int, string>
     */
    private function extractSharedStrings(string $extractPath): array
    {
        $xml = $this->readWorkbookFile($extractPath, 'xl/sharedStrings.xml');

        if (! is_string($xml) || $xml === '') {
            return [];
        }

        $document = new DOMDocument();
        $document->loadXML($xml);
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('ss', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $items = $xpath->query('/ss:sst/ss:si');

        if ($items === false) {
            throw new RuntimeException('No se pudieron leer las cadenas compartidas del Excel.');
        }

        $strings = [];

        foreach ($items as $item) {
            if (! $item instanceof DOMElement) {
                continue;
            }

            $text = '';
            $textNodes = $xpath->query('./ss:t | ./ss:r/ss:t', $item);

            if ($textNodes === false) {
                continue;
            }

            foreach ($textNodes as $textNode) {
                $text .= $textNode->textContent;
            }

            $strings[] = $text;
        }

        return $strings;
    }

    private function readWorkbookFile(string $extractPath, string $relativePath): ?string
    {
        $path = rtrim($extractPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);

        if (! is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        return is_string($contents) ? $contents : null;
    }

    private function ensureDirectory(string $path): void
    {
        if (is_dir($path)) {
            return;
        }

        if (! mkdir($path, 0777, true) && ! is_dir($path)) {
            throw new RuntimeException('No se pudo crear la carpeta temporal para importar el Excel.');
        }
    }

    private function removeDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($files as $fileInfo) {
            if ($fileInfo->isDir()) {
                @rmdir($fileInfo->getPathname());

                continue;
            }

            @unlink($fileInfo->getPathname());
        }

        @rmdir($path);
    }

    /**
     * @return array<int, mixed>
     */
    private function extractDomCellValues(DOMXPath $xpath, DOMElement $rowNode, array $sharedStrings): array
    {
        $cellNodes = $xpath->query('./ss:c', $rowNode);

        if ($cellNodes === false) {
            return [];
        }

        $values = [];

        foreach ($cellNodes as $cellNode) {
            if (! $cellNode instanceof DOMElement) {
                continue;
            }

            $reference = $cellNode->getAttribute('r');
            $columnIndex = $this->columnIndexFromReference($reference);

            if ($columnIndex === null) {
                continue;
            }

            $values[$columnIndex] = $this->extractDomCellValue($xpath, $cellNode, $sharedStrings);
        }

        if ($values === []) {
            return [];
        }

        ksort($values);

        return $values;
    }

    private function extractDomCellValue(DOMXPath $xpath, DOMElement $cellNode, array $sharedStrings): mixed
    {
        $type = $cellNode->getAttribute('t');

        if ($type === 'inlineStr') {
            $inlineNodes = $xpath->query('./ss:is/ss:t | ./ss:is/ss:r/ss:t', $cellNode);

            if ($inlineNodes === false || $inlineNodes->length === 0) {
                return null;
            }

            $text = '';

            foreach ($inlineNodes as $inlineNode) {
                $text .= $inlineNode->textContent;
            }

            return $text;
        }

        $valueNode = $xpath->query('./ss:v', $cellNode)?->item(0);
        $value = $valueNode?->textContent ?? '';

        if ($value === '') {
            return null;
        }

        if ($type === 's') {
            return $sharedStrings[(int) $value] ?? null;
        }

        if ($type === 'b') {
            return $value === '1';
        }

        return $value;
    }

    /**
     * @param  array<int, mixed>  $cells
     * @return array<int, string>
     */
    private function extractHeaders(array $cells): array
    {
        $headers = [];

        foreach ($cells as $index => $value) {
            $headers[$index] = $this->normalizeHeader((string) $value);
        }

        return $headers;
    }

    /**
     * @param  array<int, string>  $headers
     */
    private function guardExpectedHeaders(array $headers): void
    {
        $required = ['marca', 'categoria', 'producto', 'presentacion', 'stock', 'precio'];

        foreach ($required as $header) {
            if (! in_array($header, $headers, true)) {
                throw new RuntimeException('La hoja Inventario debe incluir las columnas Marca, Categoria, Producto, Presentacion, Stock y Precio.');
            }
        }
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, mixed>  $cells
     * @return array<string, mixed>
     */
    private function mapRow(array $headers, array $cells): array
    {
        $mapped = [];

        foreach ($headers as $index => $header) {
            $mapped[$header] = $cells[$index] ?? null;
        }

        return $mapped;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function isEmptyRecord(array $record): bool
    {
        foreach ($record as $value) {
            if ($this->normalizeText((string) ($value ?? '')) !== '') {
                return false;
            }
        }

        return true;
    }

    private function requireText(mixed $value, string $label, int $rowNumber): string
    {
        $text = $this->normalizeText((string) ($value ?? ''));

        if ($text === '') {
            throw new RuntimeException("La columna {$label} esta vacia en la fila {$rowNumber} del Excel.");
        }

        return $text;
    }

    private function normalizeText(mixed $value): string
    {
        return Str::of((string) ($value ?? ''))->squish()->trim()->toString();
    }

    private function normalizeHeader(string $value): string
    {
        return Str::of($value)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->toString();
    }

    private function normalizeNumber(mixed $value, int $rowNumber): float
    {
        if ($value === null) {
            return 0.0;
        }

        $normalized = trim((string) $value);

        if ($normalized === '') {
            return 0.0;
        }

        $normalized = str_ireplace(['S/', 'USD', '$'], '', $normalized);
        $normalized = preg_replace('/\s+/', '', $normalized) ?? $normalized;

        if (str_contains($normalized, ',') && ! str_contains($normalized, '.')) {
            $normalized = str_replace(',', '.', $normalized);
        } else {
            $normalized = str_replace(',', '', $normalized);
        }

        if (! is_numeric($normalized)) {
            throw new RuntimeException("Hay un valor numerico invalido en la fila {$rowNumber} del Excel.");
        }

        return round((float) $normalized, 2);
    }

    private function columnIndexFromReference(string $reference): ?int
    {
        if ($reference === '' || ! preg_match('/^[A-Z]+/', strtoupper($reference), $matches)) {
            return null;
        }

        $letters = $matches[0];
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }
}
