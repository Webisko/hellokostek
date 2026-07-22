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
                'title' => 'Obiekt II',
                'year' => '2022',
                'category' => 'akwarela',
                'originalPrice' => 300,
                'printPrice' => 30,
                'isOriginalAvailable' => true,
                'imageUrl' => 'products/Wiecej-o-obiekcie-2-2022-edited-768x768.webp',
                'description' => 'Subtelna akwarela z cyklu badającego formę i relacje przestrzenne. Delikatne rozmycia i głębokie tony budują melancholijny, intymny nastrój idealny do sypialni lub salonu wypoczynkowego.'
            ],
            [
                'id' => 'akwarela-7-2022',
                'title' => 'Obiekt VII',
                'year' => '2022',
                'category' => 'akwarela',
                'originalPrice' => 300,
                'printPrice' => 30,
                'isOriginalAvailable' => true,
                'imageUrl' => 'products/Wiecej-o-obiekcie-7-2022-scaled.webp',
                'description' => 'Poruszająca kompozycja akwarelowa na grubym papierze bawełnianym. Harmoniczne zestrojenie chłodnych barw z delikatną nutą ciepła emanuje spokojem i wyciszeniem.'
            ],
            [
                'id' => 'akwarela-8-2022',
                'title' => 'Obiekt VIII',
                'year' => '2022',
                'category' => 'akwarela',
                'originalPrice' => 300,
                'printPrice' => 30,
                'isOriginalAvailable' => true,
                'imageUrl' => 'products/Wiecej-o-obiekcie-8.webp',
                'description' => 'Kameralna praca z przewagą organicznych, miękkich kształtów. Urzekający detal, który przyciąga wzrok i zaprasza do codziennej, cichej kontemplacji.'
            ],
            [
                'id' => 'akwarela-9-2022',
                'title' => 'Obiekt IX',
                'year' => '2022',
                'category' => 'akwarela',
                'originalPrice' => 300,
                'printPrice' => 30,
                'isOriginalAvailable' => true,
                'imageUrl' => 'products/Wiecej-o-obiekcie-9-2022-scaled.webp',
                'description' => 'Zmysłowe, płynne przejścia akwarelowe. Praca o silnym ładunku emocjonalnym, zbalansowana lekkim tłem, która doskonale komponuje się z nowoczesnymi oraz klasycznymi wnętrzami.'
            ],
            [
                'id' => 'akwarela-13-2022',
                'title' => 'Obiekt XIII (Sygnowany)',
                'year' => '2022',
                'category' => 'akwarela',
                'originalPrice' => 300,
                'printPrice' => 30,
                'isOriginalAvailable' => false,
                'imageUrl' => 'products/Wiecej-o-obiekcie-13-2022-scaled.webp',
                'description' => 'Wyrafinowana kompozycja akwarelowa, dostępna wyłącznie w postaci wysokiej jakości wydruku artystycznego na luksusowym papierze archiwalnym.'
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
                ['product_option_id' => $option->id, 'value' => 'Reprodukcja (Wydruk)'],
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

            // 2. Wariant: Reprodukcja (Wydruk)
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

        // 3. GALERIA (na podstawie src/data/gallery.ts)
        $galleryData = [
            ['id' => 'gallery-1', 'title' => 'Portret Kobiety', 'year' => '2024', 'imageUrl' => 'gallery/portret_Leona.webp', 'originalUrl' => null, 'technique' => 'olej'],
            ['id' => 'gallery-2', 'title' => 'Ciepły portret dziewczynki', 'year' => '2024', 'imageUrl' => 'gallery/20240917_211358-edited-2.webp', 'originalUrl' => '/hellokostek/images/20240917_211358-scaled.webp', 'technique' => 'olej'],
            ['id' => 'gallery-3', 'title' => 'Portret dojrzałej kobiety', 'year' => '2024', 'imageUrl' => 'gallery/20240115_174016-edited.webp', 'originalUrl' => '/hellokostek/images/20240115_174016-scaled.webp', 'technique' => 'olej'],
            ['id' => 'gallery-4', 'title' => 'Portret rodzinny rodzeństwa', 'year' => '2024', 'imageUrl' => 'gallery/IMG-20240303-WA0001-edited.webp', 'originalUrl' => '/hellokostek/images/IMG-20240303-WA0001.webp', 'technique' => 'olej'],
            ['id' => 'gallery-6', 'title' => 'Portret małego chłopca', 'year' => '2023', 'imageUrl' => 'gallery/20240903_130426-edited.webp', 'originalUrl' => '/hellokostek/images/20240903_130426.webp', 'technique' => 'olej'],
            ['id' => 'gallery-7', 'title' => 'Portret dziewczynki w wianku', 'year' => '2023', 'imageUrl' => 'gallery/20231124_215938-777x1024.webp', 'originalUrl' => '/hellokostek/images/20231124_215938.webp', 'technique' => 'olej'],
            ['id' => 'gallery-8', 'title' => "Portret kota 'Stray'", 'year' => '2023', 'imageUrl' => 'gallery/Stray-2023-edited.webp', 'originalUrl' => 'https://hellokostek.pl/stray/', 'technique' => 'olej'],
            ['id' => 'gallery-9', 'title' => "Portret psa 'Tequila'", 'year' => '2023', 'imageUrl' => 'gallery/tequila-whole-edited-1.webp', 'originalUrl' => '/hellokostek/images/tequila-whole.webp', 'technique' => 'olej'],
            ['id' => 'gallery-10', 'title' => 'Portret Franka', 'year' => '2023', 'imageUrl' => 'gallery/Portret-Franka-23-scaled-e1689869410746.webp', 'originalUrl' => '/hellokostek/images/Portret-Franka-2023-scaled.webp', 'technique' => 'olej'],
            ['id' => 'gallery-11', 'title' => 'Portret uśmiechniętej dziewczynki', 'year' => '2023', 'imageUrl' => 'gallery/20231206_012611.webp', 'originalUrl' => '/hellokostek/images/20231206_012611.webp', 'technique' => 'olej'],
            ['id' => 'gallery-12', 'title' => 'Portret dojrzałej kobiety w profilu', 'year' => '2023', 'imageUrl' => 'gallery/20231216_142134-edited.webp', 'originalUrl' => '/hellokostek/images/20231216_142134.webp', 'technique' => 'olej'],
            ['id' => 'gallery-13', 'title' => 'Portret kobiety - profil', 'year' => '2021', 'imageUrl' => 'gallery/MBOne020-edited.webp', 'originalUrl' => '/hellokostek/images/MBOne020.webp', 'technique' => 'akwarela'],
            ['id' => 'gallery-14', 'title' => 'Portret młodej kobiety w zieleni', 'year' => '2021', 'imageUrl' => 'gallery/hellokostek017-edited.webp', 'originalUrl' => '/hellokostek/images/hellokostek017-edited.webp', 'technique' => 'akwarela'],
            ['id' => 'gallery-16', 'title' => 'Portret Ewy', 'year' => '2023', 'imageUrl' => 'gallery/ewa-luty-2023-edited.webp', 'originalUrl' => '/hellokostek/images/ewa-luty-2023.webp', 'technique' => 'akryl'],
            ['id' => 'gallery-17', 'title' => 'Portret Ślubny Pary', 'year' => '2022', 'imageUrl' => 'gallery/Portret-Slubny-2022-edited.webp', 'originalUrl' => '/hellokostek/images/Portret-Slubny-2022.webp', 'technique' => 'akryl'],
            ['id' => 'gallery-18', 'title' => 'Portret Dziadka', 'year' => '2022', 'imageUrl' => 'gallery/Dziadek-na-konkurs-Huawei-08.2021-edited.webp', 'originalUrl' => '/hellokostek/images/Dziadek-na-konkurs-Huawei-08.2021-edited.webp', 'technique' => 'akryl'],
            ['id' => 'gallery-19', 'title' => 'Portret Ciotki', 'year' => '2020', 'imageUrl' => 'gallery/ciotka-babka-01-edited.webp', 'originalUrl' => '/hellokostek/images/20191221_060019-scaled.webp', 'technique' => 'akryl'],
            ['id' => 'gallery-20', 'title' => 'Portret dojrzałego mężczyzny', 'year' => '2017', 'imageUrl' => 'gallery/20190103_103811-edited.webp', 'originalUrl' => '/hellokostek/images/20190103_103811-scaled.webp', 'technique' => 'akryl'],
            ['id' => 'gallery-21', 'title' => 'Portret pfsa z kwiatami (profil)', 'year' => '2017', 'imageUrl' => 'gallery/13a-513W13R2-edited.webp', 'originalUrl' => '/hellokostek/images/13a-513W13R2-scaled.webp', 'technique' => 'akryl'],
            ['id' => 'gallery-25', 'title' => 'Studium dziewczyny', 'year' => '2019-2020', 'imageUrl' => 'gallery/portrait-01-edited-1.webp', 'originalUrl' => '/hellokostek/images/portrait-01-edited-1.webp', 'technique' => 'akryl'],
            ['id' => 'gallery-26', 'title' => 'Portret dziewczynki w kapeluszu', 'year' => '2013', 'imageUrl' => 'gallery/01-portret-edited.webp', 'originalUrl' => '/hellokostek/images/01-portret-edited.webp', 'technique' => 'akryl'],
            ['id' => 'gallery-27', 'title' => 'Portret podwójny children', 'year' => '2016', 'imageUrl' => 'gallery/podwojny-02-edited-1.webp', 'originalUrl' => '/hellokostek/images/podwojny-02.webp', 'technique' => 'akryl'],
            ['id' => 'gallery-31', 'title' => 'Portret dziewczynki z profilu', 'year' => '2017', 'imageUrl' => 'gallery/06-513W13R2-edited.webp', 'originalUrl' => '/hellokostek/images/06-513W13R2.webp', 'technique' => 'akryl'],
            ['id' => 'gallery-32', 'title' => 'Portret śpiącego dziecka', 'year' => '2017', 'imageUrl' => 'gallery/10-513W13R2-edited.webp', 'originalUrl' => '/hellokostek/images/10-513W13R2.webp', 'technique' => 'akryl'],
            ['id' => 'gallery-33', 'title' => 'Owalny portret psa', 'year' => '2026', 'imageUrl' => 'gallery/gallery_new_20250418_154931.webp', 'originalUrl' => '/hellokostek/images/gallery_new_20250418_154931.webp', 'technique' => 'olej'],
            ['id' => 'gallery-34', 'title' => 'Portret kota', 'year' => '2026', 'imageUrl' => 'gallery/gallery_new_20251124_231952.webp', 'originalUrl' => '/hellokostek/images/gallery_new_20251124_231952.webp', 'technique' => 'olej'],
            ['id' => 'gallery-35', 'title' => 'Portret z trzema psami', 'year' => '2026', 'imageUrl' => 'gallery/gallery_new_20251212_003229.webp', 'originalUrl' => '/hellokostek/images/gallery_new_20251212_003229.webp', 'technique' => 'olej'],
            ['id' => 'gallery-36', 'title' => 'Portret rodzeństwa', 'year' => '2026', 'imageUrl' => 'gallery/gallery_new_20260427_182645.webp', 'originalUrl' => '/hellokostek/images/gallery_new_20260427_182645.webp', 'technique' => 'olej'],
            ['id' => 'gallery-37', 'title' => 'Szkic parku z fontanną', 'year' => '2026', 'imageUrl' => 'gallery/gallery_new_20260527_010704.webp', 'originalUrl' => '/hellokostek/images/gallery_new_20260527_010704.webp', 'technique' => 'rysunek'],
            ['id' => 'gallery-38', 'title' => 'Studium leżącej postaci', 'year' => '2026', 'imageUrl' => 'gallery/gallery_new_20260607_192928.webp', 'originalUrl' => '/hellokostek/images/gallery_new_20260607_192928.webp', 'technique' => 'rysunek'],
            ['id' => 'gallery-39', 'title' => 'Szkic portretu kobiety', 'year' => '2026', 'imageUrl' => 'gallery/gallery_new_20260609_230909.webp', 'originalUrl' => '/hellokostek/images/gallery_new_20260609_230909.webp', 'technique' => 'rysunek'],
            ['id' => 'gallery-40', 'title' => 'Owalny portret kobiety', 'year' => '2026', 'imageUrl' => 'gallery/gallery_new_20260620_200043.webp', 'originalUrl' => '/hellokostek/images/gallery_new_20260620_200043.webp', 'technique' => 'olej'],
            ['id' => 'gallery-41', 'title' => 'Oprawiony portret z trzema psami', 'year' => '2026', 'imageUrl' => 'gallery/gallery_new_whatsapp_image_2026_02_23_at_17.15.06.webp', 'originalUrl' => '/hellokostek/images/gallery_new_whatsapp_image_2026_02_23_at_17.15.06.webp', 'technique' => 'olej']
        ];

        $techniqueCategoryMap = [
            'olej' => $categories['olej']->id ?? null,
            'akryl' => $categories['akryl']->id ?? null,
            'akwarela' => $categories['akwarela']->id ?? $categories['watercolor']->id ?? null,
            'rysunek' => $categories['rysunek']->id ?? $categories['drawing']->id ?? null,
        ];

        foreach ($galleryData as $sortOrder => $art) {
            $catId = $techniqueCategoryMap[$art['technique']] ?? null;

            GalleryArtwork::query()->updateOrCreate(
                ['id' => (int) str_replace('gallery-', '', $art['id'])],
                [
                    'category_id' => $catId,
                    'title' => $art['title'],
                    'technique' => $art['technique'],
                    'year' => $art['year'],
                    'image_path' => $art['imageUrl'],
                    'original_url' => $art['originalUrl'],
                    'is_active' => true,
                    'sort_order' => $sortOrder * 10,
                ]
            );
        }

        // 4. OPINIE KLIENCKIE (ProductReview dla strony głównej)
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
                            'content' => '<p>Niniejszy Regulamin określa zasady korzystania ze sklepu internetowego Hello Kostek prowadzonego przez Macieja Kostka.</p><p>Sklep prowadzi sprzedaż autorskich dzieł sztuki, obrazów olejnych, akrylowych, akwareli, rysunków oraz spersonalizowanych rękodzieł.</p>'
                        ],
                        [
                            'label' => '§ 2. Definicje przedmiotów',
                            'id' => 'definicje',
                            'content' => '<p>Oryginał – unikatowe dzieło sztuki wykonane własnoręcznie przez artystę.</p><p>Reprodukcja (Wydruk) – wysokiej jakości wydruk cyfrowy na papierze artystycznym archiwalnym.</p>'
                        ],
                        [
                            'label' => '§ 3. Składanie zamówień',
                            'id' => 'skladanie-zamowien',
                            'content' => '<p>Zamówienia można składać całą dobę poprzez formularz zamówienia w sklepie internetowym.</p>'
                        ],
                        [
                            'label' => '§ 4. Ceny i płatności',
                            'id' => 'ceny-i-platnosci',
                            'content' => '<p>Ceny podane w Sklepie są cenami brutto wyrażonymi w złotych polskich (PLN).</p><p>Dostępne metody płatności: BLIK, szybkie przelewy Przelewy24, karty płatnicze Stripe oraz tradycyjny przelew bankowy.</p>'
                        ],
                        [
                            'label' => '§ 5. Dostawa i wysyłka',
                            'id' => 'dostawa',
                            'content' => '<p>Wysyłka realizowana jest na terenie Polski za pośrednictwem Paczkomatów InPost oraz kuriera.</p>'
                        ],
                        [
                            'label' => '§ 6. Prawo do zwrotu',
                            'id' => 'zwroty-i-odstapienie',
                            'content' => '<p>Konsument ma prawo odstąpić od umowy w terminie 14 dni bez podania przyczyny.</p><p>Prawo do zwrotu nie dotyczy produktów wykonanych na indywidualne zamówienie (np. spersonalizowane portrety na zamówienie).</p>'
                        ],
                        [
                            'label' => '§ 7. Reklamacje',
                            'id' => 'reklamacje',
                            'content' => '<p>W przypadku stwierdzenia wady towaru kupujący ma prawo złożyć reklamację drogą mailową na kontakt@hellokostek.pl.</p>'
                        ],
                        [
                            'label' => '§ 8. Prawa autorskie',
                            'id' => 'prawa-autorskie',
                            'content' => '<p>Wszystkie prezentowane prace i dzieła sztuki są chronione prawem autorskim. Zakup oryginału lub reprodukcji nie oznacza przeniesienia autorskich praw majątkowych ani prawa do dalszego powielania.</p>'
                        ],
                        [
                            'label' => '§ 9. Rozpatrywanie sporów',
                            'id' => 'pozasadowe-rozwiazywanie-sporow',
                            'content' => '<p>Kupujący będący konsumentem ma możliwość skorzystania z pozasądowych sposobów rozpatrywania reklamacji i dochodzenia roszczeń.</p>'
                        ],
                        [
                            'label' => '§ 10. Postanowienia końcowe',
                            'id' => 'postanowienia-koncowe',
                            'content' => '<p>Regulamin wchodzi w życie z dniem publikacji na stronie sklepu. Artysta zastrzega sobie prawo do zmiany regulaminu z zachowaniem praw nabytych kupujących.</p>'
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
                            'content' => '<p>Administratorem danych osobowych jest Maciej Kostek prowadzący działalność pod marką Hello Kostek.</p>'
                        ],
                        [
                            'label' => '§ 2. Cele i podstawy prawne',
                            'id' => 'cele-i-podstawy-przetwarzania',
                            'content' => '<p>Dane osobowe przetwarzane są w celu realizacji zamówień, obsługi zapytań kontaktowych oraz spełnienia obowiązków podatkowo-księgowych.</p>'
                        ],
                        [
                            'label' => '§ 3. Odbiorcy danych',
                            'id' => 'odbiorcy-danych',
                            'content' => '<p>Odbiorcami danych są podmioty świadczące usługi dostawy (InPost), bramki płatności (Przelewy24, Stripe) oraz dostawcy usług IT.</p>'
                        ],
                        [
                            'label' => '§ 4. Twoje prawa (RODO)',
                            'id' => 'prawa-uzytkownikow',
                            'content' => '<p>Każda osoba ma prawo do dostępu do swoich danych, ich sprostowania, usunięcia, ograniczenia przetwarzania oraz wniesienia sprzeciwu.</p>'
                        ],
                        [
                            'label' => '§ 5. Pliki cookies',
                            'id' => 'pliki-cookies',
                            'content' => '<p>Strona wykorzystuje pliki cookies w celu zapewnienia prawidłowego działania koszyka i sesji oraz w celach analitycznych.</p>'
                        ],
                        [
                            'label' => '§ 6. Dyrektywa Omnibus',
                            'id' => 'profilowanie-i-omnibus',
                            'content' => '<p>Ceny prezentowane w sklepie spełniają wymogi dyrektywy Omnibus. Nie stosujemy automatycznego profilowania cen na podstawie historii przeglądania.</p>'
                        ],
                        [
                            'label' => '§ 7. Postanowienia końcowe',
                            'id' => 'postanowienia-koncowe',
                            'content' => '<p>W sprawach nieuregulowanych polityką stosuje się przepisy RODO i polskiego prawa o ochronie danych osobowych.</p>'
                        ]
                    ]
                ]
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
    }
}
