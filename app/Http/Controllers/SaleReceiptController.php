<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\User;
use App\Services\Sales\SalesWorkbookExport;
use App\Services\Sales\SaleListingQuery;
use App\Services\Sales\SaleManagementGuard;
use App\Services\Sales\SalePaymentMethodCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SaleReceiptController extends Controller
{
    public function __construct(
        private readonly SaleManagementGuard $guard,
        private readonly SaleListingQuery $saleListingQuery,
        private readonly SalesWorkbookExport $salesWorkbookExport,
    ) {}

    public function show(Request $request, int $sale): View
    {
        $this->guard->ensureCanView($this->authUser());

        $record = Sale::query()
            ->withTrashed()
            ->with(['branch', 'client', 'items.product.presentation', 'items.service', 'payments'])
            ->findOrFail($sale);

        return view('sales.receipt', [
            'sale' => $record,
            'paymentMethods' => SalePaymentMethodCatalog::options(),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->guard->ensureCanView($this->authUser());

        $path = $this->salesWorkbookExport->export($this->filtersFromRequest($request));

        return response()->download($path, 'ventas-'.now()->format('Ymd-His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * @return array{search:string,period:string,client:string,status:string,payment:string,branch:string}
     */
    private function filtersFromRequest(Request $request): array
    {
        return [
            'search' => (string) $request->string('q')->value(),
            'period' => (string) $request->string('period', '7')->value(),
            'client' => (string) $request->string('client')->value(),
            'status' => (string) $request->string('status')->value(),
            'payment' => (string) $request->string('payment')->value(),
            'branch' => (string) $request->string('branch')->value(),
        ];
    }

    private function authUser(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
