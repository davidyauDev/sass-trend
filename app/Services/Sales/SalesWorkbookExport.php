<?php

namespace App\Services\Sales;

use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use RuntimeException;
use ZipArchive;

final class SalesWorkbookExport
{
    public function __construct(
        private readonly SalesReportExport $baseExport,
    ) {}

    /**
     * @param  array{search?:string,period?:string,client?:string,status?:string,payment?:string,branch?:string}  $filters
     */
    public function export(array $filters): string
    {
        $path = $this->baseExport->export($filters);
        $sales = $this->salesQuery($filters)
            ->with(['client', 'branch', 'user', 'items.product.category', 'items.service.category'])
            ->latest('sold_at')
            ->get();

        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new RuntimeException('No se pudo actualizar la hoja Ventas del reporte.');
        }

        $zip->addFromString('xl/worksheets/sheet2.xml', $this->buildItemsSheetXml($sales));
        $zip->addFromString('xl/worksheets/sheet3.xml', $this->buildVentasSheetXml($sales));
        $zip->close();

        return $path;
    }

    /**
     * @param  array{search?:string,period?:string,client?:string,status?:string,payment?:string,branch?:string}  $filters
     * @return Builder<Sale>
     */
    private function salesQuery(array $filters): Builder
    {
        $query = Sale::query()
            ->with(['client', 'branch', 'payments']);

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $query->search($search);
        }

        $period = (string) ($filters['period'] ?? '7');

        if ($period !== 'all') {
            $days = max(1, (int) $period);
            $query->where('sold_at', '>=', now()->subDays($days)->startOfDay());
        }

        $clientId = (string) ($filters['client'] ?? '');

        if ($clientId !== '') {
            $query->where('client_id', (int) $clientId);
        }

        $paymentMethod = trim((string) ($filters['payment'] ?? ''));

        if ($paymentMethod !== '') {
            $query->whereHas('payments', fn (Builder $paymentQuery): Builder => $paymentQuery->where('method', $paymentMethod));
        }

        $branchId = (string) ($filters['branch'] ?? '');

        if ($branchId !== '') {
            $query->where('branch_id', (int) $branchId);
        }

        $status = (string) ($filters['status'] ?? '');

        if ($status === 'deleted') {
            $query->onlyTrashed();
        } elseif ($status !== '') {
            $query->where('status', $status);
        }

        return $query;
    }

    /**
     * @param  Collection<int, Sale>  $sales
     */
    private function buildVentasSheetXml(Collection $sales): string
    {
        $headers = ['ID', 'ID interno', 'Fecha', 'Monto venta', 'Monto a pagar', 'Monto pendiente', 'Cliente', 'Local', 'Nota', 'Creado por'];
        $rows = [];
        $rowNumber = 1;

        $rows[] = $this->rowXml($rowNumber, $this->headerCells($headers));

        foreach ($sales as $sale) {
            $rowNumber++;
            $total = (float) $sale->total;
            $paidTotal = (float) $sale->paid_total;
            $pendingTotal = max(0, round($total - $paidTotal, 2));

            $rows[] = $this->rowXml($rowNumber, [
                1 => ['value' => (int) $sale->id, 'style' => 0],
                2 => ['value' => '#'.($sale->sale_number ?? $sale->id), 'style' => 0],
                3 => ['value' => $sale->sold_at?->format('d/m/Y H:i') ?? '', 'style' => 0],
                4 => ['value' => $total, 'style' => 4],
                5 => ['value' => $total, 'style' => 4],
                6 => ['value' => $pendingTotal, 'style' => 4],
                7 => ['value' => $sale->client?->fullName() ?? 'Consumidor final', 'style' => 0],
                8 => ['value' => (string) data_get($sale, 'branch.name', 'Sin local'), 'style' => 0],
                9 => ['value' => (string) ($sale->notes ?? ''), 'style' => 0],
                10 => ['value' => (string) data_get($sale, 'user.name', 'Sin usuario'), 'style' => 0],
            ]);
        }

        return $this->worksheetXml($rows, "A1:J{$rowNumber}", [
            ['min' => 1, 'max' => 1, 'width' => 12],
            ['min' => 2, 'max' => 2, 'width' => 14],
            ['min' => 3, 'max' => 3, 'width' => 18],
            ['min' => 4, 'max' => 6, 'width' => 15],
            ['min' => 7, 'max' => 7, 'width' => 24],
            ['min' => 8, 'max' => 8, 'width' => 18],
            ['min' => 9, 'max' => 9, 'width' => 24],
            ['min' => 10, 'max' => 10, 'width' => 20],
        ], 1);
    }

    /**
     * @param  Collection<int, Sale>  $sales
     */
    private function buildItemsSheetXml(Collection $sales): string
    {
        $headers = ['ID Venta', 'Fecha venta', 'Local', 'Cliente', 'Tipo item', 'Categoría', 'Nombre item', 'Cantidad', 'Precio unitario', 'Descuento', 'Total', 'Prestador', 'Fecha reserva'];
        $rows = [];
        $rowNumber = 1;

        $rows[] = $this->rowXml($rowNumber, $this->headerCells($headers));

        $items = $sales->flatMap(function (Sale $sale): Collection {
            return $sale->items->map(function (SaleItem $item) use ($sale): array {
                $quantity = (float) $item->quantity;
                $unitPrice = (float) $item->unit_price;
                $total = (float) $item->subtotal;
                $discount = max(0, round(($quantity * $unitPrice) - $total, 2));
                $isService = $item->item_type === 'service';
                $category = $isService
                    ? (string) data_get($item, 'service.category.name', 'Sin categoría')
                    : (string) data_get($item, 'product.category.name', 'Sin categoría');
                $professionalName = trim((string) data_get($item->meta, 'professional_name', ''));

                return [
                    'venta' => '#'.($sale->sale_number ?? $sale->id),
                    'fecha' => $sale->sold_at?->format('d/m/Y H:i') ?? '',
                    'local' => (string) data_get($sale, 'branch.name', 'Sin local'),
                    'cliente' => $sale->client?->fullName() ?? 'Consumidor final',
                    'tipo_item' => $isService ? 'Servicio' : 'Producto',
                    'categoria' => $category,
                    'nombre_item' => (string) $item->item_name,
                    'cantidad' => $quantity,
                    'precio_unitario' => $unitPrice,
                    'descuento' => $discount,
                    'total' => $total,
                    'prestador' => $professionalName !== '' ? $professionalName : '',
                    'fecha_reserva' => (string) data_get($item->meta, 'reservation_date', ''),
                ];
            });
        });

        foreach ($items as $item) {
            $rowNumber++;
            $rows[] = $this->rowXml($rowNumber, [
                1 => ['value' => $item['venta'], 'style' => 0],
                2 => ['value' => $item['fecha'], 'style' => 0],
                3 => ['value' => $item['local'], 'style' => 0],
                4 => ['value' => $item['cliente'], 'style' => 0],
                5 => ['value' => $item['tipo_item'], 'style' => 0],
                6 => ['value' => $item['categoria'], 'style' => 0],
                7 => ['value' => $item['nombre_item'], 'style' => 0],
                8 => ['value' => $item['cantidad'], 'style' => 4],
                9 => ['value' => $item['precio_unitario'], 'style' => 4],
                10 => ['value' => $item['descuento'], 'style' => 4],
                11 => ['value' => $item['total'], 'style' => 4],
                12 => ['value' => $item['prestador'], 'style' => 0],
                13 => ['value' => $item['fecha_reserva'], 'style' => 0],
            ]);
        }

        return $this->worksheetXml($rows, "A1:M{$rowNumber}", [
            ['min' => 1, 'max' => 1, 'width' => 14],
            ['min' => 2, 'max' => 2, 'width' => 18],
            ['min' => 3, 'max' => 3, 'width' => 18],
            ['min' => 4, 'max' => 4, 'width' => 24],
            ['min' => 5, 'max' => 5, 'width' => 14],
            ['min' => 6, 'max' => 6, 'width' => 16],
            ['min' => 7, 'max' => 7, 'width' => 28],
            ['min' => 8, 'max' => 11, 'width' => 12],
            ['min' => 12, 'max' => 12, 'width' => 18],
            ['min' => 13, 'max' => 13, 'width' => 18],
        ], 1);
    }

    /**
     * @param  array<int, string>  $headers
     * @return array<int, array{value:string,style:int}>
     */
    private function headerCells(array $headers): array
    {
        $cells = [];

        foreach ($headers as $index => $header) {
            $cells[$index + 1] = ['value' => $header, 'style' => 2];
        }

        return $cells;
    }

    /**
     * @param  list<string>  $rows
     * @param  array<int, array{min:int,max:int,width:float}>  $columns
     */
    private function worksheetXml(array $rows, string $dimension, array $columns, int $freezeRow): string
    {
        $columnsXml = '';

        foreach ($columns as $column) {
            $columnsXml .= sprintf(
                '<col min="%d" max="%d" width="%s" customWidth="1"/>',
                $column['min'],
                $column['max'],
                rtrim(rtrim(number_format($column['width'], 2, '.', ''), '0'), '.'),
            );
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<dimension ref="'.$dimension.'"/>'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="'.$freezeRow.'" topLeftCell="A'.($freezeRow + 1).'" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="15"/>'
            .'<cols>'.$columnsXml.'</cols>'
            .'<sheetData>'.implode('', $rows).'</sheetData>'
            .'<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>'
            .'</worksheet>';
    }

    /**
     * @param  array<int, array{value:mixed,style?:int|null}>  $cells
     */
    private function rowXml(int $rowNumber, array $cells): string
    {
        ksort($cells);

        $xml = '<row r="'.$rowNumber.'">';

        foreach ($cells as $column => $cell) {
            $xml .= $this->cellXml($column, $rowNumber, $cell['value'], $cell['style'] ?? null);
        }

        return $xml.'</row>';
    }

    private function cellXml(int $column, int $rowNumber, mixed $value, ?int $style = null): string
    {
        $ref = $this->columnName($column).$rowNumber;
        $styleAttribute = $style !== null ? ' s="'.$style.'"' : '';

        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value))) {
            return '<c r="'.$ref.'"'.$styleAttribute.'><v>'.$this->normalizeNumber($value).'</v></c>';
        }

        $text = htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '<c r="'.$ref.'" t="inlineStr"'.$styleAttribute.'><is><t>'.$text.'</t></is></c>';
    }

    private function normalizeNumber(int|float|string $value): string
    {
        if (is_string($value)) {
            return rtrim(rtrim($value, '0'), '.');
        }

        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private function columnName(int $column): string
    {
        $name = '';

        while ($column > 0) {
            $column--;
            $name = chr(65 + ($column % 26)).$name;
            $column = intdiv($column, 26);
        }

        return $name;
    }
}
