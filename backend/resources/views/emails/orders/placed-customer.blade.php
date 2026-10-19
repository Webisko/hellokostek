@php
    $paymentProvider = data_get($order->metadata, 'payment.provider');
    $deliveryPoint = data_get($order->metadata, 'delivery_point');
    $shippingAddress = $order->shipping_address ?? $order->billing_address ?? [];
    $storeSettings = app(\App\Support\StoreSettings::class);
    $sellerName = $storeSettings->invoiceSellerName() !== 'Firma testowa sp. z o.o.'
        ? $storeSettings->invoiceSellerName()
        : 'hellokostek Maciej Kosteczka';
    $sellerAddress = $storeSettings->invoiceSellerAddress() !== 'ul. Testowa 1, 00-001 Warszawa'
        ? $storeSettings->invoiceSellerAddress()
        : 'Rynek 33, 42-470 Siewierz';
    $bankAccount = $storeSettings->invoiceSellerBankAccount() ?: 'PL 74 1050 1227 1000 0090 8312 8604';
    $productNames = $order->items
        ->map(fn ($item) => is_array($item->name) ? ($item->name['pl'] ?? reset($item->name)) : $item->name)
        ->filter()
        ->implode(', ');
    $transferTitle = $productNames !== '' ? $productNames : ('Zamówienie ' . $order->number);
    $paymentUrl = $order->latestPaymentTransaction?->redirect_url;
@endphp
<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1a1a1a; line-height: 1.6; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #eee; border-radius: 16px;">
    <p style="font-family: monospace; font-size: 12px; text-transform: uppercase; letter-spacing: 0.1em; color: #E0115F; font-weight: bold; margin: 0 0 8px 0;">
        Potwierdzenie zamówienia • hellokostek
    </p>
    <h1 style="font-size: 24px; color: #111; margin: 0 0 16px 0;">
        Dziękujemy za zamówienie {{ $order->number }}
    </h1>

    <p style="margin: 0 0 16px 0;">
        Cześć {{ $order->customer_first_name }},<br>
        potwierdzamy przyjęcie Twojego zamówienia w Pracowni Artystycznej <strong>hellokostek</strong>.
    </p>

    <div style="background-color: #fafaf9; border: 1px solid #e7e5e4; border-radius: 12px; padding: 16px; margin-bottom: 20px;">
        <h3 style="font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; color: #57534e; margin: 0 0 12px 0;">
            Zamówione prace
        </h3>
        <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
            @foreach ($order->items as $item)
                <tr>
                    <td style="padding: 6px 0; border-bottom: 1px solid #f5f5f4;">
                        <strong>{{ is_array($item->name) ? ($item->name['pl'] ?? reset($item->name)) : $item->name }}</strong>
                        <span style="color: #78716c;">× {{ $item->quantity }}</span>
                    </td>
                    <td style="padding: 6px 0; border-bottom: 1px solid #f5f5f4; text-align: right; font-family: monospace; font-weight: bold;">
                        {{ number_format($item->total_amount / 100, 2, ',', ' ') }} {{ $order->currency }}
                    </td>
                </tr>
            @endforeach
            @if ($order->discount_amount > 0)
                <tr>
                    <td style="padding: 6px 0; color: #047857;">Rabat:</td>
                    <td style="padding: 6px 0; text-align: right; font-family: monospace; color: #047857;">
                        -{{ number_format($order->discount_amount / 100, 2, ',', ' ') }} {{ $order->currency }}
                    </td>
                </tr>
            @endif
            <tr>
                <td style="padding: 6px 0; color: #57534e;">
                    Dostawa ({{ $order->shipping_method_name ?? 'Kurier' }}):
                </td>
                <td style="padding: 6px 0; text-align: right; font-family: monospace;">
                    {{ $order->shipping_amount == 0 ? 'Bezpłatnie' : number_format($order->shipping_amount / 100, 2, ',', ' ') . ' ' . $order->currency }}
                </td>
            </tr>
            <tr>
                <td style="padding: 10px 0 0 0; font-weight: bold; font-size: 16px;">Razem:</td>
                <td style="padding: 10px 0 0 0; text-align: right; font-family: monospace; font-weight: bold; font-size: 16px; color: #E0115F;">
                    {{ number_format($order->total_amount / 100, 2, ',', ' ') }} {{ $order->currency }}
                </td>
            </tr>
        </table>
    </div>

    <div style="background-color: #fafaf9; border: 1px solid #e7e5e4; border-radius: 12px; padding: 16px; margin-bottom: 20px; font-size: 14px;">
        <h3 style="font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; color: #57534e; margin: 0 0 8px 0;">
            Dane dostawy
        </h3>
        <p style="margin: 0;">
            <strong>Odbiorca:</strong> {{ $order->customer_first_name }} {{ $order->customer_last_name }}<br>
            @if ($order->customer_phone)
                <strong>Telefon:</strong> {{ $order->customer_phone }}<br>
            @endif
            @if (!empty($deliveryPoint['id']))
                <strong>Punkt odbioru:</strong> {{ $deliveryPoint['name'] ?? $deliveryPoint['id'] }} ({{ $deliveryPoint['address'] ?? '' }} {{ $deliveryPoint['city'] ?? '' }})
            @elseif (!empty($shippingAddress['street']))
                <strong>Adres:</strong> {{ $shippingAddress['street'] ?? '' }}, {{ $shippingAddress['postal_code'] ?? '' }} {{ $shippingAddress['city'] ?? '' }}
            @endif
        </p>
    </div>

    @if (in_array($paymentProvider, ['stripe', 'przelewy24'], true) && $order->payment_status !== 'paid' && !empty($paymentUrl))
        <div style="background-color: #fff1f2; border: 1px solid #fecdd3; border-radius: 12px; padding: 18px; margin-bottom: 20px; font-size: 14px;">
            <h3 style="font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; color: #E0115F; margin: 0 0 8px 0;">
                Płatność online za zamówienie
            </h3>
            <p style="margin: 0 0 14px 0; color: #44403c;">
                Jeśli Twoje zamówienie zostało już opłacone – dziękujemy! W przypadku gdy płatność w bramce Stripe została przerwana lub chcesz opłacić zamówienie w innym momencie, <strong>nie musisz składać zamówienia ponownie</strong>. Wystarczy kliknąć poniższy przycisk, aby przejść bezpośrednio do płatności:
            </p>
            <p style="margin: 0; text-align: center;">
                <a href="{{ $paymentUrl }}" target="_blank" rel="noopener noreferrer" style="display: inline-block; background-color: #181716; color: #ffffff; font-weight: bold; text-decoration: none; padding: 12px 24px; border-radius: 10px; font-size: 14px;">
                    Opłać zamówienie online ({{ number_format($order->total_amount / 100, 2, ',', ' ') }} {{ $order->currency }}) →
                </a>
            </p>
        </div>
    @endif

    @if ($paymentProvider === 'bank_transfer')
        <div style="background-color: #fff1f2; border: 1px solid #fecdd3; border-radius: 12px; padding: 16px; margin-bottom: 20px; font-size: 14px;">
            <h3 style="font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; color: #E0115F; margin: 0 0 8px 0;">
                Płatność przelewem tradycyjnym
            </h3>
            <p style="margin: 0 0 8px 0;">
                Wybraną pracę rezerwujemy dla Ciebie na <strong>3 dni robocze</strong>. Prosimy o dokonanie przelewu na poniższe dane:
            </p>
            <p style="margin: 0; font-family: monospace; font-size: 13px;">
                <strong>Odbiorca:</strong> {{ $sellerName }}<br>
                <strong>Adres:</strong> {{ $sellerAddress }}<br>
                <strong>Konto firmowe IBAN:</strong> {{ $bankAccount }}<br>
                <strong>Tytuł przelewu:</strong> {{ $transferTitle }}<br>
                <strong>Kwota:</strong> {{ number_format($order->total_amount / 100, 2, ',', ' ') }} {{ $order->currency }}
            </p>
        </div>
    @endif

    <p style="font-size: 14px; color: #57534e; margin: 0 0 16px 0;">
        W razie jakichkolwiek pytań wystarczy odpowiedzieć na tę wiadomość lub napisać bezpośrednio na <a href="mailto:kontakt@hellokostek.pl" style="color: #E0115F;">kontakt@hellokostek.pl</a>.
    </p>

    <p style="font-size: 14px; margin: 0;">
        Pozdrawiam serdecznie,<br>
        <strong>Maciej Kosteczka • hellokostek</strong>
    </p>
</div>