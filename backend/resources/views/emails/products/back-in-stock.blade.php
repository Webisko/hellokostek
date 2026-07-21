<h1>Produkt powrócił do magazynu!</h1>

<p>Dzień dobry,</p>

<p>Mamy świetną wiadomość! Produkt, na który czekasz, jest już ponownie dostępny w sprzedaży:</p>

<h3>{{ is_array($product->name) ? ($product->name['pl'] ?? reset($product->name)) : $product->name }}</h3>

@if ($variant)
    @php
        $variantName = $variant->optionValues->pluck('value')->join(', ');
    @endphp
    <p>Wybrany wariant: <strong>{{ $variantName }}</strong></p>
    <p>SKU: <code>{{ $variant->sku ?? $product->sku }}</code></p>
@else
    <p>SKU: <code>{{ $product->sku }}</code></p>
@endif

<p>Cena: 
    <strong>
        @if ($variant)
            {{ number_format(($variant->sale_price_amount ?? $variant->regular_price_amount) / 100, 2, ',', ' ') }}
        @else
            {{ number_format(($product->sale_price_amount ?? $product->regular_price_amount) / 100, 2, ',', ' ') }}
        @endif
        PLN
    </strong>
</p>

<p>
    <a href="{{ config('shop.storefront.url', config('app.url')) }}/products/{{ $product->slug }}" style="display: inline-block; padding: 10px 20px; background-color: #111827; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold;">
        Kup teraz
    </a>
</p>

<p>Dziękujemy za zakupy w naszym sklepie!</p>
