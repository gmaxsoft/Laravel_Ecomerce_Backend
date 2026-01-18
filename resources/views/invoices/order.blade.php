<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faktura {{ $order->order_number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
        }
        .header {
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .invoice-number {
            font-size: 14px;
            color: #666;
        }
        .company-info {
            margin-bottom: 30px;
        }
        .customer-info {
            margin-bottom: 30px;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-section h3 {
            font-size: 14px;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .total-section {
            margin-top: 20px;
            float: right;
            width: 300px;
        }
        .total-row {
            padding: 5px 0;
        }
        .total-row.total {
            font-weight: bold;
            font-size: 16px;
            border-top: 2px solid #333;
            margin-top: 10px;
            padding-top: 10px;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="invoice-title">FAKTURA</div>
        <div class="invoice-number">Numer: {{ $order->order_number }}</div>
        <div>Data wystawienia: {{ $order->created_at->format('d.m.Y') }}</div>
    </div>

    <div class="company-info">
        <div class="info-section">
            <h3>Sprzedawca</h3>
            <div>E-commerce Store</div>
            <div>ul. Przykładowa 123</div>
            <div>00-000 Warszawa</div>
            <div>NIP: 1234567890</div>
        </div>
    </div>

    <div class="customer-info">
        <div class="info-section">
            <h3>Nabywca</h3>
            <div>{{ $order->shipping_name }}</div>
            <div>{{ $order->shipping_address }}</div>
            <div>{{ $order->shipping_postal_code }} {{ $order->shipping_city }}</div>
            <div>{{ $order->shipping_country }}</div>
            @if($order->shipping_email)
            <div>Email: {{ $order->shipping_email }}</div>
            @endif
            @if($order->shipping_phone)
            <div>Telefon: {{ $order->shipping_phone }}</div>
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Lp.</th>
                <th>Nazwa produktu</th>
                <th>SKU</th>
                <th class="text-right">Ilość</th>
                <th class="text-right">Cena jednostkowa</th>
                <th class="text-right">Wartość</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->product_name }}</td>
                <td>{{ $item->product_sku ?? '-' }}</td>
                <td class="text-right">{{ $item->quantity }}</td>
                <td class="text-right">{{ number_format($item->price, 2, ',', ' ') }} zł</td>
                <td class="text-right">{{ number_format($item->subtotal, 2, ',', ' ') }} zł</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-row">
            <span>Wartość netto:</span>
            <span style="float: right;">{{ number_format($order->subtotal, 2, ',', ' ') }} zł</span>
        </div>
        @if($order->tax > 0)
        <div class="total-row">
            <span>Podatek VAT (10%):</span>
            <span style="float: right;">{{ number_format($order->tax, 2, ',', ' ') }} zł</span>
        </div>
        @endif
        @if($order->shipping > 0)
        <div class="total-row">
            <span>Dostawa:</span>
            <span style="float: right;">{{ number_format($order->shipping, 2, ',', ' ') }} zł</span>
        </div>
        @endif
        @if($order->discount > 0)
        <div class="total-row">
            <span>Rabat:
                @if($order->coupon)
                    ({{ $order->coupon->code }})
                @endif
            </span>
            <span style="float: right;">-{{ number_format($order->discount, 2, ',', ' ') }} zł</span>
        </div>
        @endif
        <div class="total-row total">
            <span>Razem do zapłaty:</span>
            <span style="float: right;">{{ number_format($order->total, 2, ',', ' ') }} zł</span>
        </div>
    </div>

    <div style="clear: both;"></div>

    <div class="footer">
        <div>Status zamówienia: {{ ucfirst($order->status) }}</div>
        <div>Status płatności: {{ ucfirst($order->payment_status) }}</div>
        @if($order->payment_method)
        <div>Metoda płatności: {{ ucfirst($order->payment_method) }}</div>
        @endif
    </div>
</body>
</html>
