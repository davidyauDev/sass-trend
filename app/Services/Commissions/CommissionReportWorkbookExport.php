<?php

namespace App\Services\Commissions;

use App\Models\Branch;
use App\Models\Professional;
use App\Models\SaleItem;
use App\Models\Service;
use App\Services\Sales\SalePaymentMethodCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use RuntimeException;
use ZipArchive;

final class CommissionReportWorkbookExport
{
    private const GREEN = 'FF21C58D';

    private const DARK_GREEN = 'FF1A9B73';

    private const NUMBER_FORMAT = 4;

    /**
     * @param  array{period?:string,branchId?:string,userType?:string,professionalId?:string}  $filters
     */
    public function export(array $filters): string
    {
        $period = $this->resolvedPeriod((string) ($filters['period'] ?? 'last_7_days'));
        $branchId = (string) ($filters['branchId'] ?? '');
        $userType = (string) ($filters['userType'] ?? 'active_professionals');
        $professionalId = (string) ($filters['professionalId'] ?? 'all');

        $items = $this->itemsQuery($period, $branchId)
            ->with([
                'sale.branch',
                'sale.user',
                'sale.payments',
                'service.category',
                'product.category',
            ])
            ->get();

        $detailRows = $this->buildDetailRows($items, $userType, $professionalId);
        $professionals = $this->buildProfessionalsCatalog($detailRows, $userType);
        $summaryRows = $this->buildSummaryRows($detailRows, $professionals);
        $dailyRows = $this->buildDailyRows($detailRows, $professionals, $period);
        $productionRows = $this->buildProductionRows($detailRows);

        $sheets = [
            ['name' => 'resumen', 'xml' => $this->buildSummarySheetXml($summaryRows)],
            ['name' => 'Recaudaciones por fecha', 'xml' => $this->buildDailySheetXml($dailyRows, $professionals)],
            ['name' => 'produccion', 'xml' => $this->buildProductionSheetXml($productionRows)],
        ];

        foreach ($professionals as $professional) {
            $sheets[] = [
                'name' => $this->sanitizeSheetName($professional['professional_name'].' ('.$professional['professional_id'].')'),
                'xml' => $this->buildProfessionalSheetXml($professional, $detailRows),
            ];
        }

        $path = tempnam(sys_get_temp_dir(), 'commission-report-');

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

        $sheetNames = array_map(static fn (array $sheet): string => $sheet['name'], $sheets);

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml($sheets));
        $zip->addFromString('_rels/.rels', $this->rootRelsXml());
        $zip->addFromString('docProps/app.xml', $this->appXml($sheetNames));
        $zip->addFromString('docProps/core.xml', $this->coreXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml($sheetNames));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml(count($sheets)));
        $zip->addFromString('xl/styles.xml', $this->stylesXml());

        foreach ($sheets as $index => $sheet) {
            $zip->addFromString('xl/worksheets/sheet'.($index + 1).'.xml', $sheet['xml']);
        }

        $zip->close();

        return $xlsxPath;
    }

    /**
     * @return array{start:CarbonImmutable,end:CarbonImmutable,label:string}
     */
    private function resolvedPeriod(string $period): array
    {
        $today = CarbonImmutable::today();

        return match ($period) {
            'today' => [
                'start' => $today,
                'end' => $today,
                'label' => $today->format('d/m/Y').' - '.$today->format('d/m/Y'),
            ],
            'last_15_days' => [
                'start' => $today->subDays(15),
                'end' => $today,
                'label' => $today->subDays(15)->format('d/m/Y').' - '.$today->format('d/m/Y'),
            ],
            'last_30_days' => [
                'start' => $today->subDays(30),
                'end' => $today,
                'label' => $today->subDays(30)->format('d/m/Y').' - '.$today->format('d/m/Y'),
            ],
            default => [
                'start' => $today->subDays(7),
                'end' => $today,
                'label' => $today->subDays(7)->format('d/m/Y').' - '.$today->format('d/m/Y'),
            ],
        };
    }

    /**
     * @return Builder<SaleItem>
     */
    private function itemsQuery(array $period, string $branchId): Builder
    {
        return SaleItem::query()
            ->whereIn('item_type', ['service', 'product'])
            ->whereHas('sale', function (Builder $query) use ($period, $branchId): void {
                $query
                    ->whereNull('deleted_at')
                    ->whereIn('status', ['paid', 'partial'])
                    ->whereBetween('sold_at', [$period['start']->startOfDay(), $period['end']->endOfDay()]);

                if ($branchId !== '') {
                    $query->where('branch_id', (int) $branchId);
                }
            });
    }

    /**
     * @param  Collection<int, SaleItem>  $items
     * @return Collection<int, array<string, mixed>>
     */
    private function buildDetailRows(Collection $items, string $userType, string $professionalIdFilter): Collection
    {
        $professionals = Professional::query()
            ->when($userType === 'active_professionals', fn (Builder $query): Builder => $query->where('is_active', true))
            ->get()
            ->keyBy('id');

        return $items->map(function (SaleItem $item) use ($professionals, $professionalIdFilter): ?array {
            $professionalId = (int) data_get($item->meta, 'professional_id', 0);

            if ($professionalId === 0) {
                return null;
            }

            if ($professionalIdFilter !== 'all' && $professionalId !== (int) $professionalIdFilter) {
                return null;
            }

            /** @var Professional|null $professional */
            $professional = $professionals->get($professionalId);

            if (! $professional instanceof Professional) {
                return null;
            }

            $sale = $item->sale;
            $subtotal = round((float) $item->subtotal, 2);
            $gross = round((float) $item->quantity * (float) $item->unit_price, 2);
            $discountAmount = max(0, round($gross - $subtotal, 2));
            $discountPercent = $gross > 0 ? round(($discountAmount / $gross) * 100, 2) : 0.0;
            $commissionAmount = $this->calculateCommission($item, $professional);
            $commissionType = $this->commissionType($item, $professional);
            $commissionLabel = $commissionType === 'amount'
                ? $this->formatNumber($commissionAmount)
                : $this->formatNumber($this->commissionRate($item, $professional)).'%';

            return [
                'id_pago' => (int) $item->id,
                'fecha_pago' => $sale?->sold_at?->format('d/m/Y') ?? '',
                'fecha_pago_hora' => $sale?->sold_at?->format('d/m/Y H:i') ?? '',
                'prestador' => $professional->public_name,
                'professional_id' => $professionalId,
                'tipo' => $item->item_type === 'service' ? 'Servicio' : 'Producto',
                'categoria' => $item->item_type === 'service'
                    ? (string) data_get($item, 'service.category.name', 'Sin categoría')
                    : (string) data_get($item, 'product.category.name', 'Sin categoría'),
                'nombre' => (string) $item->item_name,
                'precio_lista' => (float) $item->unit_price,
                'dscto_aplica' => $discountPercent,
                'total' => $subtotal,
                'comision' => $commissionLabel,
                'comision_pagar' => $commissionAmount,
                'medios_pago' => $sale?->payments
                    ->pluck('method')
                    ->map(fn (string $method): string => SalePaymentMethodCatalog::options()[$method] ?? $method)
                    ->implode(', ') ?? '',
                'comentario' => (string) ($item->item_detail ?? $sale?->notes ?? ''),
                'numero_com' => (string) ($sale?->sale_number ?? $sale?->id ?? $item->id),
                'usuario_ultima_modificacion' => (string) data_get($sale, 'user.name', 'Sin usuario'),
                'sale_date_key' => $sale?->sold_at?->format('Y-m-d') ?? '',
                'sale_total' => $subtotal,
            ];
        })->filter()->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $detailRows
     * @return array<int, array<string, mixed>>
     */
    private function buildProfessionalsCatalog(Collection $detailRows, string $userType): array
    {
        $professionals = Professional::query()
            ->when($userType === 'active_professionals', fn (Builder $query): Builder => $query->where('is_active', true))
            ->get()
            ->keyBy('id');

        $rows = [];

        foreach ($detailRows->groupBy('professional_id') as $professionalId => $group) {
            /** @var Professional|null $professional */
            $professional = $professionals->get((int) $professionalId);

            if (! $professional instanceof Professional) {
                continue;
            }

            $servicesSales = 0.0;
            $productsSales = 0.0;
            $commissionAmount = 0.0;

            foreach ($group as $row) {
                if ($row['tipo'] === 'Servicio') {
                    $servicesSales += (float) $row['total'];
                } else {
                    $productsSales += (float) $row['total'];
                }

                $commissionAmount += (float) $row['comision_pagar'];
            }

            $rows[] = [
                'professional_id' => (int) $professionalId,
                'professional_name' => $professional->public_name,
                'reservas' => 0.0,
                'servicios' => round($servicesSales, 2),
                'productos' => round($productsSales, 2),
                'planes' => 0.0,
                'total_comisiones' => round($commissionAmount, 2),
                'compras_pro' => 0.0,
                'comisiones_netas' => round($commissionAmount, 2),
            ];
        }

        return collect($rows)
            ->sortBy('professional_name')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $professionals
     * @return array<int, array<string, mixed>>
     */
    private function buildSummaryRows(Collection $detailRows, array $professionals): array
    {
        $rows = [];
        $totalReservas = 0.0;
        $totalServicios = 0.0;
        $totalProductos = 0.0;
        $totalPlanes = 0.0;
        $totalComisiones = 0.0;
        $totalCompras = 0.0;

        foreach ($professionals as $professional) {
            $rows[] = $professional;
            $totalReservas += (float) $professional['reservas'];
            $totalServicios += (float) $professional['servicios'];
            $totalProductos += (float) $professional['productos'];
            $totalPlanes += (float) $professional['planes'];
            $totalComisiones += (float) $professional['total_comisiones'];
            $totalCompras += (float) $professional['compras_pro'];
        }

        $rows[] = [
            'professional_id' => 0,
            'professional_name' => 'TOTAL',
            'reservas' => round($totalReservas, 2),
            'servicios' => round($totalServicios, 2),
            'productos' => round($totalProductos, 2),
            'planes' => round($totalPlanes, 2),
            'total_comisiones' => round($totalComisiones, 2),
            'compras_pro' => round($totalCompras, 2),
            'comisiones_netas' => round($totalComisiones - $totalCompras, 2),
        ];

        return $rows;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $detailRows
     * @param  array<int, array<string, mixed>>  $professionals
     * @return array<int, array<string, mixed>>
     */
    private function buildDailyRows(Collection $detailRows, array $professionals, array $period): array
    {
        $rowsByDate = [];

        foreach ($detailRows as $row) {
            $dateKey = (string) $row['sale_date_key'];
            $professionalId = (int) $row['professional_id'];

            $rowsByDate[$dateKey] ??= [];
            $rowsByDate[$dateKey][$professionalId] ??= [
                'ventas' => 0.0,
                'comisiones' => 0.0,
                'compras' => 0.0,
            ];

            $rowsByDate[$dateKey][$professionalId]['ventas'] += (float) $row['total'];
            $rowsByDate[$dateKey][$professionalId]['comisiones'] += (float) $row['comision_pagar'];
        }

        $dailyRows = [];

        $start = $period['start'];
        $end = $period['end'];

        foreach ($start->daysUntil($end->addDay()) as $day) {
            $dateKey = $day->format('Y-m-d');
            $row = ['fecha' => $day->format('d/m/Y')];
            $totalVentas = 0.0;
            $totalComisiones = 0.0;
            $totalCompras = 0.0;

            foreach ($professionals as $professional) {
                $values = $rowsByDate[$dateKey][$professional['professional_id']] ?? ['ventas' => 0.0, 'comisiones' => 0.0, 'compras' => 0.0];

                $row['professional_'.$professional['professional_id'].'_ventas'] = round($values['ventas'], 2);
                $row['professional_'.$professional['professional_id'].'_comisiones'] = round($values['comisiones'], 2);
                $row['professional_'.$professional['professional_id'].'_compras'] = round($values['compras'], 2);

                $totalVentas += (float) $values['ventas'];
                $totalComisiones += (float) $values['comisiones'];
                $totalCompras += (float) $values['compras'];
            }

            $row['total_ventas'] = round($totalVentas, 2);
            $row['total_comisiones'] = round($totalComisiones, 2);
            $row['total_compras'] = round($totalCompras, 2);

            $dailyRows[] = $row;
        }

        $totalsRow = ['fecha' => 'Totales'];
        $sumVentas = 0.0;
        $sumComisiones = 0.0;
        $sumCompras = 0.0;

        foreach ($professionals as $professional) {
            $ventas = round(array_sum(array_map(
                fn (array $row): float => (float) ($row['professional_'.$professional['professional_id'].'_ventas'] ?? 0),
                $dailyRows,
            )), 2);
            $comisiones = round(array_sum(array_map(
                fn (array $row): float => (float) ($row['professional_'.$professional['professional_id'].'_comisiones'] ?? 0),
                $dailyRows,
            )), 2);
            $compras = round(array_sum(array_map(
                fn (array $row): float => (float) ($row['professional_'.$professional['professional_id'].'_compras'] ?? 0),
                $dailyRows,
            )), 2);

            $totalsRow['professional_'.$professional['professional_id'].'_ventas'] = $ventas;
            $totalsRow['professional_'.$professional['professional_id'].'_comisiones'] = $comisiones;
            $totalsRow['professional_'.$professional['professional_id'].'_compras'] = $compras;

            $sumVentas += $ventas;
            $sumComisiones += $comisiones;
            $sumCompras += $compras;
        }

        $totalsRow['total_ventas'] = round($sumVentas, 2);
        $totalsRow['total_comisiones'] = round($sumComisiones, 2);
        $totalsRow['total_compras'] = round($sumCompras, 2);

        $dailyRows[] = $totalsRow;

        return $dailyRows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $detailRows
     * @return array<int, array<string, mixed>>
     */
    private function buildProductionRows(Collection $detailRows): array
    {
        return $detailRows->map(function (array $row): array {
            return [
                'id_pago' => $row['id_pago'],
                'fecha_pago' => $row['fecha_pago'],
                'prestador' => $row['prestador'],
                'tipo' => $row['tipo'],
                'categoria' => $row['categoria'],
                'nombre' => $row['nombre'],
                'precio_lista' => $row['precio_lista'],
                'dscto_aplica' => $row['dscto_aplica'],
                'total' => $row['total'],
                'comision' => $row['comision'],
                'comision_pagar' => $row['comision_pagar'],
                'medios_pago' => $row['medios_pago'],
                'comentario' => $row['comentario'],
                'numero_com' => $row['numero_com'],
                'usuario_ultima_modificacion' => $row['usuario_ultima_modificacion'],
            ];
        })->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $detailRows
     */
    private function buildProfessionalSheetXml(array $professional, Collection $detailRows): string
    {
        $rows = [];
        $filtered = $detailRows->where('professional_id', $professional['professional_id'])->values();
        $serviceSales = (float) $filtered->where('tipo', 'Servicio')->sum('total');
        $productSales = (float) $filtered->where('tipo', 'Producto')->sum('total');
        $commissions = (float) $filtered->sum('comision_pagar');

        $rows[] = $this->rowXml(1, [
            1 => ['value' => $professional['professional_name'], 'style' => 2],
        ]);
        $rows[] = $this->rowXml(2, [
            1 => ['value' => 'Servicios', 'style' => 1],
            2 => ['value' => $serviceSales, 'style' => 4],
            3 => ['value' => 'Productos', 'style' => 1],
            4 => ['value' => $productSales, 'style' => 4],
            5 => ['value' => 'Comisiones', 'style' => 1],
            6 => ['value' => $commissions, 'style' => 4],
        ]);

        $tableHeaders = ['Id pago', 'Fecha pago', 'Prestador', 'Tipo', 'Categoría', 'Nombre', 'Precio Lista', 'Dscto. aplica', 'Total', 'Comisión', 'Comisión a pagar', 'Medios de pago', 'Comentario', 'N° de com', 'Usuario última modificación'];
        $rows[] = $this->rowXml(4, $this->headerCells($tableHeaders));

        $rowNumber = 4;

        foreach ($filtered as $row) {
            $rowNumber++;
            $rows[] = $this->rowXml($rowNumber, [
                1 => ['value' => $row['id_pago'], 'style' => 0],
                2 => ['value' => $row['fecha_pago'], 'style' => 0],
                3 => ['value' => $row['prestador'], 'style' => 0],
                4 => ['value' => $row['tipo'], 'style' => 0],
                5 => ['value' => $row['categoria'], 'style' => 0],
                6 => ['value' => $row['nombre'], 'style' => 0],
                7 => ['value' => $row['precio_lista'], 'style' => 4],
                8 => ['value' => $row['dscto_aplica'], 'style' => 4],
                9 => ['value' => $row['total'], 'style' => 4],
                10 => ['value' => $row['comision'], 'style' => 0],
                11 => ['value' => $row['comision_pagar'], 'style' => 4],
                12 => ['value' => $row['medios_pago'], 'style' => 0],
                13 => ['value' => $row['comentario'], 'style' => 0],
                14 => ['value' => $row['numero_com'], 'style' => 0],
                15 => ['value' => $row['usuario_ultima_modificacion'], 'style' => 0],
            ]);
        }

        return $this->worksheetXml($rows, "A1:O{$rowNumber}", [
            ['min' => 1, 'max' => 1, 'width' => 14],
            ['min' => 2, 'max' => 2, 'width' => 18],
            ['min' => 3, 'max' => 3, 'width' => 18],
            ['min' => 4, 'max' => 4, 'width' => 14],
            ['min' => 5, 'max' => 5, 'width' => 16],
            ['min' => 6, 'max' => 6, 'width' => 28],
            ['min' => 7, 'max' => 11, 'width' => 14],
            ['min' => 12, 'max' => 12, 'width' => 18],
            ['min' => 13, 'max' => 15, 'width' => 20],
        ], 4);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, array<string, mixed>>  $professionals
     */
    private function buildSummarySheetXml(array $rows): string
    {
        $headers = ['Prestador', 'Reservas', 'Servicios no agendados', 'Productos', 'Planes', 'Total comisiones', 'Total compras de pro', 'Comisiones Netas'];
        $xmlRows = [];
        $rowNumber = 1;

        $xmlRows[] = $this->rowXml($rowNumber, $this->headerCells($headers));

        foreach ($rows as $row) {
            $rowNumber++;
            $xmlRows[] = $this->rowXml($rowNumber, [
                1 => ['value' => $row['professional_name'], 'style' => 0],
                2 => ['value' => $row['reservas'], 'style' => 4],
                3 => ['value' => $row['servicios'], 'style' => 4],
                4 => ['value' => $row['productos'], 'style' => 4],
                5 => ['value' => $row['planes'], 'style' => 4],
                6 => ['value' => $row['total_comisiones'], 'style' => 4],
                7 => ['value' => $row['compras_pro'], 'style' => 4],
                8 => ['value' => $row['comisiones_netas'], 'style' => 4],
            ]);
        }

        return $this->worksheetXml($xmlRows, "A1:H{$rowNumber}", [
            ['min' => 1, 'max' => 1, 'width' => 24],
            ['min' => 2, 'max' => 2, 'width' => 12],
            ['min' => 3, 'max' => 3, 'width' => 20],
            ['min' => 4, 'max' => 4, 'width' => 14],
            ['min' => 5, 'max' => 5, 'width' => 12],
            ['min' => 6, 'max' => 8, 'width' => 16],
        ], 1);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, array<string, mixed>>  $professionals
     */
    private function buildDailySheetXml(array $rows, array $professionals): string
    {
        $xmlRows = [];
        $rowNumber = 1;
        $mergeRanges = [];
        $column = 2;

        $xmlRows[] = $this->rowXml(1, [
            1 => ['value' => 'Fecha', 'style' => 1],
        ]);

        foreach ($professionals as $professional) {
            $name = $professional['professional_name'];
            $xmlRows[0] = $this->rowXml(1, [
                1 => ['value' => 'Fecha', 'style' => 1],
                $column => ['value' => $name, 'style' => 1],
            ]);
            $mergeRanges[] = $this->columnName($column).':'.$this->columnName($column + 2);
            $column += 3;
        }

        $totalsStart = $column;
        $xmlRows[0] = $this->rowXml(1, []);

        $headerCells = [1 => ['value' => 'Fecha', 'style' => 1]];
        $column = 2;
        foreach ($professionals as $professional) {
            $headerCells[$column] = ['value' => $professional['professional_name'], 'style' => 1];
            $headerCells[$column + 1] = ['value' => '', 'style' => 1];
            $headerCells[$column + 2] = ['value' => '', 'style' => 1];
            $column += 3;
        }
        $headerCells[$totalsStart] = ['value' => 'Total ventas', 'style' => 1];
        $headerCells[$totalsStart + 1] = ['value' => 'Total comisiones', 'style' => 1];
        $headerCells[$totalsStart + 2] = ['value' => 'Total compras internas', 'style' => 1];
        $xmlRows[0] = $this->rowXml(1, $headerCells);

        $rowsHeader = [1 => ['value' => 'Fecha', 'style' => 1]];
        $column = 2;
        foreach ($professionals as $professional) {
            $rowsHeader[$column] = ['value' => 'Ventas', 'style' => 1];
            $rowsHeader[$column + 1] = ['value' => 'Comisión', 'style' => 1];
            $rowsHeader[$column + 2] = ['value' => 'Compras', 'style' => 1];
            $column += 3;
        }
        $rowsHeader[$totalsStart] = ['value' => 'Total ventas', 'style' => 1];
        $rowsHeader[$totalsStart + 1] = ['value' => 'Total comisiones', 'style' => 1];
        $rowsHeader[$totalsStart + 2] = ['value' => 'Total compras internas', 'style' => 1];

        $rowsXml = [$this->rowXml(1, $rowsHeader)];

        foreach ($rows as $row) {
            $rowNumber++;
            $cells = [1 => ['value' => $row['fecha'], 'style' => 0]];
            $column = 2;
            foreach ($professionals as $professional) {
                $id = (int) $professional['professional_id'];
                $cells[$column] = ['value' => $row['professional_'.$id.'_ventas'], 'style' => 4];
                $cells[$column + 1] = ['value' => $row['professional_'.$id.'_comisiones'], 'style' => 4];
                $cells[$column + 2] = ['value' => $row['professional_'.$id.'_compras'], 'style' => 4];
                $column += 3;
            }
            $cells[$totalsStart] = ['value' => $row['total_ventas'], 'style' => 4];
            $cells[$totalsStart + 1] = ['value' => $row['total_comisiones'], 'style' => 4];
            $cells[$totalsStart + 2] = ['value' => $row['total_compras'], 'style' => 4];
            $rowsXml[] = $this->rowXml($rowNumber, $cells);
        }

        $dimensionEnd = $this->columnName($totalsStart + 2).$rowNumber;

        return $this->worksheetXml($rowsXml, 'A1:'.$dimensionEnd, [], 2);
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function buildProductionSheetXml(array $rows): string
    {
        $headers = ['Id pago', 'Fecha pago', 'Prestador', 'Tipo', 'Categoría', 'Nombre', 'Precio Lista', 'Dscto. aplica', 'Total', 'Comisión', 'Comisión a pagar', 'Medios de pago', 'Comentario', 'N° de com', 'Usuario última modificación'];
        $xmlRows = [$this->rowXml(1, $this->headerCells($headers))];
        $rowNumber = 1;

        foreach ($rows as $row) {
            $rowNumber++;
            $xmlRows[] = $this->rowXml($rowNumber, [
                1 => ['value' => $row['id_pago'], 'style' => 0],
                2 => ['value' => $row['fecha_pago'], 'style' => 0],
                3 => ['value' => $row['prestador'], 'style' => 0],
                4 => ['value' => $row['tipo'], 'style' => 0],
                5 => ['value' => $row['categoria'], 'style' => 0],
                6 => ['value' => $row['nombre'], 'style' => 0],
                7 => ['value' => $row['precio_lista'], 'style' => 4],
                8 => ['value' => $row['dscto_aplica'], 'style' => 4],
                9 => ['value' => $row['total'], 'style' => 4],
                10 => ['value' => $row['comision'], 'style' => 0],
                11 => ['value' => $row['comision_pagar'], 'style' => 4],
                12 => ['value' => $row['medios_pago'], 'style' => 0],
                13 => ['value' => $row['comentario'], 'style' => 0],
                14 => ['value' => $row['numero_com'], 'style' => 0],
                15 => ['value' => $row['usuario_ultima_modificacion'], 'style' => 0],
            ]);
        }

        return $this->worksheetXml($xmlRows, 'A1:O'.$rowNumber, [
            ['min' => 1, 'max' => 1, 'width' => 14],
            ['min' => 2, 'max' => 2, 'width' => 18],
            ['min' => 3, 'max' => 3, 'width' => 18],
            ['min' => 4, 'max' => 4, 'width' => 14],
            ['min' => 5, 'max' => 5, 'width' => 16],
            ['min' => 6, 'max' => 6, 'width' => 28],
            ['min' => 7, 'max' => 11, 'width' => 14],
            ['min' => 12, 'max' => 12, 'width' => 18],
            ['min' => 13, 'max' => 15, 'width' => 20],
        ], 1);
    }

    /**
     * @param  array<int, string>  $headers
     * @return array<int, array{value:mixed,style?:int}>
     */
    private function headerCells(array $headers): array
    {
        $cells = [];

        foreach ($headers as $index => $header) {
            $cells[$index + 1] = ['value' => $header, 'style' => 1];
        }

        return $cells;
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

    private function formatNumber(float $value): string
    {
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

    private function commissionType(SaleItem $item, Professional $professional): string
    {
        if ($item->item_type === 'service' && $item->service instanceof Service) {
            $assignment = $item->service->professionalProfiles->firstWhere('id', $professional->id);

            return (string) ($assignment?->pivot?->commission_type ?? $professional->commission_type ?? 'percent');
        }

        if ($item->item_type === 'product' && $item->product instanceof \App\Models\Product) {
            return (string) ($item->product->commission_type ?? 'percent');
        }

        return 'percent';
    }

    private function commissionRate(SaleItem $item, Professional $professional): float
    {
        if ($item->item_type === 'service' && $item->service instanceof Service) {
            $assignment = $item->service->professionalProfiles->firstWhere('id', $professional->id);

            return (float) ($assignment?->pivot?->sale_commission ?? $professional->sale_commission ?? 0);
        }

        if ($item->item_type === 'product' && $item->product instanceof \App\Models\Product) {
            return (float) ($item->product->sale_commission ?? 0);
        }

        return 0.0;
    }

    private function calculateCommission(SaleItem $item, Professional $professional): float
    {
        $revenue = round((float) $item->subtotal, 2);
        $amount = $this->commissionRate($item, $professional);
        $type = $this->commissionType($item, $professional);

        return $type === 'amount'
            ? round(max(0, $amount), 2)
            : round($revenue * max(0, $amount) / 100, 2);
    }

    private function sanitizeSheetName(string $name): string
    {
        $clean = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', ' ', $name) ?? $name;
        $clean = trim(preg_replace('/\\s+/', ' ', $clean) ?? $clean);

        return mb_substr($clean, 0, 31);
    }

    /**
     * @param  list<array{name:string,xml:string}>  $sheets
     */
    private function contentTypesXml(array $sheets): string
    {
        $overrides = '';

        foreach ($sheets as $index => $_sheet) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet'.($index + 1).'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .$overrides
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
            .'<HeadingPairs><vt:vector size="2" baseType="variant"><vt:variant><vt:lpstr>Hojas</vt:lpstr></vt:variant><vt:variant><vt:i4>'.$sheetCount.'</vt:i4></vt:variant></vt:vector></HeadingPairs>'
            .'<TitlesOfParts><vt:vector size="'.$sheetCount.'" baseType="lpstr">'.implode('', array_map(static fn (string $sheet): string => '<vt:lpstr>'.htmlspecialchars($sheet, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</vt:lpstr>', $sheets)).'</vt:vector></TitlesOfParts>'
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
            .'<bookViews><workbookView activeTab="0" firstSheet="0" visibility="visible"/></bookViews>'
            .'<sheets>'.$sheetXml.'</sheets>'
            .'</workbook>';
    }

    private function workbookRelsXml(int $sheetCount): string
    {
        $relationships = '';

        for ($i = 1; $i <= $sheetCount; $i++) {
            $relationships .= '<Relationship Id="rId'.$i.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$i.'.xml"/>';
        }

        $relationships .= '<Relationship Id="rId'.($sheetCount + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .$relationships
            .'</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="3">'
            .'<font><sz val="11"/><name val="Calibri"/><family val="2"/></font>'
            .'<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>'
            .'<font><b/><sz val="12"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>'
            .'</fonts>'
            .'<fills count="3">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            .'<fill><patternFill patternType="solid"><fgColor rgb="'.self::GREEN.'"/><bgColor indexed="64"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="4">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            .'<xf numFmtId="0" fontId="2" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment horizontal="left" vertical="center"/></xf>'
            .'<xf numFmtId="'.self::NUMBER_FORMAT.'" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            .'</cellXfs>'
            .'</styleSheet>';
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
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<dimension ref="'.$dimension.'"/>'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="'.$freezeRow.'" topLeftCell="A'.($freezeRow + 1).'" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="15"/>'
            .'<cols>'.$columnsXml.'</cols>'
            .'<sheetData>'.implode('', $rows).'</sheetData>'
            .'<pageMargins left="0.7" right="0.7" top="0.75" bottom="0.75" header="0.3" footer="0.3"/>'
            .'</worksheet>';
    }
}
