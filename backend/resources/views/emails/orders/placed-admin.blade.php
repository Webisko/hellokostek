@php
    $paymentProvider = data_get($order->metadata, 'payment.provider');
    $deliveryPoint = data_get($order->metadata, 'delivery_point');
    $shippingAddress = $order->shipping_address ?? $order->billing_address ?? [];
@endphp
<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1a1a1a; line-height: 1.6; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #eee; border-radius: 16px;">
    <h1 style="font-size: 22px; color: #111; margin: 0 0 12px 0;">
        Nowe zamówienie {{ $order->number }}
    </h1>

    <p style="margin: 0 0 16px 0;">
        W sklepie <strong>hellokostek</strong> pojawiło się nowe zamówienie.
    </p>

    <p style="margin: 0 0 16px 0; font-size: 14px;">
        <strong>Klient:</strong> {{ $order->customer_first_name }} {{ $order->customer_last_name }}<br>
        <strong>E-mail:</strong> {{ $order->customer_email }}<br>
        <strong>Telefon:</strong> {{ $order->customer_phone ?? 'brak' }}<br>
        <strong>Metoda płatności:</strong> {{ $paymentProvider ?? 'brak' }} (status: {{ $order->payment_status }})<br>
        <strong>Dostawa:</strong> {{ $order->shipping_method_name ?? $order->shipping_method_code ?? 'brak' }}<br>
        @if (!empty($deliveryPoint['id']))
            <strong>Punkt odbioru:</strong> {{ $deliveryPoint['name'] ?? $deliveryPoint['id'] }} ({{ $deliveryPoint['address'] ?? '' }} {{ $deliveryPoint['city'] ?? '' }})<br>
        @elseif (!empty($shippingAddress['street']))
            <strong>Adres wysyłki:</strong> {{ $shippingAddress['street'] ?? '' }}, {{ $shippingAddress['postal_code'] ?? '' }} {{ $shippingAddress['city'] ?? '' }}<br>
        @endif
        <strong>Kwota łączna:</strong> {{ number_format($order->total_amount / 100, 2, ',', ' ') }} {{ $order->currency }}
    </p>

    <h3 style="font-size: 14px; text-transform: uppercase; color: #57534e; margin: 16px 0 8px 0;">Pozycje zamówienia:</h3>
    <ul style="font-size: 14px; margin: 0; padding-left: 20px;">
        @foreach ($order->items as $item)
            @php
                $rawName = is_array($item->name) ? ($item->name['pl'] ?? reset($item->name)) : (string) $item->name;
                $cleanName = str_replace(['(Reprodukcja (Wydruk))', '((Wydruk))', '(Wydruk (Reprodukcja))'], '(Wydruk)', $rawName);
            @endphp
            <li>
                <strong>{{ $cleanName }}</strong>
                ({{ $item->sku ?? 'brak SKU' }}) × {{ $item->quantity }} —
                {{ number_format($item->total_amount / 100, 2, ',', ' ') }} {{ $order->currency }}
            </li>
        @endforeach
    </ul>
</div>