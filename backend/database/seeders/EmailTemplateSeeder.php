<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'order_placed_customer',
                'name' => 'Potwierdzenie zamówienia (Klient)',
                'subject' => 'Potwierdzenie zamówienia {order_number}',
                'body_html' => '<h1>Dziękujemy za zamówienie {order_number}</h1>
<p>Potwierdzamy przyjęcie zamówienia w naszym sklepie.</p>
<p>
    <strong>Dane klienta:</strong><br>
    Klient: {customer_first_name} {customer_last_name}<br>
    E-mail: {customer_email}<br>
    Kwota: {total_amount}
</p>
<p><strong>Zamówione produkty:</strong></p>
{items_list}
<p>Jeśli zamówienie wymaga dalszych kroków, wyślemy osobne instrukcje.</p>',
                'placeholders' => [
                    'order_number' => 'Numer zamówienia (np. ORD-20260613-ABCDEF)',
                    'customer_first_name' => 'Imię klienta',
                    'customer_last_name' => 'Nazwisko klienta',
                    'customer_email' => 'Adres e-mail klienta',
                    'total_amount' => 'Łączna kwota zamówienia wraz z walutą',
                    'items_list' => 'Lista produktów w zamówieniu (format HTML)',
                ],
            ],
            [
                'key' => 'order_placed_admin',
                'name' => 'Nowe zamówienie (Administrator)',
                'subject' => 'Nowe zamówienie {order_number} w sklepie',
                'body_html' => '<h1>Nowe zamówienie {order_number}</h1>
<p>W systemie pojawiło się nowe zamówienie wymagające obsługi.</p>
<p>
    <strong>Szczegóły:</strong><br>
    Klient: {customer_first_name} {customer_last_name}<br>
    E-mail: {customer_email}<br>
    Kwota: {total_amount}
</p>
<p><strong>Zamówione produkty:</strong></p>
{items_list}',
                'placeholders' => [
                    'order_number' => 'Numer zamówienia (np. ORD-20260613-ABCDEF)',
                    'customer_first_name' => 'Imię klienta',
                    'customer_last_name' => 'Nazwisko klienta',
                    'customer_email' => 'Adres e-mail klienta',
                    'total_amount' => 'Łączna kwota zamówienia wraz z walutą',
                    'items_list' => 'Lista produktów w zamówieniu (format HTML)',
                ],
            ],
            [
                'key' => 'digital_delivery',
                'name' => 'Dostawa produktów cyfrowych',
                'subject' => 'Dostęp cyfrowy do zamówienia {order_number}',
                'body_html' => '<h1>Dostęp cyfrowy do zamówienia {order_number}</h1>
<p>Twoje zamówienie zawiera produkty cyfrowe.</p>
<p>Przygotuj dla klienta lub automatycznie wyślij link do pobrania zgodnie z dalszą konfiguracją integracji.</p>
<p><strong>Produkty z zamówienia:</strong></p>
{items_list}',
                'placeholders' => [
                    'order_number' => 'Numer zamówienia (np. ORD-20260613-ABCDEF)',
                    'customer_first_name' => 'Imię klienta',
                    'customer_last_name' => 'Nazwisko klienta',
                    'customer_email' => 'Adres e-mail klienta',
                    'total_amount' => 'Łączna kwota zamówienia wraz z walutą',
                    'items_list' => 'Lista produktów w zamówieniu (format HTML)',
                ],
            ],
            [
                'key' => 'service_followup',
                'name' => 'Instrukcje do zamówionej usługi',
                'subject' => 'Instrukcje do zamówienia usługowego {order_number}',
                'body_html' => '<h1>Instrukcje do zamówienia usługowego {order_number}</h1>
<p>Twoje zamówienie zawiera usługę wymagającą dalszej obsługi.</p>
<p>Wkrótce prześlemy instrukcje startowe i dalsze kroki realizacji.</p>
<p><strong>Produkty z zamówienia:</strong></p>
{items_list}',
                'placeholders' => [
                    'order_number' => 'Numer zamówienia (np. ORD-20260613-ABCDEF)',
                    'customer_first_name' => 'Imię klienta',
                    'customer_last_name' => 'Nazwisko klienta',
                    'customer_email' => 'Adres e-mail klienta',
                    'total_amount' => 'Łączna kwota zamówienia wraz z walutą',
                    'items_list' => 'Lista produktów w zamówieniu (format HTML)',
                ],
            ],
            [
                'key' => 'abandoned_cart',
                'name' => 'Porzucony koszyk (Przypomnienie)',
                'subject' => 'Wróć do swojego koszyka i dokończ zakupy!',
                'body_html' => '<h1>Wróć do swojego koszyka!</h1>
<p>Witaj {customer_first_name},</p>
<p>Zauważyliśmy, że w Twoim koszyku pozostały wybrane produkty. Nie pozwól im zniknąć!</p>
<h3>Lista produktów w koszyku:</h3>
{items_list}
<p><strong>Suma koszyka:</strong> {total_amount}</p>
<p>Kliknij w poniższy link, aby dokończyć zamówienie:</p>
<p><a href="/checkout/resume/{order_number}" style="background: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">Kontynuuj zakupy</a></p>',
                'placeholders' => [
                    'order_number' => 'Numer zamówienia (np. ORD-20260613-ABCDEF)',
                    'customer_first_name' => 'Imię klienta',
                    'customer_last_name' => 'Nazwisko klienta',
                    'customer_email' => 'Adres e-mail klienta',
                    'total_amount' => 'Łączna kwota zamówienia wraz z walutą',
                    'items_list' => 'Lista produktów w zamówieniu (format HTML)',
                ],
            ],
            [
                'key' => 'order_return_confirmation',
                'name' => 'Potwierdzenie zgłoszenia zwrotu (Klient)',
                'subject' => 'Potwierdzenie przyjęcia zwrotu {return_number} dla zamówienia {order_number}',
                'body_html' => '<h1>Potwierdzenie przyjęcia zgłoszenia zwrotu</h1>
<p>Witaj {customer_first_name},</p>
<p>Otrzymaliśmy Twoje oświadczenie o odstąpieniu od umowy (zwrocie) o numerze <strong>{return_number}</strong> złożone w dniu {return_date}.</p>
<p><strong>Szczegóły zwracanego zamówienia:</strong><br>
Zamówienie: {order_number}<br>
E-mail: {customer_email}</p>
<h3>Zwracane produkty:</h3>
{returned_items_list}
<p>Nasz zespół zweryfikuje zgłoszenie i prześle dalsze instrukcje dotyczące odesłania towaru oraz zwrotu środków.</p>',
                'placeholders' => [
                    'order_number' => 'Numer zamówienia (np. ORD-20260613-ABCDEF)',
                    'return_number' => 'Numer zwrotu (np. RET-20260621-XYZ123)',
                    'return_date' => 'Data i godzina złożenia zwrotu',
                    'customer_first_name' => 'Imię klienta',
                    'customer_last_name' => 'Nazwisko klienta',
                    'customer_email' => 'Adres e-mail klienta',
                    'returned_items_list' => 'Lista zwracanych produktów w zgłoszeniu (format HTML)',
                ],
            ],
        ];

        foreach ($templates as $templateData) {
            EmailTemplate::query()->updateOrCreate(
                ['key' => $templateData['key']],
                [
                    'name' => $templateData['name'],
                    'subject' => $templateData['subject'],
                    'body_html' => $templateData['body_html'],
                    'placeholders' => $templateData['placeholders'],
                ]
            );
        }
    }
}
