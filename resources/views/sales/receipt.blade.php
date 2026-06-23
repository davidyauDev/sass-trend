<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Comprobante de pago {{ $sale->ticket_number }}</title>
        <style>
            body { font-family: Arial, sans-serif; background:#f5f5f5; margin:0; padding:24px; }
            .receipt { width:320px; margin:0 auto; background:white; padding:24px 20px; box-shadow:0 10px 30px rgba(0,0,0,.08); }
            .center { text-align:center; }
            .muted { color:#666; font-size:12px; }
            .section { border-top:1px solid #ddd; padding-top:16px; margin-top:16px; }
            .row { display:flex; justify-content:space-between; gap:12px; margin-top:8px; font-size:14px; }
            .total { font-weight:700; }
            @media print { body { background:white; padding:0; } .receipt { box-shadow:none; width:100%; } }
        </style>
    </head>
    <body>
        <div class="receipt">
            <div class="center">
                <h2 style="margin:0 0 12px;">{{ $sale->branch?->name ?? 'Local' }}</h2>
            </div>

            <div class="section">
                <strong>Detalle de la venta</strong>
                @foreach ($sale->items as $item)
                    <div class="row">
                        <span>{{ $item->item_name }} x{{ rtrim(rtrim((string) $item->quantity, '0'), '.') }}</span>
                        <span>S/{{ number_format((float) $item->subtotal, 2) }}</span>
                    </div>
                @endforeach
            </div>

            <div class="section">
                <div class="row total"><span>TOTAL</span><span>S/{{ number_format((float) $sale->total, 2) }}</span></div>
                <div class="row"><span>Monto pagado</span><span>S/{{ number_format((float) $sale->paid_total, 2) }}</span></div>
                <div class="row"><span>Monto por pagar</span><span>S/{{ number_format(max(0, (float) $sale->total - (float) $sale->paid_total), 2) }}</span></div>
            </div>

            <div class="section center">
                <div><strong>Venta #{{ $sale->sale_number }}</strong></div>
                <div>Ticket #{{ $sale->ticket_number }}</div>
                <div>{{ $sale->sold_at?->format('d-m-Y H:i') }}</div>
                @foreach ($sale->payments as $payment)
                    <div>Pagado en: {{ $paymentMethods[$payment->method] ?? $payment->method }}</div>
                @endforeach
            </div>
        </div>
    </body>
</html>
