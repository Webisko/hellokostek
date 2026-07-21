<h1>Dziekujemy za zamowienie {{ $order->number }}</h1>

<p>Potwierdzamy przyjęcie zamówienia w naszym sklepie.</p>

<p>
    Klient: {{ $order->customer_first_name }} {{ $order->customer_last_name }}<br>
    E-mail: {{ $order->customer_email }}<br>
    Kwota: {{ number_format($order->total_amount / 100, 2, ',', ' ') }} {{ $order->currency }}
</p>

<p>Jesli zamowienie wymaga dalszych krokow, wyslemy osobne instrukcje.</p>