<h1>Wróć do swojego koszyka!</h1>

<p>Witaj {{ $order->customer_first_name }},</p>

<p>Zauważyliśmy, że w Twoim koszyku pozostały wybrane produkty. Nie pozwól im zniknąć!</p>

@php
    $couponCode = data_get($order->metadata, 'recovery_coupon_code');
    $discountPercent = data_get($order->metadata, 'recovery_discount_percent');
    $recoveryLink = data_get($order->metadata, 'recovery_link') 
        ?? (config('app.url') . '/checkout/resume/' . $order->number);
@endphp

@if($couponCode)
    <div style="background-color: #f3f4f6; border-left: 4px solid #2563eb; padding: 15px; margin: 15px 0; border-radius: 4px;">
        <p style="margin: 0; font-weight: bold; color: #1f2937;">
            Specjalny kod rabatowy dla Ciebie: <span style="font-size: 16px; color: #2563eb; letter-spacing: 1px;">{{ $couponCode }}</span>
        </p>
        <p style="margin: 5px 0 0 0; font-size: 12px; color: #4b5563;">
            Użyj go podczas finalizacji zamówienia, aby otrzymać dodatkowe <strong>{{ $discountPercent }}%</strong> zniżki!
        </p>
    </div>
@endif

<h3>Lista produktów w koszyku:</h3>
<ul>
    @foreach ($order->items as $item)
        <li>{{ $item->name }} x {{ $item->quantity }}</li>
    @endforeach
</ul>

<p>
    <strong>Suma koszyka:</strong> {{ number_format($order->total_amount / 100, 2, ',', ' ') }} {{ $order->currency }}
</p>

<p>Kliknij w poniższy link, aby dokończyć zamówienie:</p>
<p>
    <a href="{{ $recoveryLink }}" style="background: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;">
        Kontynuuj zakupy
    </a>
</p>

<p>Pozdrawiamy,<br>Zespół Sklepu</p>
