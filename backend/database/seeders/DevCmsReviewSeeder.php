<?php

namespace Database\Seeders;

use App\Domain\Commerce\Enums\ProductType;
use App\Models\ContentPage;
use App\Models\FaqItem;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DevCmsReviewSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@genericshop.local')->firstOrNew();
        $admin->forceFill([
            'name' => 'Shop Admin',
            'email' => 'admin@genericshop.local',
            'password' => Hash::make('Admin1234!'),
            'is_admin' => true,
            'role' => \App\Domain\Commerce\Enums\UserRole::Admin,
        ])->save();

        $categories = collect([
            [
                'slug' => 'ziolowe-mieszanki',
                'name' => 'Zioloowe mieszanki',
                'description' => 'Produkty wspierajace codzienne rytualy i naturalne wsparcie organizmu.',
                'sort_order' => 10,
            ],
            [
                'slug' => 'rytualy-i-wsparcie',
                'name' => 'Rytualy i wsparcie',
                'description' => 'Produkty do regularnej pracy z cialem, energia i codziennymi nawykami.',
                'sort_order' => 20,
            ],
            [
                'slug' => 'konsultacje',
                'name' => 'Konsultacje',
                'description' => 'Uslugi i konsultacje prowadzone przez sklep.',
                'sort_order' => 30,
            ],
        ])->mapWithKeys(function (array $category): array {
            $model = ProductCategory::query()->updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'seo_title' => $category['name'] . ' | Sklep',
                    'seo_description' => $category['description'],
                    'sort_order' => $category['sort_order'],
                    'is_active' => true,
                ],
            );

            return [$category['slug'] => $model];
        });

        $attributes = collect([
            [
                'slug' => 'forma-podania',
                'name' => 'Forma podania',
                'value_type' => 'text',
                'sort_order' => 10,
                'categories' => ['ziolowe-mieszanki', 'rytualy-i-wsparcie'],
            ],
            [
                'slug' => 'intencja',
                'name' => 'Intencja',
                'value_type' => 'text',
                'sort_order' => 20,
                'categories' => ['ziolowe-mieszanki', 'rytualy-i-wsparcie', 'konsultacje'],
            ],
            [
                'slug' => 'czas-ritualu',
                'name' => 'Czas rytualu',
                'value_type' => 'text',
                'sort_order' => 30,
                'categories' => ['rytualy-i-wsparcie'],
            ],
            [
                'slug' => 'forma-konsultacji',
                'name' => 'Forma konsultacji',
                'value_type' => 'text',
                'sort_order' => 40,
                'categories' => ['konsultacje'],
            ],
        ])->mapWithKeys(function (array $attribute) use ($categories): array {
            $model = ProductAttribute::query()->updateOrCreate(
                ['slug' => $attribute['slug']],
                [
                    'name' => $attribute['name'],
                    'value_type' => $attribute['value_type'],
                    'sort_order' => $attribute['sort_order'],
                    'is_active' => true,
                ],
            );

            $model->categories()->sync(
                collect($attribute['categories'])
                    ->map(fn (string $slug): ?int => $categories->get($slug)?->id)
                    ->filter()
                    ->values()
                    ->all(),
            );

            return [$attribute['slug'] => $model];
        });

        $products = [
            [
                'slug' => 'esencja-rownowagi',
                'sku' => 'CUR-001',
                'type' => ProductType::Physical,
                'name' => 'Esencja Rownowagi',
                'short_description' => 'Roslinna mieszanka wspierajaca spokoj i codzienna regulacje.',
                'description' => 'Produkt demo do sprawdzenia ukladu katalogu, merchandisingu i atrybutow w CMS.',
                'regular_price_amount' => 6900,
                'sale_price_amount' => 5900,
                'stock_quantity' => 24,
                'manages_stock' => true,
                'is_active' => true,
                'is_visible' => true,
                'is_purchasable' => true,
                'is_new' => true,
                'is_bestseller' => true,
                'is_recommended' => true,
                'is_promoted' => true,
                'is_seasonal' => false,
                'is_clearance' => false,
                'show_on_homepage' => true,
                'show_in_bestsellers' => true,
                'show_in_new_arrivals' => true,
                'show_in_recommended' => true,
                'manual_tags' => ['spokoj', 'rytual wieczorny'],
                'categories' => ['ziolowe-mieszanki', 'rytualy-i-wsparcie'],
                'attributes' => [
                    'forma-podania' => 'Napar ziolowy',
                    'intencja' => 'Spokoj i regeneracja',
                    'czas-ritualu' => 'Wieczorny rytual 15 minut',
                ],
            ],
            [
                'slug' => 'kadzidlo-naturalne',
                'sku' => 'CUR-004',
                'type' => ProductType::Physical,
                'name' => 'Kadzidlo Naturalne',
                'short_description' => 'Tradycyjne kadzidlo do oczyszczania przestrzeni i wyciszenia.',
                'description' => 'Recznie zwijane kadzidlo z naturalnych ziol i zywic.',
                'regular_price_amount' => 4900,
                'sale_price_amount' => null,
                'stock_quantity' => 50,
                'manages_stock' => true,
                'is_active' => true,
                'is_visible' => true,
                'is_purchasable' => true,
                'is_new' => true,
                'is_bestseller' => false,
                'is_recommended' => true,
                'is_promoted' => false,
                'is_seasonal' => false,
                'is_clearance' => false,
                'show_on_homepage' => true,
                'show_in_bestsellers' => false,
                'show_in_new_arrivals' => true,
                'show_in_recommended' => true,
                'manual_tags' => ['kadzidlo', 'oczyszczenie'],
                'categories' => ['rytualy-i-wsparcie'],
                'attributes' => [
                    'forma-podania' => 'Peczki ziolowe',
                    'intencja' => 'Oczyszczenie i relaks',
                    'czas-ritualu' => 'Rytual oczyszczenia 20 minut',
                ],
            ],
            [
                'slug' => 'ziolowa-herbatka-wyciszajaca',
                'sku' => 'CUR-005',
                'type' => ProductType::Physical,
                'name' => 'Ziolowa Herbatka Wyciszajaca',
                'short_description' => 'Kompozycja ziol wspierajaca dobry sen i odprezenie.',
                'description' => 'Mieszanka melisy, lawendy i rumianku zebranych w ekologicznych ogrodach.',
                'regular_price_amount' => 2900,
                'sale_price_amount' => null,
                'stock_quantity' => 35,
                'manages_stock' => true,
                'is_active' => true,
                'is_visible' => true,
                'is_purchasable' => true,
                'is_new' => false,
                'is_bestseller' => true,
                'is_recommended' => false,
                'is_promoted' => false,
                'is_seasonal' => false,
                'is_clearance' => false,
                'show_on_homepage' => true,
                'show_in_bestsellers' => true,
                'show_in_new_arrivals' => false,
                'show_in_recommended' => false,
                'manual_tags' => ['sen', 'ziola'],
                'categories' => ['ziolowe-mieszanki', 'rytualy-i-wsparcie'],
                'attributes' => [
                    'forma-podania' => 'Ziolowy susz',
                    'intencja' => 'Gleboki sen i spokoj',
                    'czas-ritualu' => 'Wieczorny rytual przed snem',
                ],
            ],
            [
                'slug' => 'rytual-oczyszczenia',
                'sku' => 'CUR-002',
                'type' => ProductType::Digital,
                'name' => 'Rytual Oczyszczenia',
                'short_description' => 'Cyfrowy przewodnik do pracy z codziennym rytualem oczyszczajacym.',
                'description' => 'Produkt demo pokazujacy typ cyfrowy, tagi i sekcje strony glownej.',
                'regular_price_amount' => 3900,
                'sale_price_amount' => null,
                'stock_quantity' => null,
                'manages_stock' => false,
                'is_active' => true,
                'is_visible' => true,
                'is_purchasable' => true,
                'is_new' => true,
                'is_bestseller' => false,
                'is_recommended' => true,
                'is_promoted' => false,
                'is_seasonal' => true,
                'is_clearance' => false,
                'show_on_homepage' => true,
                'show_in_bestsellers' => false,
                'show_in_new_arrivals' => true,
                'show_in_recommended' => true,
                'manual_tags' => ['ebook', 'praktyka domowa'],
                'categories' => ['rytualy-i-wsparcie'],
                'attributes' => [
                    'forma-podania' => 'E-book PDF',
                    'intencja' => 'Oczyszczenie i lekkość',
                    'czas-ritualu' => 'Poranny rytual 10 minut',
                ],
            ],
            [
                'slug' => 'medytacja-prowadzona',
                'sku' => 'CUR-006',
                'type' => ProductType::Digital,
                'name' => 'Medytacja Prowadzona',
                'short_description' => 'Audiobook MP3 z gleboka praktyka uwaznosci i relaksacji.',
                'description' => 'Prowadzona sesja medytacji do codziennego odsluchu przed snem lub rano.',
                'regular_price_amount' => 1900,
                'sale_price_amount' => null,
                'stock_quantity' => null,
                'manages_stock' => false,
                'is_active' => true,
                'is_visible' => true,
                'is_purchasable' => true,
                'is_new' => true,
                'is_bestseller' => false,
                'is_recommended' => true,
                'is_promoted' => false,
                'is_seasonal' => false,
                'is_clearance' => false,
                'show_on_homepage' => false,
                'show_in_bestsellers' => false,
                'show_in_new_arrivals' => true,
                'show_in_recommended' => true,
                'manual_tags' => ['audio', 'medytacja'],
                'categories' => ['rytualy-i-wsparcie'],
                'attributes' => [
                    'forma-podania' => 'Plik MP3',
                    'intencja' => 'Spokoj i mindfulness',
                    'czas-ritualu' => 'Dowolna pora dnia 15 minut',
                ],
            ],
            [
                'slug' => 'konsultacja-intuicyjna',
                'sku' => 'CUR-003',
                'type' => ProductType::Service,
                'name' => 'Konsultacja Intuicyjna',
                'short_description' => 'Usluga demo do przegladu typow uslugowych i oznaczen produktowych.',
                'description' => 'Przykladowa usluga do oceny sposobu prezentacji oferty i sekcji frontowych.',
                'regular_price_amount' => 18900,
                'sale_price_amount' => 16900,
                'stock_quantity' => null,
                'manages_stock' => false,
                'is_active' => true,
                'is_visible' => true,
                'is_purchasable' => true,
                'is_new' => false,
                'is_bestseller' => false,
                'is_recommended' => true,
                'is_promoted' => true,
                'is_seasonal' => false,
                'is_clearance' => false,
                'show_on_homepage' => true,
                'show_in_bestsellers' => false,
                'show_in_new_arrivals' => false,
                'show_in_recommended' => true,
                'manual_tags' => ['1:1', 'premium'],
                'categories' => ['konsultacje'],
                'attributes' => [
                    'intencja' => 'Indywidualne wsparcie i kierunek',
                    'forma-konsultacji' => 'Online / wideorozmowa',
                ],
            ],
            [
                'slug' => 'rytual-indywidualny',
                'sku' => 'CUR-007',
                'type' => ProductType::Service,
                'name' => 'Rytual Indywidualny',
                'short_description' => 'Indywidualna ceremonia i prowadzenie energetyczne dostosowane do Twoich potrzeb.',
                'description' => 'Prywatna sesja rytualna 1:1, w trakcie ktorej wspólnie pracujemy nad Twoja intencja.',
                'regular_price_amount' => 25000,
                'sale_price_amount' => null,
                'stock_quantity' => null,
                'manages_stock' => false,
                'is_active' => true,
                'is_visible' => true,
                'is_purchasable' => true,
                'is_new' => true,
                'is_bestseller' => false,
                'is_recommended' => true,
                'is_promoted' => true,
                'is_seasonal' => false,
                'is_clearance' => false,
                'show_on_homepage' => true,
                'show_in_bestsellers' => false,
                'show_in_new_arrivals' => true,
                'show_in_recommended' => true,
                'manual_tags' => ['ceremonia', 'indywidualny'],
                'categories' => ['konsultacje'],
                'attributes' => [
                    'intencja' => 'Gleboka transformacja osobista',
                    'forma-konsultacji' => 'Sesja online lub stacjonarnie',
                ],
            ],
        ];

        foreach ($products as $productData) {
            $product = Product::query()->updateOrCreate(
                ['slug' => $productData['slug']],
                [
                    'sku' => $productData['sku'],
                    'type' => $productData['type'],
                    'name' => $productData['name'],
                    'short_description' => $productData['short_description'],
                    'description' => $productData['description'],
                    'currency' => 'PLN',
                    'regular_price_amount' => $productData['regular_price_amount'],
                    'sale_price_amount' => $productData['sale_price_amount'],
                    'stock_quantity' => $productData['stock_quantity'],
                    'manages_stock' => $productData['manages_stock'],
                    'is_active' => $productData['is_active'],
                    'is_visible' => $productData['is_visible'],
                    'is_purchasable' => $productData['is_purchasable'],
                    'is_new' => $productData['is_new'],
                    'is_bestseller' => $productData['is_bestseller'],
                    'is_recommended' => $productData['is_recommended'],
                    'is_promoted' => $productData['is_promoted'],
                    'is_seasonal' => $productData['is_seasonal'],
                    'is_clearance' => $productData['is_clearance'],
                    'show_on_homepage' => $productData['show_on_homepage'],
                    'show_in_bestsellers' => $productData['show_in_bestsellers'],
                    'show_in_new_arrivals' => $productData['show_in_new_arrivals'],
                    'show_in_recommended' => $productData['show_in_recommended'],
                    'seo_title' => $productData['name'] . ' | Sklep',
                    'seo_description' => $productData['short_description'],
                    'published_at' => now()->subDay(),
                    'metadata' => [
                        'seeded_for' => 'cms_review',
                        'owner_id' => $admin->id,
                    ],
                    'manual_tags' => $productData['manual_tags'],
                ],
            );

            $product->categories()->sync(
                collect($productData['categories'])
                    ->map(fn (string $slug): ?int => $categories->get($slug)?->id)
                    ->filter()
                    ->values()
                    ->all(),
            );

            $attributeSync = collect($productData['attributes'])
                ->mapWithKeys(function (string $value, string $slug) use ($attributes): array {
                    $attribute = $attributes->get($slug);

                    if (! $attribute) {
                        return [];
                    }

                    return [
                        $attribute->id => [
                            'value' => $value,
                            'sort_order' => $attribute->sort_order,
                        ],
                    ];
                })
                ->all();

            $product->attributes()->sync($attributeSync);
        }

        foreach ([
            [
                'slug' => 'strona-glowna',
                'title' => 'Strona glowna',
                'template' => 'home',
                'excerpt' => 'Glowny punkt startowy dla odwiedzajacych.',
                'content' => 'Sekcja demo do oceny ukladu tresci i sposobu zarzadzania strona glowna w CMS.',
            ],
            [
                'slug' => 'o-marce',
                'title' => 'O marce',
                'template' => 'about',
                'excerpt' => 'Historia, podejscie i filozofia sklepu.',
                'content' => 'Strona demo przygotowana do dalszej rozbudowy pod Astro i branding.',
            ],
            [
                'slug' => 'kontakt',
                'title' => 'Kontakt',
                'template' => 'contact',
                'excerpt' => 'Miejsce na dane kontaktowe i formularz.',
                'content' => 'Strona demo pod formularz kontaktowy, dane firmy i dodatkowe CTA.',
            ],
        ] as $pageData) {
            ContentPage::query()->updateOrCreate(
                ['slug' => $pageData['slug']],
                [
                    'title' => $pageData['title'],
                    'excerpt' => $pageData['excerpt'],
                    'content' => $pageData['content'],
                    'template' => $pageData['template'],
                    'seo_title' => $pageData['title'] . ' | Sklep',
                    'seo_description' => $pageData['excerpt'],
                    'is_active' => true,
                    'published_at' => now()->subDay(),
                    'metadata' => ['seeded_for' => 'cms_review'],
                ],
            );
        }


        foreach ([
            ['question' => 'Jak dlugo idzie przesylka?', 'answer' => 'To wpis demo do oceny ukladu FAQ w panelu.', 'group_name' => 'Zamowienia', 'sort_order' => 10],
            ['question' => 'Czy moge kupic produkt jako prezent?', 'answer' => 'Tak, to przykladowa odpowiedz do dalszej edycji.', 'group_name' => 'Zakupy', 'sort_order' => 20],
            ['question' => 'Jak wyglada konsultacja online?', 'answer' => 'To przykladowy rekord do sprawdzenia struktury odpowiedzi i grup.', 'group_name' => 'Konsultacje', 'sort_order' => 30],
        ] as $faqData) {
            FaqItem::query()->updateOrCreate(
                ['question' => $faqData['question']],
                [
                    'answer' => $faqData['answer'],
                    'group_name' => $faqData['group_name'],
                    'sort_order' => $faqData['sort_order'],
                    'is_active' => true,
                    'metadata' => ['seeded_for' => 'cms_review'],
                ],
            );
        }


        // 10. Seed Customers (Users with role customer + CustomerProfile + Address)
        $customers = [
            [
                'email' => 'client1@example.com',
                'name' => 'Alicja Kowalska',
                'segment' => \App\Domain\Commerce\Enums\CustomerSegment::LoyalEight,
                'phone' => '111222333',
                'address' => [
                    'first_name' => 'Alicja',
                    'last_name' => 'Kowalska',
                    'street' => 'Jasna 12/4',
                    'city' => 'Warszawa',
                    'postal_code' => '00-001',
                    'country_code' => 'PL',
                ]
            ],
            [
                'email' => 'client2@example.com',
                'name' => 'Michał Wiśniewski',
                'segment' => \App\Domain\Commerce\Enums\CustomerSegment::Regular,
                'phone' => '444555666',
                'address' => [
                    'first_name' => 'Michał',
                    'last_name' => 'Wiśniewski',
                    'street' => 'Zielona 8',
                    'city' => 'Kraków',
                    'postal_code' => '30-002',
                    'country_code' => 'PL',
                ]
            ],
            [
                'email' => 'client3@example.com',
                'name' => 'Piotr Zieliński',
                'segment' => \App\Domain\Commerce\Enums\CustomerSegment::LoyalFive,
                'phone' => '777888999',
                'address' => [
                    'first_name' => 'Piotr',
                    'last_name' => 'Zieliński',
                    'street' => 'Długa 54',
                    'city' => 'Wrocław',
                    'postal_code' => '50-003',
                    'country_code' => 'PL',
                ]
            ],
        ];

        foreach ($customers as $cData) {
            $user = User::query()->updateOrCreate(
                ['email' => $cData['email']],
                [
                    'name' => $cData['name'],
                    'password' => Hash::make('Client123!'),
                    'role' => \App\Domain\Commerce\Enums\UserRole::Customer,
                ]
            );

            \App\Models\CustomerProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'segment' => $cData['segment'],
                    'phone' => $cData['phone'],
                    'completed_orders_count' => 1,
                    'marketing_consent_at' => now()->subMonths(2),
                    'last_order_at' => now()->subDays(3),
                ]
            );

            \App\Models\CustomerAddress::query()->updateOrCreate(
                ['user_id' => $user->id, 'address_line_1' => $cData['address']['street']],
                [
                    'first_name' => $cData['address']['first_name'],
                    'last_name' => $cData['address']['last_name'],
                    'city' => $cData['address']['city'],
                    'postal_code' => $cData['address']['postal_code'],
                    'country_code' => $cData['address']['country_code'],
                    'phone' => $cData['phone'],
                    'is_default_billing' => true,
                    'is_default_shipping' => true,
                    'name' => 'Główny adres',
                ]
            );
        }

        // 11. Seed Coupons
        $coupons = [
            ['code' => 'LATO15', 'name' => 'Kupon Letni 15%', 'discount_type' => 'percentage', 'value' => 1500, 'minimum_subtotal_amount' => 10000, 'usage_limit' => 100, 'usage_limit_per_customer' => 1, 'is_active' => true],
            ['code' => 'WELCOME10', 'name' => 'Zniżka Powitalna 10 PLN', 'discount_type' => 'fixed', 'value' => 1000, 'minimum_subtotal_amount' => 5000, 'usage_limit' => 1000, 'usage_limit_per_customer' => 1, 'is_active' => true],
        ];
        foreach ($coupons as $coupData) {
            \App\Models\Coupon::query()->updateOrCreate(
                ['code' => $coupData['code']],
                [
                    'name' => $coupData['name'],
                    'discount_type' => $coupData['discount_type'],
                    'value' => $coupData['value'],
                    'currency' => 'PLN',
                    'minimum_subtotal_amount' => $coupData['minimum_subtotal_amount'],
                    'usage_limit' => $coupData['usage_limit'],
                    'usage_limit_per_customer' => $coupData['usage_limit_per_customer'],
                    'starts_at' => now()->subMonth(),
                    'ends_at' => now()->addMonths(3),
                    'is_active' => $coupData['is_active'],
                ]
            );
        }

        // 12. Seed Product Reviews
        $dbProducts = Product::all();
        foreach ($dbProducts as $p) {
            \App\Models\ProductReview::query()->updateOrCreate(
                ['product_id' => $p->id, 'customer_email' => 'client1@example.com'],
                [
                    'customer_name' => 'Alicja Kowalska',
                    'rating' => 5,
                    'comment' => 'Świetny produkt, idealnie dopasowany do opisu. Serdecznie polecam wszystkim kupującym!',
                    'is_verified_purchase' => true,
                    'is_approved' => true,
                ]
            );
            \App\Models\ProductReview::query()->updateOrCreate(
                ['product_id' => $p->id, 'customer_email' => 'client2@example.com'],
                [
                    'customer_name' => 'Michał Wiśniewski',
                    'rating' => 4,
                    'comment' => 'Dobra jakość, sprawna dostawa. Chętnie kupię ponownie w tym sklepie.',
                    'is_verified_purchase' => true,
                    'is_approved' => true,
                ]
            );
        }

        // 13. Seed Orders & OrderItems & Returns
        $client1 = User::where('email', 'client1@example.com')->first();
        $client2 = User::where('email', 'client2@example.com')->first();
        $client3 = User::where('email', 'client3@example.com')->first();

        $couponWelcome = \App\Models\Coupon::where('code', 'WELCOME10')->first();

        $prodEsencja = Product::where('slug', 'esencja-rownowagi')->first();
        $prodKadzidlo = Product::where('slug', 'kadzidlo-naturalne')->first();
        $prodHerbatka = Product::where('slug', 'ziolowa-herbatka-wyciszajaca')->first();
        $prodRitualOczyszcz = Product::where('slug', 'rytual-oczyszczenia')->first();
        $prodMedytacja = Product::where('slug', 'medytacja-prowadzona')->first();
        $prodKonsultacja = Product::where('slug', 'konsultacja-intuicyjna')->first();
        $prodRitualIndywidualny = Product::where('slug', 'rytual-indywidualny')->first();

        if ($client1 && $client2 && $client3) {
            // ORDER 1: Fizyczne
            $order1 = \App\Models\Order::query()->updateOrCreate(
                ['number' => 'ORD-2026-0001'],
                [
                    'user_id' => $client1->id,
                    'coupon_id' => $couponWelcome?->id,
                    'status' => 'completed',
                    'payment_status' => 'paid',
                    'fulfillment_status' => 'fulfilled',
                    'currency' => 'PLN',
                    'customer_segment' => \App\Domain\Commerce\Enums\CustomerSegment::LoyalEight,
                    'customer_email' => $client1->email,
                    'customer_first_name' => 'Alicja',
                    'customer_last_name' => 'Kowalska',
                    'customer_phone' => '111222333',
                    'subtotal_amount' => 21600,
                    'discount_amount' => 1000,
                    'shipping_amount' => 1500,
                    'tax_amount' => 3852,
                    'total_amount' => 22100,
                    'shipping_method_code' => 'inpost_locker',
                    'shipping_method_name' => 'InPost Paczkomaty 24/7',
                    'billing_address' => [
                        'street' => 'Jasna 12/4',
                        'city' => 'Warszawa',
                        'postal_code' => '00-001',
                        'country_code' => 'PL',
                    ],
                    'shipping_address' => [
                        'street' => 'Paczkomat WAW123, Jasna 10',
                        'city' => 'Warszawa',
                        'postal_code' => '00-001',
                        'country_code' => 'PL',
                    ],
                    'placed_at' => now()->subDays(12),
                    'notes' => 'Proszę o staranne spakowanie paczki.',
                    'tracking_number' => '628394857201948576028194',
                    'carrier' => 'InPost',
                    'shipped_at' => now()->subDays(11),
                ]
            );

            if ($prodEsencja) {
                \App\Models\OrderItem::query()->updateOrCreate(
                    ['order_id' => $order1->id, 'product_id' => $prodEsencja->id],
                    [
                        'product_type' => $prodEsencja->type,
                        'sku' => $prodEsencja->sku,
                        'name' => $prodEsencja->name,
                        'quantity' => 2,
                        'unit_price_amount' => 6900,
                        'regular_unit_price_amount' => 6900,
                        'discount_amount' => 639,
                        'tax_amount' => 2461,
                        'total_amount' => 13161,
                    ]
                );
            }

            if ($prodKadzidlo) {
                \App\Models\OrderItem::query()->updateOrCreate(
                    ['order_id' => $order1->id, 'product_id' => $prodKadzidlo->id],
                    [
                        'product_type' => $prodKadzidlo->type,
                        'sku' => $prodKadzidlo->sku,
                        'name' => $prodKadzidlo->name,
                        'quantity' => 1,
                        'unit_price_amount' => 4900,
                        'regular_unit_price_amount' => 4900,
                        'discount_amount' => 227,
                        'tax_amount' => 874,
                        'total_amount' => 4673,
                    ]
                );
            }

            if ($prodHerbatka) {
                \App\Models\OrderItem::query()->updateOrCreate(
                    ['order_id' => $order1->id, 'product_id' => $prodHerbatka->id],
                    [
                        'product_type' => $prodHerbatka->type,
                        'sku' => $prodHerbatka->sku,
                        'name' => $prodHerbatka->name,
                        'quantity' => 1,
                        'unit_price_amount' => 2900,
                        'regular_unit_price_amount' => 2900,
                        'discount_amount' => 134,
                        'tax_amount' => 517,
                        'total_amount' => 2766,
                    ]
                );
            }

            // Create Return for order1
            $orderReturn = \App\Models\OrderReturn::query()->updateOrCreate(
                ['order_id' => $order1->id],
                [
                    'user_id' => $client1->id,
                    'status' => 'approved',
                    'reason' => 'Nie odpowiada mi ten smak herbaty.',
                    'refund_amount' => 2766,
                    'tracking_number' => 'RET-INPOST-123456',
                ]
            );

            $orderItem = \App\Models\OrderItem::where('order_id', $order1->id)->where('sku', 'CUR-005')->first();
            if ($orderItem) {
                \App\Models\OrderReturnItem::query()->updateOrCreate(
                    ['order_return_id' => $orderReturn->id, 'order_item_id' => $orderItem->id],
                    [
                        'quantity' => 1,
                    ]
                );
            }

            // ORDER 2: Cyfrowe
            $order2 = \App\Models\Order::query()->updateOrCreate(
                ['number' => 'ORD-2026-0002'],
                [
                    'user_id' => $client2->id,
                    'coupon_id' => null,
                    'status' => 'completed',
                    'payment_status' => 'paid',
                    'fulfillment_status' => 'fulfilled',
                    'currency' => 'PLN',
                    'customer_segment' => \App\Domain\Commerce\Enums\CustomerSegment::Regular,
                    'customer_email' => $client2->email,
                    'customer_first_name' => 'Michał',
                    'customer_last_name' => 'Wiśniewski',
                    'customer_phone' => '444555666',
                    'subtotal_amount' => 7700,
                    'discount_amount' => 0,
                    'shipping_amount' => 0,
                    'tax_amount' => 1440,
                    'total_amount' => 7700,
                    'shipping_method_code' => null,
                    'shipping_method_name' => null,
                    'billing_address' => [
                        'street' => 'Zielona 8',
                        'city' => 'Kraków',
                        'postal_code' => '30-002',
                        'country_code' => 'PL',
                    ],
                    'placed_at' => now()->subDays(8),
                ]
            );

            if ($prodRitualOczyszcz) {
                \App\Models\OrderItem::query()->updateOrCreate(
                    ['order_id' => $order2->id, 'product_id' => $prodRitualOczyszcz->id],
                    [
                        'product_type' => $prodRitualOczyszcz->type,
                        'sku' => $prodRitualOczyszcz->sku,
                        'name' => $prodRitualOczyszcz->name,
                        'quantity' => 1,
                        'unit_price_amount' => 3900,
                        'regular_unit_price_amount' => 3900,
                        'discount_amount' => 0,
                        'tax_amount' => 729,
                        'total_amount' => 3900,
                    ]
                );
            }

            if ($prodMedytacja) {
                \App\Models\OrderItem::query()->updateOrCreate(
                    ['order_id' => $order2->id, 'product_id' => $prodMedytacja->id],
                    [
                        'product_type' => $prodMedytacja->type,
                        'sku' => $prodMedytacja->sku,
                        'name' => $prodMedytacja->name,
                        'quantity' => 2,
                        'unit_price_amount' => 1900,
                        'regular_unit_price_amount' => 1900,
                        'discount_amount' => 0,
                        'tax_amount' => 711,
                        'total_amount' => 3800,
                    ]
                );
            }

            // ORDER 3: Usługi
            $order3 = \App\Models\Order::query()->updateOrCreate(
                ['number' => 'ORD-2026-0003'],
                [
                    'user_id' => $client3->id,
                    'coupon_id' => null,
                    'status' => 'placed',
                    'payment_status' => 'pending',
                    'fulfillment_status' => 'unfulfilled',
                    'currency' => 'PLN',
                    'customer_segment' => \App\Domain\Commerce\Enums\CustomerSegment::LoyalFive,
                    'customer_email' => $client3->email,
                    'customer_first_name' => 'Piotr',
                    'customer_last_name' => 'Zieliński',
                    'customer_phone' => '777888999',
                    'subtotal_amount' => 41900,
                    'discount_amount' => 0,
                    'shipping_amount' => 0,
                    'tax_amount' => 7835,
                    'total_amount' => 41900,
                    'shipping_method_code' => null,
                    'shipping_method_name' => null,
                    'billing_address' => [
                        'street' => 'Długa 54',
                        'city' => 'Wrocław',
                        'postal_code' => '50-003',
                        'country_code' => 'PL',
                    ],
                    'placed_at' => now()->subDays(1),
                ]
            );

            if ($prodKonsultacja) {
                \App\Models\OrderItem::query()->updateOrCreate(
                    ['order_id' => $order3->id, 'product_id' => $prodKonsultacja->id],
                    [
                        'product_type' => $prodKonsultacja->type,
                        'sku' => $prodKonsultacja->sku,
                        'name' => $prodKonsultacja->name,
                        'quantity' => 1,
                        'unit_price_amount' => 16900,
                        'regular_unit_price_amount' => 18900,
                        'discount_amount' => 0,
                        'tax_amount' => 3160,
                        'total_amount' => 16900,
                    ]
                );
            }

            if ($prodRitualIndywidualny) {
                \App\Models\OrderItem::query()->updateOrCreate(
                    ['order_id' => $order3->id, 'product_id' => $prodRitualIndywidualny->id],
                    [
                        'product_type' => $prodRitualIndywidualny->type,
                        'sku' => $prodRitualIndywidualny->sku,
                        'name' => $prodRitualIndywidualny->name,
                        'quantity' => 1,
                        'unit_price_amount' => 25000,
                        'regular_unit_price_amount' => 25000,
                        'discount_amount' => 0,
                        'tax_amount' => 4675,
                        'total_amount' => 25000,
                    ]
                );
            }

            // ORDER 4: Mieszane 1 (Fizyczny + Cyfrowy)
            $order4 = \App\Models\Order::query()->updateOrCreate(
                ['number' => 'ORD-2026-0004'],
                [
                    'user_id' => $client1->id,
                    'coupon_id' => null,
                    'status' => 'placed',
                    'payment_status' => 'paid',
                    'fulfillment_status' => 'unfulfilled',
                    'currency' => 'PLN',
                    'customer_segment' => \App\Domain\Commerce\Enums\CustomerSegment::LoyalEight,
                    'customer_email' => $client1->email,
                    'customer_first_name' => 'Alicja',
                    'customer_last_name' => 'Kowalska',
                    'customer_phone' => '111222333',
                    'subtotal_amount' => 7800,
                    'discount_amount' => 0,
                    'shipping_amount' => 1500,
                    'tax_amount' => 1458,
                    'total_amount' => 9300,
                    'shipping_method_code' => 'dhl_courier',
                    'shipping_method_name' => 'Kurier DHL',
                    'billing_address' => [
                        'street' => 'Jasna 12/4',
                        'city' => 'Warszawa',
                        'postal_code' => '00-001',
                        'country_code' => 'PL',
                    ],
                    'shipping_address' => [
                        'street' => 'Jasna 12/4',
                        'city' => 'Warszawa',
                        'postal_code' => '00-001',
                        'country_code' => 'PL',
                    ],
                    'placed_at' => now()->subDays(2),
                ]
            );

            if ($prodEsencja) {
                \App\Models\OrderItem::query()->updateOrCreate(
                    ['order_id' => $order4->id, 'product_id' => $prodEsencja->id],
                    [
                        'product_type' => $prodEsencja->type,
                        'sku' => $prodEsencja->sku,
                        'name' => $prodEsencja->name,
                        'quantity' => 1,
                        'unit_price_amount' => 5900,
                        'regular_unit_price_amount' => 6900,
                        'discount_amount' => 0,
                        'tax_amount' => 1103,
                        'total_amount' => 5900,
                    ]
                );
            }

            if ($prodMedytacja) {
                \App\Models\OrderItem::query()->updateOrCreate(
                    ['order_id' => $order4->id, 'product_id' => $prodMedytacja->id],
                    [
                        'product_type' => $prodMedytacja->type,
                        'sku' => $prodMedytacja->sku,
                        'name' => $prodMedytacja->name,
                        'quantity' => 1,
                        'unit_price_amount' => 1900,
                        'regular_unit_price_amount' => 1900,
                        'discount_amount' => 0,
                        'tax_amount' => 355,
                        'total_amount' => 1900,
                    ]
                );
            }

            // ORDER 5: Mieszane 2 (Fizyczny + Usługa)
            $order5 = \App\Models\Order::query()->updateOrCreate(
                ['number' => 'ORD-2026-0005'],
                [
                    'user_id' => $client2->id,
                    'coupon_id' => null,
                    'status' => 'completed',
                    'payment_status' => 'paid',
                    'fulfillment_status' => 'fulfilled',
                    'currency' => 'PLN',
                    'customer_segment' => \App\Domain\Commerce\Enums\CustomerSegment::Regular,
                    'customer_email' => $client2->email,
                    'customer_first_name' => 'Michał',
                    'customer_last_name' => 'Wiśniewski',
                    'customer_phone' => '444555666',
                    'subtotal_amount' => 22700,
                    'discount_amount' => 0,
                    'shipping_amount' => 1500,
                    'tax_amount' => 4245,
                    'total_amount' => 24200,
                    'shipping_method_code' => 'dhl_courier',
                    'shipping_method_name' => 'Kurier DHL',
                    'billing_address' => [
                        'street' => 'Zielona 8',
                        'city' => 'Kraków',
                        'postal_code' => '30-002',
                        'country_code' => 'PL',
                    ],
                    'shipping_address' => [
                        'street' => 'Zielona 8',
                        'city' => 'Kraków',
                        'postal_code' => '30-002',
                        'country_code' => 'PL',
                    ],
                    'placed_at' => now()->subDays(15),
                    'shipped_at' => now()->subDays(14),
                    'tracking_number' => 'DHL123456789PL',
                    'carrier' => 'DHL',
                ]
            );

            if ($prodHerbatka) {
                \App\Models\OrderItem::query()->updateOrCreate(
                    ['order_id' => $order5->id, 'product_id' => $prodHerbatka->id],
                    [
                        'product_type' => $prodHerbatka->type,
                        'sku' => $prodHerbatka->sku,
                        'name' => $prodHerbatka->name,
                        'quantity' => 2,
                        'unit_price_amount' => 2900,
                        'regular_unit_price_amount' => 2900,
                        'discount_amount' => 0,
                        'tax_amount' => 1085,
                        'total_amount' => 5800,
                    ]
                );
            }

            if ($prodKonsultacja) {
                \App\Models\OrderItem::query()->updateOrCreate(
                    ['order_id' => $order5->id, 'product_id' => $prodKonsultacja->id],
                    [
                        'product_type' => $prodKonsultacja->type,
                        'sku' => $prodKonsultacja->sku,
                        'name' => $prodKonsultacja->name,
                        'quantity' => 1,
                        'unit_price_amount' => 16900,
                        'regular_unit_price_amount' => 18900,
                        'discount_amount' => 0,
                        'tax_amount' => 3160,
                        'total_amount' => 16900,
                    ]
                );
            }

            // ORDER 6: Mieszane 3 (Cyfrowy + Usługa)
            $order6 = \App\Models\Order::query()->updateOrCreate(
                ['number' => 'ORD-2026-0006'],
                [
                    'user_id' => $client3->id,
                    'coupon_id' => null,
                    'status' => 'processing',
                    'payment_status' => 'paid',
                    'fulfillment_status' => 'unfulfilled',
                    'currency' => 'PLN',
                    'customer_segment' => \App\Domain\Commerce\Enums\CustomerSegment::LoyalFive,
                    'customer_email' => $client3->email,
                    'customer_first_name' => 'Piotr',
                    'customer_last_name' => 'Zieliński',
                    'customer_phone' => '777888999',
                    'subtotal_amount' => 28900,
                    'discount_amount' => 0,
                    'shipping_amount' => 0,
                    'tax_amount' => 5404,
                    'total_amount' => 28900,
                    'shipping_method_code' => null,
                    'shipping_method_name' => null,
                    'billing_address' => [
                        'street' => 'Długa 54',
                        'city' => 'Wrocław',
                        'postal_code' => '50-003',
                        'country_code' => 'PL',
                    ],
                    'placed_at' => now()->subDays(3),
                ]
            );

            if ($prodRitualOczyszcz) {
                \App\Models\OrderItem::query()->updateOrCreate(
                    ['order_id' => $order6->id, 'product_id' => $prodRitualOczyszcz->id],
                    [
                        'product_type' => $prodRitualOczyszcz->type,
                        'sku' => $prodRitualOczyszcz->sku,
                        'name' => $prodRitualOczyszcz->name,
                        'quantity' => 1,
                        'unit_price_amount' => 3900,
                        'regular_unit_price_amount' => 3900,
                        'discount_amount' => 0,
                        'tax_amount' => 729,
                        'total_amount' => 3900,
                    ]
                );
            }

            if ($prodRitualIndywidualny) {
                \App\Models\OrderItem::query()->updateOrCreate(
                    ['order_id' => $order6->id, 'product_id' => $prodRitualIndywidualny->id],
                    [
                        'product_type' => $prodRitualIndywidualny->type,
                        'sku' => $prodRitualIndywidualny->sku,
                        'name' => $prodRitualIndywidualny->name,
                        'quantity' => 1,
                        'unit_price_amount' => 25000,
                        'regular_unit_price_amount' => 25000,
                        'discount_amount' => 0,
                        'tax_amount' => 4675,
                        'total_amount' => 25000,
                    ]
                );
            }
        }

        // 14. Seed Back in Stock Subscriptions
        $product = Product::first();
        if ($product) {
            \App\Models\BackInStockSubscription::query()->updateOrCreate(
                ['email' => 'interested@example.com', 'product_id' => $product->id],
                [
                    'status' => 'pending',
                ]
            );
        }

        $this->call(\Database\Seeders\EmailTemplateSeeder::class);
    }
}