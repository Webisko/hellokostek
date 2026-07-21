<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <title>Faktura {{ $invoice->number }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #333333;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .header-table td {
            vertical-align: top;
        }
        .invoice-title {
            font-size: 20px;
            font-weight: bold;
            color: #1a1a1a;
            margin: 0 0 5px 0;
            text-transform: uppercase;
        }
        .invoice-number {
            font-size: 14px;
            font-weight: bold;
            color: #555555;
            margin: 0 0 15px 0;
        }
        .meta-label {
            font-weight: bold;
            color: #555555;
            width: 130px;
        }
        .meta-value {
            color: #222222;
        }
        .parties-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .parties-table td {
            width: 50%;
            vertical-align: top;
        }
        .party-title {
            font-size: 12px;
            font-weight: bold;
            color: #1a1a1a;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 5px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        .party-details {
            font-size: 11px;
            color: #374151;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #f3f4f6;
            color: #374151;
            font-weight: bold;
            text-align: left;
            padding: 8px 10px;
            font-size: 10px;
            border-bottom: 2px solid #e5e7eb;
            text-transform: uppercase;
        }
        .items-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        .items-table tr:last-child td {
            border-bottom: 2px solid #d1d5db;
        }
        .text-right {
            text-align: right !important;
        }
        .summary-container-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .summary-container-table td {
            vertical-align: top;
        }
        .vat-summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .vat-summary-table th {
            background-color: #f9fafb;
            color: #4b5563;
            font-weight: bold;
            font-size: 9px;
            padding: 5px 8px;
            border-bottom: 1px solid #e5e7eb;
            text-transform: uppercase;
        }
        .vat-summary-table td {
            padding: 5px 8px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 10px;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 6px 10px;
        }
        .total-due-row td {
            background-color: #f3f4f6;
            font-weight: bold;
            font-size: 13px;
            border-top: 1px solid #d1d5db;
            border-bottom: 1px solid #d1d5db;
        }
        .payment-info {
            margin-top: 30px;
            border-top: 1px dashed #d1d5db;
            padding-top: 15px;
            font-size: 10px;
            color: #4b5563;
        }
        .payment-info-title {
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td>
                <div class="invoice-title">Faktura VAT</div>
                <div class="invoice-number">Nr {{ $invoice->number }}</div>
            </td>
            <td class="text-right">
                <table style="border-collapse: collapse; float: right;">
                    <tr>
                        <td class="meta-label">Data wystawienia:</td>
                        <td class="meta-value">{{ $invoice->issue_date->format('d.m.Y') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Data sprzedaży:</td>
                        <td class="meta-value">{{ $order->placed_at ? $order->placed_at->format('d.m.Y') : $invoice->issue_date->format('d.m.Y') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Termin płatności:</td>
                        <td class="meta-value">{{ $invoice->due_date->format('d.m.Y') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Parties Section -->
    <table class="parties-table">
        <tr>
            <td style="padding-right: 20px;">
                <div class="party-title">Sprzedawca</div>
                <div class="party-details">
                    <strong>{{ $seller['name'] }}</strong><br>
                    {!! nl2br(e($seller['address'])) !!}<br>
                    NIP: {{ $seller['nip'] }}
                </div>
            </td>
            <td style="padding-left: 20px;">
                <div class="party-title">Nabywca</div>
                <div class="party-details">
                    @if(filled($order->billing_company_name))
                        <strong>{{ $order->billing_company_name }}</strong><br>
                        NIP: {{ $order->billing_nip }}<br>
                    @else
                        <strong>{{ $order->customer_first_name }} {{ $order->customer_last_name }}</strong><br>
                    @endif
                    
                    @php
                        $billingAddress = $order->billing_address ?? [];
                        $street = $billingAddress['line_1'] ?? $billingAddress['line1'] ?? '';
                        $city = $billingAddress['city'] ?? '';
                        $postcode = $billingAddress['postal_code'] ?? $billingAddress['postcode'] ?? '';
                    @endphp
                    {{ $street }}<br>
                    {{ $postcode }} {{ $city }}<br>
                    Email: {{ $order->customer_email }}
                </div>
            </td>
        </tr>
    </table>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">Lp.</th>
                <th style="width: 40%;">Nazwa towaru / usługi</th>
                <th style="width: 8%;" class="text-right">Ilość</th>
                <th style="width: 12%;" class="text-right">Cena netto</th>
                <th style="width: 8%;" class="text-right">VAT</th>
                <th style="width: 13%;" class="text-right">Wartość netto</th>
                <th style="width: 14%;" class="text-right">Wartość brutto</th>
            </tr>
        </thead>
        <tbody>
            @php $index = 1; @endphp
            @foreach($items as $item)
                <tr>
                    <td>{{ $index++ }}</td>
                    <td>
                        {{ $item['name'] }}
                        @if($item['sku'])
                            <br><small style="color: #666;">SKU: {{ $item['sku'] }}</small>
                        @endif
                    </td>
                    <td class="text-right">{{ $item['quantity'] }}</td>
                    <td class="text-right">{{ number_format($item['price_net'] / 100, 2, ',', ' ') }}</td>
                    <td class="text-right">{{ $item['vat_rate'] === 99 ? 'zw' : $item['vat_rate'] . '%' }}</td>
                    <td class="text-right">{{ number_format($item['total_net'] / 100, 2, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($item['total_gross'] / 100, 2, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Summary Containers (VAT Summary Left, Totals Right) -->
    <table class="summary-container-table">
        <tr>
            <td style="width: 50%; padding-right: 20px;">
                <div style="font-weight: bold; margin-bottom: 8px; text-transform: uppercase; font-size: 10px;">Tabela podatku VAT</div>
                <table class="vat-summary-table">
                    <thead>
                        <tr>
                            <th>Stawka VAT</th>
                            <th class="text-right">Wartość netto</th>
                            <th class="text-right">Kwota VAT</th>
                            <th class="text-right">Wartość brutto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($vatSummary as $rate => $sum)
                            <tr>
                                <td>{{ $rate === 99 ? 'zw' : $rate . '%' }}</td>
                                <td class="text-right">{{ number_format($sum['net'] / 100, 2, ',', ' ') }}</td>
                                <td class="text-right">{{ number_format($sum['tax'] / 100, 2, ',', ' ') }}</td>
                                <td class="text-right">{{ number_format($sum['gross'] / 100, 2, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
            <td style="width: 50%; padding-left: 20px;">
                <table class="totals-table">
                    <tr>
                        <td style="font-weight: bold;">Suma netto:</td>
                        <td class="text-right">{{ number_format($totals['net'] / 100, 2, ',', ' ') }} {{ $order->currency }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Kwota VAT:</td>
                        <td class="text-right">{{ number_format($totals['tax'] / 100, 2, ',', ' ') }} {{ $order->currency }}</td>
                    </tr>
                    <tr class="total-due-row">
                        <td>Razem brutto:</td>
                        <td class="text-right">{{ number_format($totals['gross'] / 100, 2, ',', ' ') }} {{ $order->currency }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Payment & Info Section -->
    <div class="payment-info">
        <div class="payment-info-title">Informacje o płatności</div>
        Metoda płatności: <strong>{{ $order->payment_method_name ?? ($order->isCod() ? 'Za pobraniem' : 'Przelew bankowy') }}</strong><br>
        @if(filled($seller['bank_account']))
            Rachunek bankowy: <strong>{{ $seller['bank_account'] }}</strong><br>
        @endif
        Status płatności: <strong>{{ $order->payment_status === 'paid' ? 'Zapłacono' : 'Do zapłaty' }}</strong>
    </div>

</body>
</html>
