<?php

namespace App\Services\Sales;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Services\Sales\SalePaymentMethodCatalog;
use App\Services\Sales\SaleStatusCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use RuntimeException;
use ZipArchive;

final class SalesReportExport
{
    private const PURPLE = 'FF4B2E83';

    private const NUMBER_FORMAT = 4;

    /**
     * @param  array{search?:string,period?:string,client?:string, status?:string,payment?:string,branch?:string}  $filters
     */
    public function export(array $filters): string
    {
        $sales = $this->salesQuery($filters)
            ->with([
                'branch',
                'client',
                'payments',
                'user',
                'items.product.category',
                'items.service.category',
            ])
            ->latest('sold_at')
            ->get();

        $report = $this->buildReport($sales, $filters);
        $path = tempnam(sys_get_temp_dir(), 'sales-report-');

        if ($path === false) {
            throw new RuntimeException('No se pudo crear el archivo temporal del reporte.');
        }

        if (! @rename($path, $path.'.xlsx')) {
            @unlink($path);

            throw new RuntimeException('No se pudo preparar el archivo temporal del reporte.');
        }

        $xlsxPath = $path.'.xlsx';

        $zip = new ZipArchive();

        if ($zip->open($xlsxPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($xlsxPath);

            throw new RuntimeException('No se pudo crear el archivo Excel.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelsXml());
        $zip->addFromString('docProps/app.xml', $this->appXml($report['sheets']));
        $zip->addFromString('docProps/core.xml', $this->coreXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml($report['sheets']));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml($report['sheets']));
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->buildResumenSheetXml($report));
        $zip->addFromString('xl/worksheets/sheet2.xml', $this->buildItemsSheetXml($report['items']));
        $zip->addFromString('xl/worksheets/sheet3.xml', $this->buildSalesSheetXml($report['sales']));
        $zip->addFromString('xl/worksheets/sheet4.xml', $this->buildTransactionsSheetXml($report['transactions']));
        $zip->close();

        return $xlsxPath;
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
     * @param  array<string, string>  $filters
     * @return array{
     *     title: string,
     *     subtitle: string,
     *     sheets: list<string>,
     *     summary: array<string, float|int>,
     *     branches: array<int, array{local:string, ventas:float, cantidad:int, promedio:float}>,
     *     itemTypes: array<int, array{type:string, ventas:float, cantidad:float}>,
     *     categories: array<int, array{name:string, ventas:float}>,
     *     services: array<int, array{name:string, ventas:float, cantidad:float}>,
     *     items: array<int, array<string, string|float|int>>,
     *     sales: array<int, array<string, string|float|int>>,
     *     transactions: array<int, array<string, string|float|int>>,
     * }
     */
    private function buildReport(Collection $sales, array $filters): array
    {
        $periodLabel = $this->periodLabel($filters, $sales);
        $title = 'Reporte de ventas';
        $subtitle = $periodLabel;

        $branches = $sales
            ->groupBy(fn (Sale $sale): string => (string) data_get($sale, 'branch.name', 'Sin local'))
            ->map(function (Collection $group, string $local): array {
                $salesTotal = (float) $group->sum('total');
                $count = $group->count();

                return [
                    'local' => $local,
                    'ventas' => round($salesTotal, 2),
                    'cantidad' => $count,
                    'promedio' => round($count > 0 ? $salesTotal / $count : 0, 2),
                ];
            })
            ->sortByDesc('ventas')
            ->values()
            ->all();

        $items = $sales->flatMap(function (Sale $sale): Collection {
            return $sale->items->map(function (SaleItem $item) use ($sale): array {
                $itemType = $item->item_type === 'service' ? 'Servicio' : 'Producto';
                $category = $item->item_type === 'service'
                    ? (string) data_get($item, 'service.category.name', 'Sin categoría')
                    : (string) data_get($item, 'product.category.name', 'Sin categoría');

                return [
                    'venta' => (string) ($sale->sale_number ?? $sale->id),
                    'fecha' => $sale->sold_at?->format('d/m/Y H:i') ?? '',
                    'tipo' => $itemType,
                    'nombre' => (string) $item->item_name,
                    'detalle' => (string) ($item->item_detail ?? ''),
                    'cantidad' => (float) $item->quantity,
                    'precio' => (float) $item->unit_price,
                    'subtotal' => (float) $item->subtotal,
                    'local' => (string) data_get($sale, 'branch.name', 'Sin local'),
                    'cliente' => $sale->client?->fullName() ?? 'Consumidor final',
                    'categoria' => $category,
                    'vendedor' => (string) data_get($sale, 'user.name', 'Sin vendedor'),
                ];
            });
        })->values();

        $itemTypes = $items
            ->groupBy('tipo')
            ->map(function (Collection $group, string $type): array {
                return [
                    'type' => $type,
                    'ventas' => round((float) $group->sum('subtotal'), 2),
                    'cantidad' => round((float) $group->sum('cantidad'), 2),
                ];
            })
            ->sortByDesc('ventas')
            ->values()
            ->all();

        $categories = $items
            ->groupBy('categoria')
            ->map(function (Collection $group, string $name): array {
                return [
                    'name' => $name,
                    'ventas' => round((float) $group->sum('subtotal'), 2),
                ];
            })
            ->sortByDesc('ventas')
            ->values()
            ->all();

        $services = $items
            ->filter(fn (array $item): bool => $item['tipo'] === 'Servicio')
            ->groupBy('nombre')
            ->map(function (Collection $group, string $name): array {
                return [
                    'name' => $name,
                    'ventas' => round((float) $group->sum('subtotal'), 2),
                    'cantidad' => round((float) $group->sum('cantidad'), 2),
                ];
            })
            ->sortByDesc('ventas')
            ->values()
            ->all();

        $salesRows = $sales->map(function (Sale $sale): array {
            $methods = $sale->payments
                ->pluck('method')
                ->map(fn (string $method): string => SalePaymentMethodCatalog::options()[$method] ?? $method)
                ->implode(', ');

            return [
                'id' => (int) $sale->id,
                'venta' => (string) ($sale->sale_number ?? $sale->id),
                'fecha' => $sale->sold_at?->format('d/m/Y H:i') ?? '',
                'estado' => $this->saleStatusLabel((string) $sale->status),
                'cliente' => $sale->client?->fullName() ?? 'Consumidor final',
                'local' => (string) data_get($sale, 'branch.name', 'Sin local'),
                'pagos' => $methods,
                'subtotal' => (float) $sale->subtotal,
                'descuento' => (float) $sale->discount_total,
                'total' => (float) $sale->total,
                'pagado' => (float) $sale->paid_total,
                'vuelto' => (float) $sale->change_total,
                'nota' => (string) ($sale->notes ?? ''),
                'creado_por' => (string) data_get($sale, 'user.name', 'Sin usuario'),
            ];
        })->values()->all();

        $transactions = $sales->flatMap(function (Sale $sale): Collection {
            return $sale->payments->map(function (SalePayment $payment) use ($sale): array {
                return [
                    'venta' => (string) ($sale->sale_number ?? $sale->id),
                    'fecha' => $sale->sold_at?->format('d/m/Y H:i') ?? '',
                    'cliente' => $sale->client?->fullName() ?? 'Consumidor final',
                    'local' => (string) data_get($sale, 'branch.name', 'Sin local'),
                    'metodo' => SalePaymentMethodCatalog::options()[$payment->method] ?? $payment->method,
                    'monto' => (float) $payment->amount,
                    'referencia' => (string) ($payment->reference ?? ''),
                ];
            });
        })->values()->all();

        $summary = [
            'total_sales' => $sales->count(),
            'total_revenue' => round((float) $sales->sum('total'), 2),
            'average_sale' => round($sales->isNotEmpty() ? (float) $sales->sum('total') / $sales->count() : 0, 2),
            'total_discounts' => round((float) $sales->sum('discount_total'), 2),
        ];

        return [
            'title' => $title,
            'subtitle' => $subtitle,
            'sheets' => ['Resumen', 'Ítems', 'Ventas', 'Transacciones'],
            'summary' => $summary,
            'branches' => $branches,
            'itemTypes' => $itemTypes,
            'categories' => $categories,
            'services' => $services,
            'items' => $items->all(),
            'sales' => $salesRows,
            'transactions' => $transactions,
        ];
    }

    /**
     * @param  array{period?:string}  $filters
     * @param  Collection<int, Sale>  $sales
     */
    private function periodLabel(array $filters, Collection $sales): string
    {
        $period = (string) ($filters['period'] ?? '7');

        if ($period !== 'all') {
            $days = max(1, (int) $period);
            $from = now()->subDays($days)->startOfDay()->format('d/m/Y');
            $to = now()->format('d/m/Y');

            return "{$from} al {$to}";
        }

        if ($sales->isEmpty()) {
            return 'Todo el historial';
        }

        $fromSale = $sales->sortBy('sold_at')->first();
        $toSale = $sales->sortByDesc('sold_at')->first();
        $from = $fromSale?->sold_at?->format('d/m/Y') ?? now()->format('d/m/Y');
        $to = $toSale?->sold_at?->format('d/m/Y') ?? now()->format('d/m/Y');

        return "{$from} al {$to}";
    }

    private function saleStatusLabel(string $status): string
    {
        return match ($status) {
            SaleStatusCatalog::PAID => 'Pagada',
            SaleStatusCatalog::PARTIAL => 'Abono',
            SaleStatusCatalog::DRAFT => 'Borrador',
            default => ucfirst($status),
        };
    }

    /**
     * @param  array{
     *     title:string,
     *     subtitle:string,
     *     sheets:list<string>,
     *     summary:array<string, float|int>,
     *     branches:array<int, array{local:string, ventas:float, cantidad:int, promedio:float}>,
     *     itemTypes:array<int, array{type:string, ventas:float, cantidad:float}>,
     *     categories:array<int, array{name:string, ventas:float}>,
     *     services:array<int, array{name:string, ventas:float, cantidad:float}>,
     * }  $report
     */
    private function buildResumenSheetXml(array $report): string
    {
        $rows = [];
        $merges = ['A1:B1', 'A4:B4'];
        $currentRow = 1;

        $rows[] = $this->rowXml($currentRow, [
            1 => ['value' => $report['title'], 'style' => 1],
        ]);
        $currentRow++;

        $rows[] = $this->rowXml($currentRow, [
            1 => ['value' => $report['subtitle'], 'style' => 0],
        ]);
        $currentRow += 2;

        $rows[] = $this->rowXml($currentRow, [
            1 => ['value' => 'Resumen', 'style' => 2],
        ]);
        $currentRow++;

        foreach ([
            'Total ventas (S/)' => $report['summary']['total_revenue'],
            'Cantidad de ventas' => $report['summary']['total_sales'],
            'Venta promedio (S/)' => $report['summary']['average_sale'],
            'Total descuentos (S/)' => $report['summary']['total_discounts'],
        ] as $label => $value) {
            $rows[] = $this->rowXml($currentRow, [
                1 => ['value' => $label, 'style' => 0],
                2 => ['value' => $value, 'style' => self::NUMBER_FORMAT],
            ]);
            $currentRow++;
        }

        $currentRow++;
        $rows[] = $this->rowXml($currentRow, [
            1 => ['value' => 'Ventas por local', 'style' => 2],
        ]);
        $merges[] = "A{$currentRow}:E{$currentRow}";
        $currentRow++;

        $rows[] = $this->rowXml($currentRow, [
            1 => ['value' => 'Local', 'style' => 3],
            2 => ['value' => 'Ventas (S/)', 'style' => 3],
            3 => ['value' => 'Cantidad de ventas', 'style' => 3],
            4 => ['value' => 'Venta promedio (S/)', 'style' => 3],
        ]);
        $currentRow++;

        foreach ($report['branches'] as $row) {
            $rows[] = $this->rowXml($currentRow, [
                1 => ['value' => $row['local'], 'style' => 0],
                2 => ['value' => $row['ventas'], 'style' => self::NUMBER_FORMAT],
                3 => ['value' => $row['cantidad'], 'style' => self::NUMBER_FORMAT],
                4 => ['value' => $row['promedio'], 'style' => self::NUMBER_FORMAT],
            ]);
            $currentRow++;
        }

        $currentRow += 1;
        $rows[] = $this->rowXml($currentRow, [
            1 => ['value' => 'Ventas por tipo de item', 'style' => 2],
        ]);
        $merges[] = "A{$currentRow}:D{$currentRow}";
        $currentRow++;

        $rows[] = $this->rowXml($currentRow, [
            1 => ['value' => 'Tipo de item', 'style' => 3],
            2 => ['value' => 'Ventas (S/)', 'style' => 3],
            3 => ['value' => 'Cantidad', 'style' => 3],
        ]);
        $currentRow++;

        foreach ($report['itemTypes'] as $row) {
            $rows[] = $this->rowXml($currentRow, [
                1 => ['value' => $row['type'], 'style' => 0],
                2 => ['value' => $row['ventas'], 'style' => self::NUMBER_FORMAT],
                3 => ['value' => $row['cantidad'], 'style' => self::NUMBER_FORMAT],
            ]);
            $currentRow++;
        }

        $currentRow += 1;
        $rows[] = $this->rowXml($currentRow, [
            1 => ['value' => 'Ventas por categoría', 'style' => 2],
        ]);
        $merges[] = "A{$currentRow}:B{$currentRow}";
        $currentRow++;

        foreach ($report['categories'] as $row) {
            $rows[] = $this->rowXml($currentRow, [
                1 => ['value' => $row['name'], 'style' => 0],
                2 => ['value' => $row['ventas'], 'style' => self::NUMBER_FORMAT],
            ]);
            $currentRow++;
        }

        $currentRow += 1;
        $rows[] = $this->rowXml($currentRow, [
            1 => ['value' => 'Ventas por servicio', 'style' => 2],
        ]);
        $merges[] = "A{$currentRow}:D{$currentRow}";
        $currentRow++;

        $rows[] = $this->rowXml($currentRow, [
            1 => ['value' => 'Nombre del servicio', 'style' => 3],
            2 => ['value' => 'Ventas (S/)', 'style' => 3],
            3 => ['value' => 'Cantidad', 'style' => 3],
        ]);
        $currentRow++;

        foreach ($report['services'] as $row) {
            $rows[] = $this->rowXml($currentRow, [
                1 => ['value' => $row['name'], 'style' => 0],
                2 => ['value' => $row['ventas'], 'style' => self::NUMBER_FORMAT],
                3 => ['value' => $row['cantidad'], 'style' => self::NUMBER_FORMAT],
            ]);
            $currentRow++;
        }

        return $this->worksheetXml($rows, $merges, "A1:E{$currentRow}", [
            ['min' => 1, 'max' => 1, 'width' => 32],
            ['min' => 2, 'max' => 2, 'width' => 18],
            ['min' => 3, 'max' => 3, 'width' => 18],
            ['min' => 4, 'max' => 4, 'width' => 20],
            ['min' => 5, 'max' => 5, 'width' => 20],
        ], 4);
    }

    /**
     * @param  array<int, array<string, string|float|int>>  $items
     */
    private function buildItemsSheetXml(array $items): string
    {
        $rows = [];
        $headers = ['Venta', 'Fecha', 'Tipo', 'Nombre', 'Detalle', 'Cantidad', 'Precio', 'Subtotal', 'Local', 'Cliente', 'Categoría', 'Vendedor'];
        $rowNumber = 1;

        $headerCells = [];
        foreach ($headers as $index => $label) {
            $headerCells[$index + 1] = ['value' => $label, 'style' => 3];
        }

        $rows[] = $this->rowXml($rowNumber, $headerCells);

        foreach ($items as $item) {
            $rowNumber++;
            $rows[] = $this->rowXml($rowNumber, [
                1 => ['value' => $item['venta'], 'style' => 0],
                2 => ['value' => $item['fecha'], 'style' => 0],
                3 => ['value' => $item['tipo'], 'style' => 0],
                4 => ['value' => $item['nombre'], 'style' => 0],
                5 => ['value' => $item['detalle'], 'style' => 0],
                6 => ['value' => $item['cantidad'], 'style' => self::NUMBER_FORMAT],
                7 => ['value' => $item['precio'], 'style' => self::NUMBER_FORMAT],
                8 => ['value' => $item['subtotal'], 'style' => self::NUMBER_FORMAT],
                9 => ['value' => $item['local'], 'style' => 0],
                10 => ['value' => $item['cliente'], 'style' => 0],
                11 => ['value' => $item['categoria'], 'style' => 0],
                12 => ['value' => $item['vendedor'], 'style' => 0],
            ]);
        }

        return $this->worksheetXml($rows, [], "A1:L{$rowNumber}", [
            ['min' => 1, 'max' => 1, 'width' => 14],
            ['min' => 2, 'max' => 2, 'width' => 18],
            ['min' => 3, 'max' => 3, 'width' => 14],
            ['min' => 4, 'max' => 4, 'width' => 32],
            ['min' => 5, 'max' => 5, 'width' => 22],
            ['min' => 6, 'max' => 8, 'width' => 14],
            ['min' => 9, 'max' => 12, 'width' => 22],
        ], 1);
    }

    /**
     * @param  array<int, array<string, string|float|int>>  $sales
     */
    private function buildSalesSheetXml(array $sales): string
    {
        $rows = [];
        $headers = ['Venta', 'Fecha', 'Estado', 'Cliente', 'Local', 'Métodos de pago', 'Subtotal', 'Descuento', 'Total', 'Pagado', 'Vuelto'];
        $rowNumber = 1;

        $headerCells = [];
        foreach ($headers as $index => $label) {
            $headerCells[$index + 1] = ['value' => $label, 'style' => 3];
        }

        $rows[] = $this->rowXml($rowNumber, $headerCells);

        foreach ($sales as $sale) {
            $rowNumber++;
            $rows[] = $this->rowXml($rowNumber, [
                1 => ['value' => $sale['venta'], 'style' => 0],
                2 => ['value' => $sale['fecha'], 'style' => 0],
                3 => ['value' => $sale['estado'], 'style' => 0],
                4 => ['value' => $sale['cliente'], 'style' => 0],
                5 => ['value' => $sale['local'], 'style' => 0],
                6 => ['value' => $sale['pagos'], 'style' => 0],
                7 => ['value' => $sale['subtotal'], 'style' => self::NUMBER_FORMAT],
                8 => ['value' => $sale['descuento'], 'style' => self::NUMBER_FORMAT],
                9 => ['value' => $sale['total'], 'style' => self::NUMBER_FORMAT],
                10 => ['value' => $sale['pagado'], 'style' => self::NUMBER_FORMAT],
                11 => ['value' => $sale['vuelto'], 'style' => self::NUMBER_FORMAT],
            ]);
        }

        return $this->worksheetXml($rows, [], "A1:K{$rowNumber}", [
            ['min' => 1, 'max' => 1, 'width' => 14],
            ['min' => 2, 'max' => 2, 'width' => 18],
            ['min' => 3, 'max' => 3, 'width' => 14],
            ['min' => 4, 'max' => 4, 'width' => 28],
            ['min' => 5, 'max' => 5, 'width' => 22],
            ['min' => 6, 'max' => 6, 'width' => 22],
            ['min' => 7, 'max' => 11, 'width' => 14],
        ], 1);
    }

    /**
     * @param  array<int, array<string, string|float|int>>  $transactions
     */
    private function buildTransactionsSheetXml(array $transactions): string
    {
        $rows = [];
        $headers = ['Venta', 'Fecha', 'Cliente', 'Local', 'Método', 'Monto', 'Referencia'];
        $rowNumber = 1;

        $headerCells = [];
        foreach ($headers as $index => $label) {
            $headerCells[$index + 1] = ['value' => $label, 'style' => 3];
        }

        $rows[] = $this->rowXml($rowNumber, $headerCells);

        foreach ($transactions as $transaction) {
            $rowNumber++;
            $rows[] = $this->rowXml($rowNumber, [
                1 => ['value' => $transaction['venta'], 'style' => 0],
                2 => ['value' => $transaction['fecha'], 'style' => 0],
                3 => ['value' => $transaction['cliente'], 'style' => 0],
                4 => ['value' => $transaction['local'], 'style' => 0],
                5 => ['value' => $transaction['metodo'], 'style' => 0],
                6 => ['value' => $transaction['monto'], 'style' => self::NUMBER_FORMAT],
                7 => ['value' => $transaction['referencia'], 'style' => 0],
            ]);
        }

        return $this->worksheetXml($rows, [], "A1:G{$rowNumber}", [
            ['min' => 1, 'max' => 1, 'width' => 14],
            ['min' => 2, 'max' => 2, 'width' => 18],
            ['min' => 3, 'max' => 3, 'width' => 28],
            ['min' => 4, 'max' => 4, 'width' => 22],
            ['min' => 5, 'max' => 5, 'width' => 18],
            ['min' => 6, 'max' => 6, 'width' => 14],
            ['min' => 7, 'max' => 7, 'width' => 20],
        ], 1);
    }

    /**
     * @param  list<string>  $rows
     * @param  list<string>  $merges
     * @param  array<int, array{min:int,max:int,width:float}>  $columns
     */
    private function worksheetXml(array $rows, array $merges, string $dimension, array $columns, int $freezeRow): string
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

        $mergeXml = '';

        if ($merges !== []) {
            $mergeXml = '<mergeCells count="'.count($merges).'">'.implode('', array_map(
                static fn (string $merge): string => '<mergeCell ref="'.htmlspecialchars($merge, ENT_XML1 | ENT_QUOTES, 'UTF-8').'"/>',
                $merges
            )).'</mergeCells>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="'.$freezeRow.'" topLeftCell="A'.($freezeRow + 1).'" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="15"/>'
            .'<cols>'.$columnsXml.'</cols>'
            .'<sheetData>'.implode('', $rows).'</sheetData>'
            .$mergeXml
            .'<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>'
            .'</worksheet>';
    }

    /**
     * @param  array<int, array{value:mixed,style?:int|null}>  $cells
     */
    private function rowXml(int $rowNumber, array $cells): string
    {
        $xml = '<row r="'.$rowNumber.'">';

        ksort($cells);

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

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet3.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet4.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'</Types>';
    }

    private function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            .'</Relationships>';
    }

    /**
     * @param  list<string>  $sheets
     */
    private function appXml(array $sheets): string
    {
        $sheetCount = count($sheets);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            .'<Application>Microsoft Excel</Application>'
            .'<DocSecurity>0</DocSecurity>'
            .'<ScaleCrop>false</ScaleCrop>'
            .'<HeadingPairs><vt:vector size="2" baseType="variant"><vt:variant><vt:lpstr>Hojas</vt:lpstr></vt:variant><vt:variant><vt:i4>'.$sheetCount.'</vt:i4></vt:variant></vt:vector></HeadingPairs>'
            .'<TitlesOfParts><vt:vector size="'.$sheetCount.'" baseType="lpstr">'.implode('', array_map(
                static fn (string $sheet): string => '<vt:lpstr>'.htmlspecialchars($sheet, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</vt:lpstr>',
                $sheets
            )).'</vt:vector></TitlesOfParts>'
            .'</Properties>';
    }

    private function coreXml(): string
    {
        $timestamp = now()->utc()->format('Y-m-d\TH:i:s\Z');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            .'<dc:creator>Sass Trend</dc:creator>'
            .'<cp:lastModifiedBy>Sass Trend</cp:lastModifiedBy>'
            .'<dcterms:created xsi:type="dcterms:W3CDTF">'.$timestamp.'</dcterms:created>'
            .'<dcterms:modified xsi:type="dcterms:W3CDTF">'.$timestamp.'</dcterms:modified>'
            .'</cp:coreProperties>';
    }

    /**
     * @param  list<string>  $sheets
     */
    private function workbookXml(array $sheets): string
    {
        $sheetXml = '';

        foreach ($sheets as $index => $sheet) {
            $sheetXml .= '<sheet name="'.htmlspecialchars($sheet, ENT_XML1 | ENT_QUOTES, 'UTF-8').'" sheetId="'.($index + 1).'" r:id="rId'.($index + 1).'"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<fileVersion appName="xl"/>'
            .'<workbookPr/>'
            .'<bookViews><workbookView activeTab="0" firstSheet="0" visibility="visible"/></bookViews>'
            .'<sheets>'.$sheetXml.'</sheets>'
            .'<calcPr calcId="124519" fullCalcOnLoad="1"/>'
            .'</workbook>';
    }

    /**
     * @param  list<string>  $sheets
     */
    private function workbookRelsXml(array $sheets): string
    {
        $relationships = '';

        foreach ($sheets as $index => $_sheet) {
            $relationships .= '<Relationship Id="rId'.($index + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.($index + 1).'.xml"/>';
        }

        $relationships .= '<Relationship Id="rId'.(count($sheets) + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .$relationships
            .'</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="4">'
            .'<font><sz val="11"/><name val="Calibri"/><family val="2"/></font>'
            .'<font><b/><sz val="12"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>'
            .'<font><b/><sz val="11"/><name val="Calibri"/><family val="2"/></font>'
            .'</fonts>'
            .'<fills count="3">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="'.self::PURPLE.'"/><bgColor indexed="64"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="5">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="2" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'<xf numFmtId="'.self::NUMBER_FORMAT.'" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            .'</cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            .'<dxfs count="0"/>'
            .'<tableStyles count="0" defaultTableStyle="TableStyleMedium2" defaultPivotStyle="PivotStyleLight16"/>'
            .'</styleSheet>';
    }
}
