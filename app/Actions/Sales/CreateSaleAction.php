<?php

namespace App\Actions\Sales;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Service;
use App\Models\User;
use App\Services\Products\ProductStockService;
use App\Services\Sales\SaleManagementGuard;
use App\Services\Sales\SaleStatusCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateSaleAction
{
    public function __construct(
        private readonly SaleManagementGuard $guard,
        private readonly ProductStockService $stockService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(User $actor, array $data): Sale
    {
        $this->guard->ensureCanCreate($actor);

        return DB::transaction(function () use ($actor, $data): Sale {
            $branch = Branch::query()->lockForUpdate()->findOrFail((int) $data['branch_id']);
            $status = (string) $data['status'];
            $itemsData = $data['items'];
            $paymentsData = $data['payments'];

            if ($itemsData === [] || count($itemsData) === 0) {
                throw ValidationException::withMessages([
                    'items' => 'Agrega al menos un ítem al carrito.',
                ]);
            }

            $sale = Sale::query()->create([
                'branch_id' => $branch->id,
                'client_id' => $data['client_id'],
                'user_id' => $actor->id,
                'sold_at' => now(),
                'status' => $status,
                'subtotal' => (float) ($data['subtotal'] ?? 0),
                'discount_total' => (float) ($data['discount_total'] ?? 0),
                'total' => (float) ($data['total'] ?? 0),
                'paid_total' => 0,
                'change_total' => 0,
                'notes' => $data['notes'],
            ]);

            $subtotal = 0.0;
            $discountTotal = 0.0;

            foreach ($itemsData as $itemData) {
                $quantity = (float) $itemData['quantity'];
                $unitPrice = (float) $itemData['unit_price'];
                $itemSubtotal = round((float) ($itemData['subtotal'] ?? round($quantity * $unitPrice, 2)), 2);
                $itemDiscountAmount = round((float) ($itemData['discount_amount'] ?? max(0, round($quantity * $unitPrice, 2) - $itemSubtotal)), 2);
                $itemType = (string) $itemData['item_type'];

                $serviceId = null;
                $productId = null;
                $itemName = (string) $itemData['item_name'];
                $itemDetail = $itemData['item_detail'];

                if ($itemType === 'service' && isset($itemData['service_id'])) {
                    $service = Service::query()->findOrFail((int) $itemData['service_id']);
                    $serviceId = $service->id;
                    $itemName = $service->name;

                    $professionalId = data_get($itemData, 'meta.professional_id');

                    if ($professionalId !== null) {
                        $professional = $service->professionalProfiles()->whereKey((int) $professionalId)->first();

                        if ($professional === null) {
                            throw ValidationException::withMessages([
                                'items' => 'El profesional seleccionado no pertenece al servicio.',
                            ]);
                        }

                        $itemData['meta']['professional_name'] = $professional->displayName();
                    }
                }

                if ($itemType === 'product' && isset($itemData['product_id'])) {
                    $product = Product::query()->lockForUpdate()->findOrFail((int) $itemData['product_id']);
                    $productId = $product->id;
                    $itemName = $product->name;
                    $itemDetail ??= trim(implode(' | ', array_filter([
                        $product->brand?->name,
                        $product->presentation?->name,
                    ])));
                }

                $sale->items()->create([
                    'item_type' => $itemType,
                    'service_id' => $serviceId,
                    'product_id' => $productId,
                    'item_name' => $itemName,
                    'item_detail' => $itemDetail,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $itemSubtotal,
                    'meta' => $itemData['meta'] ?? null,
                ]);

                if ($status !== SaleStatusCatalog::DRAFT && $productId !== null) {
                    $product = Product::query()->lockForUpdate()->findOrFail($productId);
                    $this->stockService->decreaseBranchStock($product, $branch, $quantity, $actor);
                }

                $subtotal += round($quantity * $unitPrice, 2);
                $discountTotal += $itemDiscountAmount;
            }

            $total = round(max(0, $subtotal - $discountTotal), 2);
            $paidTotal = round(array_reduce(
                $paymentsData,
                fn (float $carry, array $payment): float => $carry + (float) $payment['amount'],
                0.0,
            ), 2);
            $changeTotal = $status === SaleStatusCatalog::PAID ? max(0, round($paidTotal - $total, 2)) : 0.0;

            foreach ($paymentsData as $paymentData) {
                $sale->payments()->create([
                    'method' => $paymentData['method'],
                    'amount' => $paymentData['amount'],
                    'reference' => $paymentData['reference'],
                    'paid_at' => now(),
                    'notes' => null,
                ]);
            }

            $sale->update([
                'sale_number' => (string) (1000 + $sale->id),
                'ticket_number' => (string) (42250000 + $sale->id),
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'total' => $total,
                'paid_total' => $paidTotal,
                'change_total' => $changeTotal,
            ]);

            return $sale->load(['branch', 'client', 'user', 'items.product.presentation', 'items.service', 'payments']);
        });
    }
}
