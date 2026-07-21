<h1>Potwierdzenie przyjęcia zgłoszenia zwrotu {{ $orderReturn->return_number }}</h1>

<p>Witaj {{ $orderReturn->order->customer_first_name }},</p>
<p>Otrzymaliśmy Twoje oświadczenie o odstąpieniu od umowy (zwrocie) o numerze <strong>{{ $orderReturn->return_number }}</strong> złożone w dniu {{ $orderReturn->created_at->toIso8601String() }}.</p>

<p><strong>Szczegóły zwracanego zamówienia:</strong><br>
Zamówienie: {{ $orderReturn->order->number }}<br>
E-mail: {{ $orderReturn->order->customer_email }}</p>

<h3>Zwracane produkty:</h3>
<ul>
    @foreach ($orderReturn->items as $item)
        <li>{{ $item->orderItem?->name ?? 'Produkt' }} x {{ $item->quantity }}</li>
    @endforeach
</ul>

<p>Nasz zespół zweryfikuje zgłoszenie i prześle dalsze instrukcje dotyczące odesłania towaru oraz zwrotu środków.</p>
