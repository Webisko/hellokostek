<?php

namespace Database\Seeders;

use App\Domain\Commerce\Enums\ProductType;
use App\Models\GalleryArtwork;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HelloKostekSeeder extends Seeder
{
    public function run(): void
    {
        // Czyszczenie starych angielskich kategorii i produktów
        ProductCategory::query()->whereIn('slug', ['watercolor', 'drawing'])->delete();
        Product::query()->where('slug', 'like', 'drawing-%')->orWhere('slug', 'like', 'watercolor-%')->delete();

        // 0. ADMIN USER
        $admin = \App\Models\User::query()->where('email', 'admin@hellokostek.pl')->firstOrNew();
        $admin->forceFill([
            'name' => 'Hello Kostek Admin',
            'email' => 'admin@hellokostek.pl',
            'password' => \Illuminate\Support\Facades\Hash::make('Admin1234!'),
            'is_admin' => true,
            'role' => \App\Domain\Commerce\Enums\UserRole::Admin,
        ])->save();

        $adminFallback = \App\Models\User::query()->where('email', 'admin@genericshop.local')->firstOrNew();
        $adminFallback->forceFill([
            'name' => 'Shop Admin',
            'email' => 'admin@genericshop.local',
            'password' => \Illuminate\Support\Facades\Hash::make('Admin1234!'),
            'is_admin' => true,
            'role' => \App\Domain\Commerce\Enums\UserRole::Admin,
        ])->save();

        // 0.1 STORE SETTINGS EMAILS
        $settings = \App\Models\StoreSetting::query()->first();
        if ($settings) {
            $settings->forceFill([
                'admin_notification_email' => 'kontakt@hellokostek.pl',
                'order_notification_email' => 'kontakt@hellokostek.pl',
                'mail_from_address' => 'kontakt@hellokostek.pl',
                'mail_from_name' => 'Hello Kostek',
            ])->save();
        }

        // 1. KATEGORIE (Olej, Akryl, Akwarela, Rysunek)
        $categoriesData = [
            [
                'slug' => 'olej',
                'name' => 'Olej',
                'description' => 'Tradycyjne malarstwo olejne na płótnie bawełnianym.',
                'sort_order' => 10,
            ],
            [
                'slug' => 'akryl',
                'name' => 'Akryl',
                'description' => 'Nowoczesne i wyraziste malarstwo akrylowe na płótnie.',
                'sort_order' => 20,
            ],
            [
                'slug' => 'akwarela',
                'name' => 'Akwarela',
                'description' => 'Subtelne i zmysłowe akwarele na papierze bawełnianym.',
                'sort_order' => 30,
            ],
            [
                'slug' => 'rysunek',
                'name' => 'Rysunek',
                'description' => 'Precyzyjne i dynamiczne rysunki wykonane ołówkiem.',
                'sort_order' => 40,
            ],
        ];

        $categories = [];
        foreach ($categoriesData as $cat) {
            $categories[$cat['slug']] = ProductCategory::query()->updateOrCreate(
                ['slug' => $cat['slug']],
                [
                    'name' => $cat['name'],
                    'description' => $cat['description'],
                    'seo_title' => $cat['name'] . ' | Hello Kostek',
                    'seo_description' => $cat['description'],
                    'sort_order' => $cat['sort_order'],
                    'is_active' => true,
                ]
            );
        }

        // 2. PRODUKTY (na podstawie src/data.ts)
        $productsData = [
            // --- WATERCOLORS ---
            [
                'id' => 'akwarela-2-2022',
                'title' => 'Obiekt 2',
                'year' => '2022',
                'category' => 'akwarela',
                'originalPrice' => 300,
                'printPrice' => 30,
                'isOriginalAvailable' => true,
                'imageUrl' => 'products/Wiecej-o-obiekcie-2-2022-edited-768x768.webp',
                'description' => 'Rogue planet'
            ],
            [
                'id' => 'akwarela-7-2022',
                'title' => 'Obiekt 7',
                'year' => '2022',
                'category' => 'akwarela',
                'originalPrice' => 300,
                'printPrice' => 30,
                'isOriginalAvailable' => true,
                'imageUrl' => 'products/Wiecej-o-obiekcie-7-2022-scaled.webp',
                'description' => 'Rogue planet'
            ],
            [
                'id' => 'akwarela-8-2022',
                'title' => 'Obiekt 8',
                'year' => '2022',
                'category' => 'akwarela',
                'originalPrice' => 300,
                'printPrice' => 30,
                'isOriginalAvailable' => true,
                'imageUrl' => 'products/Wiecej-o-obiekcie-8.webp',
                'description' => 'Rogue planet'
            ],
            [
                'id' => 'akwarela-9-2022',
                'title' => 'Obiekt 9',
                'year' => '2022',
                'category' => 'akwarela',
                'originalPrice' => 300,
                'printPrice' => 30,
                'isOriginalAvailable' => true,
                'imageUrl' => 'products/Wiecej-o-obiekcie-9-2022-scaled.webp',
                'description' => 'Rogue planet'
            ],
            [
                'id' => 'akwarela-13-2022',
                'title' => 'Obiekt 13 (Sygnowany)',
                'year' => '2022',
                'category' => 'akwarela',
                'originalPrice' => 300,
                'printPrice' => 30,
                'isOriginalAvailable' => false,
                'imageUrl' => 'products/Wiecej-o-obiekcie-13-2022-scaled.webp',
                'description' => 'Rogue planet'
            ],
            // --- DRAWINGS ---
            [
                'id' => 'postaci-w-biegu-2024',
                'title' => 'Postaci w biegu',
                'year' => '2024',
                'category' => 'rysunek',
                'originalPrice' => 200,
                'printPrice' => 20,
                'isOriginalAvailable' => true,
                'imageUrl' => 'products/2086036_1b.webp',
                'description' => 'Ekspresyjny rysunek ołówkiem rejestrujący dynamikę ludzkiego ciała, grę cieni i ruch. Nowoczesna kreska, która wnosi do wnętrza powiew energii.'
            ],
            [
                'id' => 'codziennosc-2022',
                'title' => 'Codzienność',
                'year' => '2022',
                'category' => 'rysunek',
                'originalPrice' => 200,
                'printPrice' => 20,
                'isOriginalAvailable' => true,
                'imageUrl' => 'products/Codziennosc-2022.webp',
                'description' => 'Kameralne studium chłodnej, melancholijnej codzienności. Wyjątkowo intymna kompozycja, skłaniająca do odnalezienia piękna w najprostszych, ulotnych momentach.'
            ],
            [
                'id' => 'nie-wytrzymam-2022',
                'title' => 'Nie wytrzymam',
                'year' => '2022',
                'category' => 'rysunek',
                'originalPrice' => 200,
                'printPrice' => 20,
                'isOriginalAvailable' => true,
                'imageUrl' => 'products/Nie-wytrzymam-2022.webp',
                'description' => 'Poruszające personifikowanie nagromadzonych emocji za pomocą wyrazistej kreski graficznej. Głębokie kontrasty ucieleśniają wewnętrzną odporność i siłę.'
            ],
            [
                'id' => 'lek-2022',
                'title' => 'Lęk',
                'year' => '2022',
                'category' => 'rysunek',
                'originalPrice' => 200,
                'printPrice' => 20,
                'isOriginalAvailable' => true,
                'imageUrl' => 'products/Lek-2022-1.webp',
                'description' => 'Delikatny, pełen czułości i zniuansowania rysunek poruszający intymny temat lęku jako części ludzkiego doświadczenia. Uniwersalna, piękna praca kolekcjonerska.'
            ],
            [
                'id' => 'obiekt-wyodrebniony-10-2022',
                'title' => 'Obiekt wyodrębniony #10',
                'year' => '2022',
                'category' => 'rysunek',
                'originalPrice' => 200,
                'printPrice' => 20,
                'isOriginalAvailable' => true,
                'imageUrl' => 'products/Obiekt-wyodrebniony-10-2022.webp',
                'description' => 'Minimalistyczny, surowy w formie rysunek ołówkiem skupiający się na pojedynczej bryle i cieniu. Wybitna lekcja czystej proporcji i przestrzeni.'
            ],
            [
                'id' => 'to-dziwne-uczucie-2022',
                'title' => 'To dziwne uczucie',
                'year' => '2022',
                'category' => 'rysunek',
                'originalPrice' => 200,
                'printPrice' => 20,
                'isOriginalAvailable' => true,
                'imageUrl' => 'products/To-dziwne-uczucie-2022.webp',
                'description' => 'Złożony i zmysłowy rysunek, który dotyka nieuchwytnych stanów emocjonalnych. Każde pociągnięcie ołówka buduje głęboką strukturę psychologiczną postaci.'
            ],
            [
                'id' => 'ucieczka-2022',
                'title' => 'Ucieczka',
                'year' => '2022',
                'category' => 'rysunek',
                'originalPrice' => 200,
                'printPrice' => 20,
                'isOriginalAvailable' => true,
                'imageUrl' => 'products/Ucieczka-2022.webp',
                'description' => 'Dynamiczny, metaforyczny rysunek ukazujący pragnienie wolności i przestrzeni. Niezwykła lekkość kompozycji idealnie ożywi minimalistyczne wnętrze.'
            ],
            [
                'id' => 'strach-2022',
                'title' => 'Strach',
                'year' => '2022',
                'category' => 'rysunek',
                'originalPrice' => 200,
                'printPrice' => 20,
                'isOriginalAvailable' => true,
                'imageUrl' => 'products/Strach-2022.webp',
                'description' => 'Sztuka zmagań sformułowana w nienagannym rzemiośle ołówka. Oparta na delikatnych cieniach praca, która potrafi oczarować głębią wyrazu.'
            ]
        ];

        foreach ($productsData as $index => $prod) {
            $product = Product::query()->updateOrCreate(
                ['slug' => $prod['id']],
                [
                    'type' => ProductType::Physical,
                    'sku' => 'HK-' . strtoupper(str_replace('-', '', $prod['id'])),
                    'name' => $prod['title'],
                    'short_description' => Str::limit($prod['description'], 120),
                    'description' => $prod['description'],
                    'featured_image_path' => $prod['imageUrl'],
                    'currency' => 'PLN',
                    'regular_price_amount' => $prod['printPrice'] * 100, // Domyślna najniższa cena (wydruk)
                    'sale_price_amount' => null,
                    'stock_quantity' => 999,
                    'manages_stock' => false,
                    'is_active' => true,
                    'is_visible' => true,
                    'is_purchasable' => true,
                    'seo_title' => $prod['title'] . ' - Hello Kostek',
                    'seo_description' => Str::limit($prod['description'], 150),
                    'published_at' => now()->subDays(10),
                    'metadata' => [
                        'year' => $prod['year'],
                        'original_available' => $prod['isOriginalAvailable']
                    ]
                ]
            );

            // Powiązanie z kategorią
            if (isset($categories[$prod['category']])) {
                $product->categories()->sync([$categories[$prod['category']]->id]);
            }

            // Opcje wariantów
            $option = ProductOption::query()->updateOrCreate(
                ['product_id' => $product->id, 'name' => 'Wariant'],
                []
            );

            // Wartości opcji
            $valOriginal = ProductOptionValue::query()->updateOrCreate(
                ['product_option_id' => $option->id, 'value' => 'Oryginał'],
                []
            );

            $valPrint = ProductOptionValue::query()->updateOrCreate(
                ['product_option_id' => $option->id, 'value' => 'Wydruk'],
                []
            );

            // 1. Wariant: Oryginał
            $variantOriginal = ProductVariant::query()->updateOrCreate(
                ['product_id' => $product->id, 'sku' => $product->sku . '-OR'],
                [
                    'regular_price_amount' => $prod['originalPrice'] * 100,
                    'stock_quantity' => $prod['isOriginalAvailable'] ? 1 : 0,
                    'manages_stock' => true,
                    'is_active' => true,
                    'vat_rate' => 23,
                ]
            );
            $variantOriginal->optionValues()->sync([$valOriginal->id]);

            // 2. Wariant: Wydruk
            $variantPrint = ProductVariant::query()->updateOrCreate(
                ['product_id' => $product->id, 'sku' => $product->sku . '-PR'],
                [
                    'regular_price_amount' => $prod['printPrice'] * 100,
                    'stock_quantity' => 999,
                    'manages_stock' => false,
                    'is_active' => true,
                    'vat_rate' => 23,
                ]
            );
            $variantPrint->optionValues()->sync([$valPrint->id]);
        }

        // 3. OPINIE KLIENCKIE (ProductReview dla strony głównej)
        $testimonials = [
            [
                'stars' => 5,
                'text' => '„Hej! Mama jest zachwycona! Co się patrzy to się uśmiecha. Roześmiała się jak zobaczyła i powiedziała, że idealnie odwzorowane!”',
                'author' => 'Kasia',
                'meta' => 'Portret z trzema psami',
                'emoji' => '🐶',
            ],
            [
                'stars' => 5,
                'text' => '„Gdy wręczyłem prezent to złożyłem życzenia od ciebie Tata bardzo Cię pozdrawia i jest 🙂 wzruszony prezentem. Bardzo lubi Twoją twórczość Jesteś gość ! Tak powiedział. 🙂”',
                'author' => 'Kamil',
                'meta' => 'Portret taty',
                'emoji' => '🙂',
            ],
            [
                'stars' => 5,
                'text' => '„Kubeczki zamówione dla siostrzeńców rok temu nadal świetnie się myją, wzory nie znikają, a siostrzeńcy uwielbiają spersonalizowane kubki ze swoimi ulubionymi bohaterami Dodatkowo, ❤ wszystkie wytwory szydełkowe jakie wychodzą spod rąk Maćka są zawsze świetnej jakości, solidnie wykonane i przede wszystkim przytulaśne. Obrazy najchętniej powiesiłabym u siebie na ścianie. Generalnie polecam tego Pana, to bardzo solidna firma!”',
                'author' => 'Wiola',
                'meta' => 'O różnych produktach hellokostek',
                'emoji' => '❤️',
            ],
            [
                'stars' => 5,
                'text' => '„Hej, cieszę się, że zamówiłam u Ciebie ten portret, wszedł bardzo ładnie. I sprawił dużo radości rodzinie, do której trafił. Miałam Ci przysłać zdjęcie, jak wygląda oprawiony, ale jednak zrezygnowałam z oprawy - dobrze się prezentuje bez ramy.”',
                'author' => 'Maria',
                'meta' => 'Portret dzieci',
                'emoji' => '🖼️',
            ],
            [
                'stars' => 5,
                'text' => '„Super jest Przepięknie . 🥰 😍😍”',
                'author' => 'Lucia',
                'meta' => 'Portret córek',
                'emoji' => '🥰',
            ],
            [
                'stars' => 5,
                'text' => '„Jaki Ty zdolny jesteś!!!!”',
                'author' => 'Dorota',
                'meta' => 'Opinia ogólna',
                'emoji' => '✨',
            ],
            [
                'stars' => 5,
                'text' => '„cześć, bardzo podobał mi się rysunek. Chętnie bym kupił dwa obrazy. Masz talent! Pozdrawiam. ”',
                'author' => 'Krzysiek',
                'meta' => 'O rysunkach',
                'emoji' => '🎨',
            ],
        ];

        foreach ($testimonials as $t) {
            \App\Models\ProductReview::query()->updateOrCreate(
                ['customer_name' => $t['author'], 'comment' => $t['text']],
                [
                    'customer_email' => strtolower(Str::slug($t['author'])) . '@klient.hellokostek.pl',
                    'rating' => $t['stars'],
                    'meta' => $t['meta'],
                    'emoji' => $t['emoji'],
                    'status' => 'publiczny',
                    'is_approved' => true,
                    'is_verified_purchase' => true,
                ]
            );
        }

        // 5. STRONY TREŚCI (ContentPage - Regulamin i Polityka Prywatności)
        \App\Models\ContentPage::query()->updateOrCreate(
            ['slug' => 'regulamin'],
            [
                'title' => 'Regulamin Sklepu',
                'excerpt' => 'Zasady korzystania ze sklepu Hello Kostek, składania zamówień oraz prawa konsumenta.',
                'template' => 'legal',
                'seo_title' => 'Regulamin Sklepu | Hello Kostek',
                'seo_description' => 'Oficjalny regulamin sklepu internetowego Hello Kostek. Zasady sprzedaży obrazów i dzieł sztuki.',
                'is_active' => true,
                'published_at' => now(),
                'metadata' => [
                    'sections' => [
                        [
                            'label' => '§ 1. Postanowienia ogólne',
                            'id' => 'postanowienia-ogolne',
                            'content' => '<p>1. Sklep internetowy działający pod adresem <code>https://hellokostek.pl/</code> prowadzony jest przez: <strong>hellokostek Maciej Kosteczka</strong> z siedzibą w: <strong>Rynek 33, 42-470 Siewierz, woj. śląskie, Polska</strong>, NIP: <strong>6252363656</strong>, REGON: <strong>527158196</strong>, wpisany do Centralnej Ewidencji i Informacji o Działalności Gospodarczej (CEIDG), zwany dalej <strong>„Sprzedawcą”</strong>.</p><p>2. Dane kontaktowe ze Sprzedawcą: adres e-mail: <strong>kontakt@hellokostek.pl</strong>, telefon: <strong>662 707 153</strong>.</p><p>3. Niniejszy Regulamin określa zasady korzystania ze Sklepu, składania zamówień na gotowe prace malarskie i rysunkowe, zasady zlecania i realizacji prac malarskich na indywidualne zamówienie, sposoby płatności, dostawy oraz procedury reklamacji i zwrotów.</p>'
                        ],
                        [
                            'label' => '§ 2. Definicje przedmiotów',
                            'id' => 'definicje',
                            'content' => '<p>W ramach Sklepu rozróżnia się następujące rodzaje asortymentu:</p><ul><li><strong>Oryginał (Produkt Gotowy)</strong> – unikatowe, fizyczne dzieło malarskie lub rysunkowe wykonane własnoręcznie przez artystę, dostępne w pojedynczym egzemplarzu i opatrzone ręcznym certyfikatem autentyczności.</li><li><strong>Wydruk Kolekcjonerski (Reprodukcja)</strong> – sygnowana reprodukcja autorskiej pracy wykonana na wysokogatunkowym papierze archiwalnym.</li><li><strong>Produkt na Zamówienie (Dzieło Spersonalizowane)</strong> – praca malarska (np. portret olejny ze zdjęcia) realizowana przez artystę od podstaw na indywidualne zlecenie Klienta, według jego specyfikacji (określony temat, format, kształt podobrazia, nadesłane zdjęcia referencyjne).</li></ul>'
                        ],
                        [
                            'label' => '§ 3. Składanie zamówień',
                            'id' => 'skladanie-zamowien',
                            'content' => '<p>1. Zamówienia na Produkty Gotowe oraz Wydruki Kolekcjonerskie można składać całodobowo poprzez interaktywny formularz zamówienia w Sklepie internetowym bez konieczności zakładania konta.</p><p>2. Złożenie zamówienia następuje po wybraniu wariantu pracy, uzupełnieniu danych adresowych i kontaktowych, zaakceptowaniu Regulaminu oraz kliknięciu przycisku „Zamawiam i płacę” lub „Zamawiam z obowiązkiem zapłaty”.</p><p>3. Zamówienia na Dzieła Spersonalizowane (portrety ze zdjęcia) przyjmowane są drogą indywidualnej wyceny przez formularz zapytania lub pocztę elektroniczną na adres <code>kontakt@hellokostek.pl</code>.</p>'
                        ],
                        [
                            'label' => '§ 4. Ceny i płatności',
                            'id' => 'ceny-i-platnosci',
                            'content' => '<p>1. Wszystkie ceny prezentowane w Sklepie są cenami brutto wyrażonymi w złotych polskich (PLN). Do ceny zamówienia doliczany jest koszt dostawy wskazany wyraźnie w podsumowaniu przed złożeniem zamówienia.</p><p>2. Sklep udostępnia następujące bezpieczne metody płatności:</p><ul><li><strong>Płatności elektroniczne Stripe Checkout:</strong> BLIK, karty płatnicze (Visa, Mastercard), Apple Pay, Google Pay, Klarna oraz Revolut Pay.</li><li><strong>Przelew tradycyjny:</strong> wpłata bezpośrednio na rachunek bankowy Sprzedawcy (rezerwacja wybranej pracy obowiązuje przez 3 dni robocze).</li></ul>'
                        ],
                        [
                            'label' => '§ 5. Dostawa i wysyłka',
                            'id' => 'dostawa',
                            'content' => '<p>1. Wysyłka zamówień realizowana jest na terytorium Rzeczypospolitej Polskiej za pośrednictwem firmy kurierskiej, automatów Paczkomat® InPost oraz punktów ORLEN Paczka.</p><p>2. Dla oryginalnych dzieł malarskich ubezpieczona przesyłka kurierska jest bezpłatna. Dla wydruków kolekcjonerskich koszt dostawy wynosi: ORLEN Paczka – 10 zł, Paczkomat InPost – 15 zł, Kurier – 20 zł (darmowa dostawa dla zamówień od 200 zł).</p><p>3. Czas przygotowania i nadania przesyłki wynosi zazwyczaj od 2 do 5 dni roboczych od momentu zaksięgowania płatności.</p>'
                        ],
                        [
                            'label' => '§ 6. Prawo do zwrotu',
                            'id' => 'zwroty-i-odstapienie',
                            'content' => '<p>1. Zgodnie z ustawą z dnia 30 maja 2014 r. o prawach konsumenta, Konsument (oraz Przedsiębiorca na prawach konsumenta) ma prawo odstąpić od umowy sprzedaży Produktu Gotowego lub Wydruku Kolekcjonerskiego w terminie <strong>14 dni</strong> od dnia objęcia rzeczy w posiadanie, bez podawania przyczyny.</p><p>2. Aby skorzystać z prawa odstąpienia od umowy, wystarczy wysłać oświadczenie drogą mailową na adres <code>kontakt@hellokostek.pl</code> przed upływem 14-dniowego terminu, a następnie odesłać bezpiecznie zapakowaną pracę na adres siedziby Sprzedawcy (Rynek 33, 42-470 Siewierz). Zwrot płatności następuje niezwłocznie, nie później niż w ciągu 14 dni.</p><p>3. <strong>Wyłączenie prawa odstąpienia (Dzieła Spersonalizowane):</strong> Zgodnie z art. 38 ust. 1 pkt 3 ustawy o prawach konsumenta, prawo odstąpienia od umowy zawartej na odległość <strong>nie przysługuje</strong> w odniesieniu do umów, w których przedmiotem świadczenia jest rzecz nieprefabrykowana, wyprodukowana według specyfikacji Konsumenta lub służąca zaspokojeniu jego zindywidualizowanych potrzeb (dotyczy to w szczególności portretów i obrazów malowanych na indywidualne zamówienie ze zdjęcia).</p>'
                        ],
                        [
                            'label' => '§ 7. Reklamacje',
                            'id' => 'reklamacje',
                            'content' => '<p>1. Sprzedawca ma obowiązek dostarczyć dzieło wolne od wad fizycznych i zgodne z zawartą umową.</p><p>2. Reklamacje z tytułu braku zgodności towaru z umową można składać drogą elektroniczną na adres e-mail: <strong>kontakt@hellokostek.pl</strong> lub pisemnie na adres siedziby Sprzedawcy.</p><p>3. Sprzedawca rozpatruje reklamację w terminie 14 dni kalendarzowych od dnia jej otrzymania.</p>'
                        ],
                        [
                            'label' => '§ 8. Prawa autorskie',
                            'id' => 'prawa-autorskie',
                            'content' => '<p>1. Wszystkie dzieła malarskie i rysunkowe sprzedawane za pośrednictwem Sklepu stanowią utwory w rozumieniu ustawy z dnia 4 lutego 1994 r. o prawie autorskim i prawach pokrewnych.</p><p>2. Zakup fizycznego obrazu (oryginału lub reprodukcji) przenosi na Kupującego własność nabytego egzemplarza dzieła, ale nie obejmuje przeniesienia autorskich praw majątkowych do jego komercyjnego powielania, druku ani rozpowszechniania w celach zarobkowych bez pisemnej zgody autora.</p>'
                        ],
                        [
                            'label' => '§ 9. Rozpatrywanie sporów',
                            'id' => 'pozasadowe-rozwiazywanie-sporow',
                            'content' => '<p>Konsument ma możliwość skorzystania z pozasądowych sposobów rozpatrywania reklamacji i dochodzenia roszczeń, m.in. poprzez zwrócenie się do stałego polubownego sądu konsumenckiego, wojewódzkiego inspektora Inspekcji Handlowej lub miejskiego (powiatowego) rzecznika konsumentów. Szczegółowe informacje dostępne są na stronie Urzędu Ochrony Konkurencji i Konsumentów (<code>uokik.gov.pl</code>).</p>'
                        ],
                        [
                            'label' => '§ 10. Postanowienia końcowe',
                            'id' => 'postanowienia-koncowe',
                            'content' => '<p>1. W sprawach nieuregulowanych niniejszym Regulaminem mają zastosowanie powszechnie obowiązujące przepisy prawa polskiego, w szczególności Kodeksu cywilnego oraz ustawy o prawach konsumenta.</p><p>2. Żadne z postanowień niniejszego Regulaminu nie ma na celu ograniczenia ani wyłączenia praw przysługujących Konsumentowi na mocy bezwzględnie obowiązujących przepisów prawa.</p>'
                        ]
                    ]
                ]
            ]
        );

        \App\Models\ContentPage::query()->updateOrCreate(
            ['slug' => 'polityka-prywatnosci'],
            [
                'title' => 'Polityka Prywatności i Cookies',
                'excerpt' => 'Zasady przetwarzania danych osobowych (RODO) oraz wykorzystywania plików cookies.',
                'template' => 'legal',
                'seo_title' => 'Polityka Prywatności | Hello Kostek',
                'seo_description' => 'Informacje o przetwarzaniu danych osobowych (RODO) oraz polityce plików cookies sklepu Hello Kostek.',
                'is_active' => true,
                'published_at' => now(),
                'metadata' => [
                    'sections' => [
                        [
                            'label' => '§ 1. Postanowienia ogólne',
                            'id' => 'postanowienia-ogolne',
                            'content' => '<p>1. Administratorem danych osobowych zbieranych za pośrednictwem Sklepu hellokostek jest <strong>hellokostek Maciej Kosteczka</strong> z siedzibą w: <strong>Rynek 33, 42-470 Siewierz, woj. śląskie, Polska</strong>, NIP: <strong>6252363656</strong>, REGON: <strong>527158196</strong>, wpisany do Centralnej Ewidencji i Informacji o Działalności Gospodarczej (CEIDG), zwany dalej <strong>„Administratorem”</strong>.</p><p>2. Kontakt z Administratorem w sprawach związanych z ochroną i przetwarzaniem danych osobowych jest możliwy za pośrednictwem adresu e-mail: <strong>kontakt@hellokostek.pl</strong>, telefonicznie pod numerem <strong>662 707 153</strong> lub korespondencyjnie na adres siedziby wskazany w ust. 1.</p><p>3. Dane osobowe użytkowników są przetwarzane zgodnie z Rozporządzeniem Parlamentu Europejskiego i Rady (UE) 2016/679 z dnia 27 kwietnia 2016 r. (RODO), ustawą o ochronie danych osobowych oraz ustawą – Prawo komunikacji elektronicznej (PKE).</p>'
                        ],
                        [
                            'label' => '§ 2. Cele i podstawy prawne',
                            'id' => 'cele-i-podstawy-przetwarzania',
                            'content' => '<p>Administrator przetwarza dane osobowe w następujących celach:</p><ul><li><strong>Zawarcie i wykonanie umowy sprzedaży lub wyceny dzieła</strong> (art. 6 ust. 1 lit. b RODO) – przez czas niezbędny do realizacji zamówienia i obsługi posprzedażowej.</li><li><strong>Wypełnienie obowiązków prawnych i podatkowo-księgowych</strong> (art. 6 ust. 1 lit. c RODO) – przez okres wymagany przepisami prawa podatkowego (5 lat od końca roku kalendarzowego).</li><li><strong>Obsługa zapytań z formularza kontaktowego oraz dochodzenie lub obrona przed roszczeniami</strong> (art. 6 ust. 1 lit. f RODO – prawnie uzasadniony interes Administratora).</li></ul>'
                        ],
                        [
                            'label' => '§ 3. Odbiorcy danych',
                            'id' => 'odbiorcy-danych',
                            'content' => '<p>W związku z realizacją zamówień dane osobowe są przekazywane wyłącznie w niezbędnym zakresie podmiotom współpracującym: operatorowi bezpiecznych płatności elektronicznych (<strong>Stripe Payments Europe, Ltd.</strong>), przewoźnikom doręczającym przesyłki (<strong>InPost Sp. z o.o.</strong>, <strong>ORLEN Paczka</strong>, firmy kurierskie), biuru rachunkowemu oraz dostawcy hostingu.</p>'
                        ],
                        [
                            'label' => '§ 4. Twoje prawa (RODO)',
                            'id' => 'prawa-uzytkownikow',
                            'content' => '<p>Każdej osobie, której dane dotyczą, przysługuje prawo dostępu do treści swoich danych, ich sprostowania, usunięcia, ograniczenia przetwarzania, przenoszenia danych oraz wniesienia sprzeciwu. Przysługuje również prawo wniesienia skargi do Prezesa Urzędu Ochrony Danych Osobowych (UODO, ul. Stawki 2, 00-193 Warszawa).</p>'
                        ],
                        [
                            'label' => '§ 5. Pliki cookies',
                            'id' => 'pliki-cookies',
                            'content' => '<p>Strona internetowa korzysta wyłącznie z niezbędnych technicznie plików cookies oraz pamięci lokalnej przeglądarki (<code>localStorage</code>) potrzebnych do prawidłowego funkcjonowania koszyka zakupowego i bezpiecznej realizacji płatności (zgodnie z art. 399 ust. 3 pkt 2 Prawa komunikacji elektronicznej). Strona nie ładuje zewnętrznych skryptów śledzących ani reklamowych bez wyraźnej zgody użytkownika.</p>'
                        ],
                        [
                            'label' => '§ 6. Dyrektywa Omnibus',
                            'id' => 'profilowanie-i-omnibus',
                            'content' => '<p>Sklep nie stosuje zautomatyzowanego podejmowania decyzji ani indywidualnego dostosowywania cen na podstawie profilowania. W przypadku ogłoszenia obniżki ceny produktu, obok ceny promocyjnej zawsze prezentowana jest najniższa cena z 30 dni przed wprowadzeniem obniżki.</p>'
                        ],
                        [
                            'label' => '§ 7. Postanowienia końcowe',
                            'id' => 'postanowienia-koncowe',
                            'content' => '<p>1. Administrator stosuje środki techniczne i organizacyjne zapewniające ochronę przetwarzanych danych osobowych (w tym szyfrowanie połączeń SSL/TLS).</p><p>2. Niniejsza Polityka Prywatności jest na bieżąco weryfikowana i w razie potrzeby aktualizowana.</p>'
                        ]
                    ]
                ]
            ]
        );

        \App\Models\ContentPage::query()->updateOrCreate(
            ['slug' => 'o-mnie'],
            [
                'title' => 'Kostek Maciej Kosteczka',
                'excerpt' => '„Wydawać się może, że klasyczne malarstwo i sztuka figuratywna bardzo ograniczają twórcę, ale stosując zasady z improwizacji w teatrze, że: „gdzie są ograniczenia, tam jest najwięcej wolności”, szukam wolności w malarstwie realistycznym.”',
                'content' => "Ukończyłem Państwową Wyższą Szkołę Teatralną w Krakowie na Wydziale Teatru Tańca w Bytomiu oraz kulturoznawstwo na Uniwersytecie Śląskim w Katowicach. Byłem stypendystą Roberta Wilsona w Watermill Center w Nowym Jorku (International Summer Program). Pracowałem w Teatrze Tańca i Ruchu Rozbark, brałem udział w projekcie „Ksenofonia Symfonia dla Innego” w ramach Festiwalu Malta oraz współtworzyłem spektakl „A co gdyby” na rezydencji Gdańskiego Festiwalu Tańca. Od lat współpracuję z Teatrem w Ruchu, grając w spektaklach dla dzieci w całym kraju. Mam też za sobą doświadczenie pracy w korporacji.\n\nRównolegle rozwijam się jako malarz, grafik i twórca rękodzieła – od 2023 roku prowadzę własną działalność: hellokostek, tworząc obrazy olejne na zamówienie oraz ucząc aktorstwa i rysunku. Od 2020 roku doskonalę warsztat pod okiem Pauliny Kwietniewskiej (malarstwo) i Macieja Kusa (rzeźba).\n\nWystawy indywidualne: „SIEWIERZ-513W13R2”, 2017, Hotel Natura Residence, Siewierz; „Rogue Planet”, 2022, Klubokawiarnia KIPISZ, Łódź.\n\nWystawy zbiorowe: „Spotkanie na czerwonej kanapie - jak rzeźba wrocławska czuje się w Łodzi”, 2025, OFF Piotrkowska, Łódź.\n\nMoje prace są dostępne w galerii Two Of Art w Amsterdamie oraz na aukcjach organizowanych przez galerię Pragaleria w Warszawie.",
                'hero_image_path' => 'content-pages/o_mnie_nowe.webp',
                'template' => 'about',
                'seo_title' => 'O mnie | Hello Kostek',
                'seo_description' => 'Poznaj moją pracownię artystyczną Hello Kostek. Tradycyjne malarstwo olejne, akrylowe, akwarele i rysunki tworzone z pasją.',
                'is_active' => true,
                'published_at' => now(),
                'metadata' => [
                    'subtitle' => 'Aktor • Tancerz • Malarz • Rocznik ‘88',
                    'quote' => '„Wydawać się może, że klasyczne malarstwo i sztuka figuratywna bardzo ograniczają twórcę, ale stosując zasady z improwizacji w teatrze, że: „gdzie są ograniczenia, tam jest najwięcej wolności”, szukam wolności w malarstwie realistycznym.”',
                    'box_title' => 'Dlaczego malarstwo tradycyjne?',
                    'box_text' => 'Wierzę, że w dobie sztucznej inteligencji i fotografii cyfrowej to właśnie tradycyjne malarstwo i sztuka użytkowa mają największą wartość sentymentalną. W końcu za każdym stworzonym przeze mnie dziełem stoją godziny pracy nad detalami i serce włożone w proces twórczy.',
                    'cta_title' => 'Podoba Ci się moja twórczość?',
                    'cta_text' => 'Zamów swój własny, unikalny portret ze zdjęcia lub zapoznaj się z gotowymi dziełami malarskimi dostępnymi w moim sklepie.',
                ],
            ]
        );

        // 6. CZĘSTO ZADAWANE PYTANIA (FAQ)
        $faqItems = [
            [
                'question' => 'Jak zamawiać obraz na indywidualne zamówienie lub portret ze zdjęcia?',
                'answer' => '<p>Wystarczy wypełnić <strong>formularz kontaktowy</strong> na stronie lub wysłać wiadomość ze zdjęciem referencyjnym. Po ustaleniu wymiaru, techniki (olej, akryl, akwarela, rysunek) oraz kompozycji, przygotuję dla Ciebie wycenę i prześlę wstępne propozycje.</p>',
                'group_name' => 'Zamówienia indywidualne',
                'sort_order' => 10,
            ],
            [
                'question' => 'Jak zabezpieczane są obrazy i rysunki podczas transportu?',
                'answer' => '<p>Każde dzieło traktuję wyjątkowo! Obrazy na płótnie pakuję w narożniki ochronne, folię bąbelkową i grube kartony malarskie. Akwarele i rysunki zabezpieczam na płasko przekładkami bezkwasowymi i sztywną tekturą, dzięki czemu przesyłka dociera w nienaruszonym stanie.</p>',
                'group_name' => 'Wysyłka i opakowanie',
                'sort_order' => 20,
            ],
            [
                'question' => 'Czy obrazy sprzedawane są z ramą?',
                'answer' => '<p>Większość obrazów olejnych i akrylowych na płótnie posiada estetycznie zamalowane krawędzie i jest <strong>gotowa do powieszenia</strong> bez dodatkowej ramy. W opisach poszczególnych prac zawsze znajdziesz dokładną informację o oprawie.</p>',
                'group_name' => 'Oprawa i prezentacja',
                'sort_order' => 30,
            ],
            [
                'question' => 'Jaki jest czas dostawy gotowych prac oraz zamówień dedykowanych?',
                'answer' => '<p>Prace z gotowej kolekcji wysyłam zazwyczaj w ciągu <strong>24–48 godzin</strong>. Czas wykonania portretu na zamówienie wynosi zazwyczaj od 7 do 14 dni roboczych, w zależności od wybranej techniki i czasochłonności.</p>',
                'group_name' => 'Czas realizacji',
                'sort_order' => 40,
            ],
            [
                'question' => 'Jak odpowiednio dbać o akwarele i obrazy olejne?',
                'answer' => '<p>Prace wykonane w technice akwarelowej warto oprawić za szkłem (najlepiej z passe-partout) i unikać eksponowania ich na bezpośrednie promienie słoneczne. Obrazy olejne i akrylowe są zabezpieczone profesjonalnym werniksem – do ich pielęgnacji wystarczy sucha, miękka szmatka z mikrofibry.</p>',
                'group_name' => 'Pielęgnacja',
                'sort_order' => 50,
            ],
        ];

        foreach ($faqItems as $item) {
            \App\Models\FaqItem::query()->updateOrCreate(
                ['question' => $item['question']],
                [
                    'answer' => $item['answer'],
                    'group_name' => $item['group_name'],
                    'sort_order' => $item['sort_order'],
                    'is_active' => true,
                ]
            );
        }

        // 7. GALERIA I PORTFOLIO (GalleryArtwork)
        $artworksData = [
            [
                'title' => 'Portret Kobiety',
                'category_slug' => 'olej',
                'technique' => 'Olej na płótnie',
                'format' => '30x40 cm',
                'year' => '2024',
                'image_path' => 'images/portret_Leona.webp',
                'original_url' => '/images/portret_Leona.webp',
                'sort_order' => 10,
            ],
            [
                'title' => 'Ciepły portret dziewczynki',
                'category_slug' => 'olej',
                'technique' => 'Olej na płótnie',
                'format' => '30x40 cm',
                'year' => '2024',
                'image_path' => 'images/20240917_211358-edited-2.webp',
                'original_url' => '/images/20240917_211358-scaled.webp',
                'sort_order' => 20,
            ],
            [
                'title' => 'Portret dojrzałej kobiety',
                'category_slug' => 'olej',
                'technique' => 'Olej na płótnie',
                'format' => '40x50 cm',
                'year' => '2024',
                'image_path' => 'images/20240115_174016-edited.webp',
                'original_url' => '/images/20240115_174016-scaled.webp',
                'sort_order' => 30,
            ],
            [
                'title' => 'Portret rodzinny rodzeństwa',
                'category_slug' => 'olej',
                'technique' => 'Olej na płótnie',
                'format' => '50x70 cm',
                'year' => '2024',
                'image_path' => 'images/IMG-20240303-WA0001-edited.webp',
                'original_url' => '/images/IMG-20240303-WA0001.webp',
                'sort_order' => 40,
            ],
            [
                'title' => 'Portret małego chłopca',
                'category_slug' => 'olej',
                'technique' => 'Olej na płótnie',
                'format' => '30x40 cm',
                'year' => '2023',
                'image_path' => 'images/20240903_130426-edited.webp',
                'original_url' => '/images/20240903_130426.webp',
                'sort_order' => 50,
            ],
            [
                'title' => 'Portret dziewczynki w wianku',
                'category_slug' => 'olej',
                'technique' => 'Olej na płótnie',
                'format' => '40x50 cm',
                'year' => '2023',
                'image_path' => 'images/20231124_215938-777x1024.webp',
                'original_url' => '/images/20231124_215938.webp',
                'sort_order' => 60,
            ],
            [
                'title' => "Portret kota 'Stray'",
                'category_slug' => 'olej',
                'technique' => 'Olej na płótnie',
                'format' => '30x40 cm',
                'year' => '2023',
                'image_path' => 'images/Stray-2023-edited.webp',
                'original_url' => 'https://hellokostek.pl/stray/',
                'sort_order' => 70,
            ],
            [
                'title' => 'Portret psa Tequili',
                'category_slug' => 'olej',
                'technique' => 'Olej na płótnie owalnym',
                'format' => '30x40 cm',
                'year' => '2023',
                'image_path' => 'images/tequila-whole-edited-1.webp',
                'original_url' => '/images/tequila-whole.webp',
                'sort_order' => 80,
            ],
            [
                'title' => 'Portret małego Franka',
                'category_slug' => 'olej',
                'technique' => 'Olej na płótnie',
                'format' => '30x40 cm',
                'year' => '2023',
                'image_path' => 'images/Portret-Franka-23-scaled-e1689869410746.webp',
                'original_url' => '/images/Portret-Franka-2023-scaled.webp',
                'sort_order' => 90,
            ],
            [
                'title' => 'Portret kobiety z uśmiechem',
                'category_slug' => 'olej',
                'technique' => 'Olej na płótnie',
                'format' => '40x50 cm',
                'year' => '2023',
                'image_path' => 'images/20231206_012611.webp',
                'original_url' => '/images/20231206_012611.webp',
                'sort_order' => 100,
            ],
            [
                'title' => 'Portret dziewczyny z profilu',
                'category_slug' => 'olej',
                'technique' => 'Olej na płótnie',
                'format' => '30x40 cm',
                'year' => '2023',
                'image_path' => 'images/20231216_142134-edited.webp',
                'original_url' => '/images/20231216_142134.webp',
                'sort_order' => 110,
            ],
            [
                'title' => 'Portret dojrzałego mężczyzny z brodą',
                'category_slug' => 'olej',
                'technique' => 'Olej na płótnie',
                'format' => '40x50 cm',
                'year' => '2022',
                'image_path' => 'images/MBOne020-edited.webp',
                'original_url' => '/images/MBOne020.webp',
                'sort_order' => 120,
            ],
            [
                'title' => 'Portret młodej kobiety w zieleni',
                'category_slug' => 'olej',
                'technique' => 'Olej na płótnie',
                'format' => '30x40 cm',
                'year' => '2021',
                'image_path' => 'images/hellokostek017-edited.webp',
                'original_url' => '/images/hellokostek017-edited.webp',
                'sort_order' => 130,
            ],
            [
                'title' => 'Portret Ewy',
                'category_slug' => 'akryl',
                'technique' => 'Akryl na płótnie',
                'format' => '30x40 cm',
                'year' => '2023',
                'image_path' => 'images/ewa-luty-2023-edited.webp',
                'original_url' => '/images/ewa-luty-2023.webp',
                'sort_order' => 140,
            ],
            [
                'title' => 'Portret Ślubny Pary',
                'category_slug' => 'akryl',
                'technique' => 'Akryl na płótnie',
                'format' => '50x70 cm',
                'year' => '2022',
                'image_path' => 'images/Portret-Slubny-2022-edited.webp',
                'original_url' => '/images/Portret-Slubny-2022.webp',
                'sort_order' => 150,
            ],
            [
                'title' => 'Portret Dziadka',
                'category_slug' => 'akryl',
                'technique' => 'Akryl na płótnie',
                'format' => '40x50 cm',
                'year' => '2022',
                'image_path' => 'images/Dziadek-na-konkurs-Huawei-08.2021-edited.webp',
                'original_url' => '/images/Dziadek-na-konkurs-Huawei-08.2021-edited.webp',
                'sort_order' => 160,
            ],
            [
                'title' => 'Portret Ciotki',
                'category_slug' => 'akryl',
                'technique' => 'Akryl na płótnie',
                'format' => '30x40 cm',
                'year' => '2020',
                'image_path' => 'images/ciotka-babka-01-edited.webp',
                'original_url' => '/images/20191221_060019-scaled.webp',
                'sort_order' => 170,
            ],
            [
                'title' => 'Portret dojrzałego mężczyzny',
                'category_slug' => 'akryl',
                'technique' => 'Akryl na płótnie',
                'format' => '40x50 cm',
                'year' => '2017',
                'image_path' => 'images/20190103_103811-edited.webp',
                'original_url' => '/images/20190103_103811-scaled.webp',
                'sort_order' => 180,
            ],
            [
                'title' => 'Portret psa z kwiatami (profil)',
                'category_slug' => 'akryl',
                'technique' => 'Akryl na płótnie',
                'format' => '30x40 cm',
                'year' => '2017',
                'image_path' => 'images/13a-513W13R2-edited.webp',
                'original_url' => '/images/13a-513W13R2-scaled.webp',
                'sort_order' => 190,
            ],
            [
                'title' => 'Studium dziewczyny',
                'category_slug' => 'akryl',
                'technique' => 'Akryl na płótnie',
                'format' => '30x40 cm',
                'year' => '2019-2020',
                'image_path' => 'images/portrait-01-edited-1.webp',
                'original_url' => '/images/portrait-01-edited-1.webp',
                'sort_order' => 200,
            ],
            [
                'title' => 'Portret siedmiolatki',
                'category_slug' => 'akryl',
                'technique' => 'Akryl na płótnie',
                'format' => '30x40 cm',
                'year' => '2013',
                'image_path' => 'images/01-portret-edited.webp',
                'original_url' => '/images/01-portret-edited.webp',
                'sort_order' => 210,
            ],
            [
                'title' => 'Portret podwójny dzieci',
                'category_slug' => 'akryl',
                'technique' => 'Akryl na płótnie',
                'format' => '40x50 cm',
                'year' => '2016',
                'image_path' => 'images/podwojny-02-edited-1.webp',
                'original_url' => '/images/podwojny-02.webp',
                'sort_order' => 220,
            ],
            [
                'title' => 'Portret dziewczynki z profilu',
                'category_slug' => 'akryl',
                'technique' => 'Akryl na płótnie',
                'format' => '30x40 cm',
                'year' => '2017',
                'image_path' => 'images/06-513W13R2-edited.webp',
                'original_url' => '/images/06-513W13R2.webp',
                'sort_order' => 230,
            ],
            [
                'title' => 'Portret śpiącego dziecka',
                'category_slug' => 'akryl',
                'technique' => 'Akryl na płótnie',
                'format' => '30x40 cm',
                'year' => '2017',
                'image_path' => 'images/10-513W13R2-edited.webp',
                'original_url' => '/images/10-513W13R2.webp',
                'sort_order' => 240,
            ],
            [
                'title' => 'Owalny portret psa',
                'category_slug' => 'olej',
                'technique' => 'Olej na płótnie owalnym',
                'format' => '30x40 cm',
                'year' => '2026',
                'image_path' => 'images/gallery_new_20250418_154931.webp',
                'original_url' => '/images/gallery_new_20250418_154931.webp',
                'sort_order' => 250,
            ],
            [
                'title' => 'Portret kota',
                'category_slug' => 'olej',
                'technique' => 'Olej na płótnie',
                'format' => '30x40 cm',
                'year' => '2026',
                'image_path' => 'images/gallery_new_20251124_231952.webp',
                'original_url' => '/images/gallery_new_20251124_231952.webp',
                'sort_order' => 260,
            ],
            [
                'title' => 'Portret z trzema psami',
                'category_slug' => 'olej',
                'technique' => 'Olej na płótnie',
                'format' => '50x70 cm',
                'year' => '2026',
                'image_path' => 'images/gallery_new_20251212_003229.webp',
                'original_url' => '/images/gallery_new_20251212_003229.webp',
                'sort_order' => 270,
            ],
            [
                'title' => 'Portret rodzeństwa',
                'category_slug' => 'olej',
                'technique' => 'Olej na płótnie',
                'format' => '40x50 cm',
                'year' => '2026',
                'image_path' => 'images/gallery_new_20260427_182645.webp',
                'original_url' => '/images/gallery_new_20260427_182645.webp',
                'sort_order' => 280,
            ],
            [
                'title' => 'Szkic parku z fontanną',
                'category_slug' => 'rysunek',
                'technique' => 'Rysunek ołówkiem na papierze',
                'format' => 'A4',
                'year' => '2026',
                'image_path' => 'images/gallery_new_20260527_010704.webp',
                'original_url' => '/images/gallery_new_20260527_010704.webp',
                'sort_order' => 290,
            ],
            [
                'title' => 'Studium leżącej postaci',
                'category_slug' => 'rysunek',
                'technique' => 'Rysunek ołówkiem na papierze',
                'format' => 'A4',
                'year' => '2026',
                'image_path' => 'images/gallery_new_20260607_192928.webp',
                'original_url' => '/images/gallery_new_20260607_192928.webp',
                'sort_order' => 300,
            ],
            [
                'title' => 'Szkic portretu kobiety',
                'category_slug' => 'rysunek',
                'technique' => 'Rysunek ołówkiem na papierze',
                'format' => 'A4',
                'year' => '2026',
                'image_path' => 'images/gallery_new_20260609_230909.webp',
                'original_url' => '/images/gallery_new_20260609_230909.webp',
                'sort_order' => 310,
            ],
            [
                'title' => 'Owalny portret kobiety',
                'category_slug' => 'olej',
                'technique' => 'Olej na płótnie owalnym',
                'format' => '30x40 cm',
                'year' => '2026',
                'image_path' => 'images/gallery_new_20260620_200043.webp',
                'original_url' => '/images/gallery_new_20260620_200043.webp',
                'sort_order' => 320,
            ],
            [
                'title' => 'Oprawiony portret z trzema psami',
                'category_slug' => 'olej',
                'technique' => 'Olej na płótnie',
                'format' => '50x70 cm',
                'year' => '2026',
                'image_path' => 'images/gallery_new_whatsapp_image_2026_02_23_at_17.15.06.webp',
                'original_url' => '/images/gallery_new_whatsapp_image_2026_02_23_at_17.15.06.webp',
                'sort_order' => 330,
            ],
        ];

        // Usunięcie zaszłościowego duplikatu ze starej ścieżki mobilnej
        GalleryArtwork::query()
            ->where('image_path', 'images/portret_franka_mobile.webp')
            ->delete();

        foreach ($artworksData as $artwork) {
            $catId = isset($categories[$artwork['category_slug']])
                ? $categories[$artwork['category_slug']]->id
                : null;

            $basename = basename($artwork['image_path']);
            $altGalleryPath = 'gallery/' . $basename;

            $existing = GalleryArtwork::query()
                ->whereIn('image_path', [$artwork['image_path'], $altGalleryPath])
                ->orderBy('id')
                ->get();

            if ($existing->count() > 1) {
                $existing->slice(1)->each->delete();
            }

            $record = $existing->first();
            $targetImagePath = $record ? $record->image_path : $artwork['image_path'];

            GalleryArtwork::query()->updateOrCreate(
                ['image_path' => $targetImagePath],
                [
                    'category_id' => $catId,
                    'title' => $artwork['title'],
                    'technique' => $artwork['technique'],
                    'format' => $artwork['format'],
                    'year' => $artwork['year'],
                    'image_path' => $targetImagePath,
                    'original_url' => $artwork['original_url'],
                    'sort_order' => $artwork['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
