<h1>Nowe zamowienie {{ $order->number }}</h1>

<p>W systemie pojawilo sie nowe zamowienie wymagajace obslugi.</p>

<p>
    Klient: {{ $order->customer_first_name }} {{ $order->customer_last_name }}<br>
    E-mail: {{ $order->customer_email }}<br>
    Kwota: {{ number_format($order->total_amount / 100, 2, ',', ' ') }} {{ $order->currency }}<br>
    Dostawa: {{ $order->shipping_method_name ?? 'brak' }}
</p>