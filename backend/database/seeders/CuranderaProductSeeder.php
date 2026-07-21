<?php

namespace Database\Seeders;

use App\Domain\Commerce\Enums\ProductType;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

/**
 * Seeds the actual Curandera product catalog into the local development database.
 * These products must match the slugs defined in storefront/src/data/products.js.
 */
class CuranderaProductSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure required categories exist
        $categories = [
            'grzyby-witalne' => 'Grzyby Witalne',
            'superfoods'     => 'Superfoods',
            'ebooki'         => 'E-booki i Edukacja',
            'irydologia'     => 'Usługi Irydologiczne',
        ];

        $categoryModels = [];
        $sortOrder = 0;
        foreach ($categories as $slug => $name) {
            $categoryModels[$slug] = ProductCategory::firstOrCreate(
                ['slug' => $slug],
                ['name' => ['pl' => $name], 'sort_order' => $sortOrder++]
            );
        }

        $products = [
            [
                'slug'                 => 'soplowka-jezowata-ekstrakt-z-grzybni-50g',
                'sku'                  => 'CUR-GRZ-001',
                'type'                 => ProductType::Physical,
                'name'                 => ['pl' => 'Soplówka Jeżowata – ekstrakt z grzybni 50g'],
                'short_description'    => ['pl' => 'Tradycyjny grzyb funkcjonalny ceniony za bogactwo erynaczyn. Wsparcie codziennej witalności.'],
                'regular_price_amount' => 18900,
                'sale_price_amount'    => null,
                'vat_rate'             => 8,
                'is_active'            => true,
                'is_visible'           => true,
                'is_purchasable'       => true,
                'manages_stock'        => false,
                'weight'               => 0.1,
                'category_slug'        => 'grzyby-witalne',
            ],
            [
                'slug'                 => 'reishi-czerwone',
                'sku'                  => 'CUR-GRZ-002',
                'type'                 => ProductType::Physical,
                'name'                 => ['pl' => 'Reishi Czerwone – ekstrakt 50g'],
                'short_description'    => ['pl' => 'Klasyczny adaptogen wspierający naturalne wyciszenie i codzienną harmonię.'],
                'regular_price_amount' => 14700,
                'sale_price_amount'    => null,
                'vat_rate'             => 8,
                'is_active'            => true,
                'is_visible'           => true,
                'is_purchasable'       => true,
                'manages_stock'        => false,
                'weight'               => 0.1,
                'category_slug'        => 'grzyby-witalne',
            ],
            [
                'slug'                 => 'chaga',
                'sku'                  => 'CUR-GRZ-003',
                'type'                 => ProductType::Physical,
                'name'                 => ['pl' => 'Chaga – ekstrakt 50g'],
                'short_description'    => ['pl' => 'Ceniony grzyb witalny o wysokim potencjale antyoksydacyjnym. Naturalna tarcza obronna.'],
                'regular_price_amount' => 14700,
                'sale_price_amount'    => null,
                'vat_rate'             => 8,
                'is_active'            => true,
                'is_visible'           => true,
                'is_purchasable'       => true,
                'manages_stock'        => false,
                'weight'               => 0.1,
                'category_slug'        => 'grzyby-witalne',
            ],
            [
                'slug'                 => 'cordyceps-sinensis',
                'sku'                  => 'CUR-GRZ-004',
                'type'                 => ProductType::Physical,
                'name'                 => ['pl' => 'Cordyceps Sinensis – ekstrakt 50g'],
                'short_description'    => ['pl' => 'Naturalny tonik energetyzujący. Wsparcie wydolności i witalności organizmu.'],
                'regular_price_amount' => 14700,
                'sale_price_amount'    => null,
                'vat_rate'             => 8,
                'is_active'            => true,
                'is_visible'           => true,
                'is_purchasable'       => true,
                'manages_stock'        => false,
                'weight'               => 0.1,
                'category_slug'        => 'grzyby-witalne',
            ],
            [
                'slug'                 => 'soplowka-jezowata-owocnik',
                'sku'                  => 'CUR-GRZ-005',
                'type'                 => ProductType::Physical,
                'name'                 => ['pl' => 'Soplówka Jeżowata – ekstrakt z owocnika 50g'],
                'short_description'    => ['pl' => 'Tradycyjne wsparcie układu pokarmowego i harmonii trawiennej.'],
                'regular_price_amount' => 14700,
                'sale_price_amount'    => null,
                'vat_rate'             => 8,
                'is_active'            => true,
                'is_visible'           => true,
                'is_purchasable'       => true,
                'manages_stock'        => false,
                'weight'               => 0.1,
                'category_slug'        => 'grzyby-witalne',
            ],
            [
                'slug'                 => 'tremella-ekstrakt-50g',
                'sku'                  => 'CUR-GRZ-006',
                'type'                 => ProductType::Physical,
                'name'                 => ['pl' => 'Tremella – ekstrakt 50g'],
                'short_description'    => ['pl' => 'Tradycyjny grzyb witalny, ceniony w kulturze Wschodu. Naturalne wsparcie i harmonia.'],
                'regular_price_amount' => 14700,
                'sale_price_amount'    => null,
                'vat_rate'             => 8,
                'is_active'            => true,
                'is_visible'           => true,
                'is_purchasable'       => true,
                'manages_stock'        => false,
                'weight'               => 0.1,
                'category_slug'        => 'grzyby-witalne',
            ],
            [
                'slug'                 => 'wrosniak-roznobarwny-ekstrakt-50g',
                'sku'                  => 'CUR-GRZ-007',
                'type'                 => ProductType::Physical,
                'name'                 => ['pl' => 'Wrośniak Różnobarwny – ekstrakt 50g'],
                'short_description'    => ['pl' => 'Tradycyjny grzyb witalny ceniony za silne wsparcie układu odpornościowego.'],
                'regular_price_amount' => 18900,
                'sale_price_amount'    => null,
                'vat_rate'             => 8,
                'is_active'            => true,
                'is_visible'           => true,
                'is_purchasable'       => true,
                'manages_stock'        => false,
                'weight'               => 0.1,
                'category_slug'        => 'grzyby-witalne',
            ],
            [
                'slug'                 => 'kurka',
                'sku'                  => 'CUR-GRZ-008',
                'type'                 => ProductType::Physical,
                'name'                 => ['pl' => 'Kurka – ekstrakt 50g'],
                'short_description'    => ['pl' => 'Ekstrakt z leśnej kurki. Źródło witamin, minerałów oraz naturalnych polisacharydów.'],
                'regular_price_amount' => 14700,
                'sale_price_amount'    => null,
                'vat_rate'             => 8,
                'is_active'            => true,
                'is_visible'           => true,
                'is_purchasable'       => true,
                'manages_stock'        => false,
                'weight'               => 0.1,
                'category_slug'        => 'grzyby-witalne',
            ],
            [
                'slug'                 => 'mlody-zielony-jeczmien-surowy-sproszkowany-sok-100g',
                'sku'                  => 'CUR-SUP-001',
                'type'                 => ProductType::Physical,
                'name'                 => ['pl' => 'Młody Zielony Jęczmień – sproszkowany sok 100g'],
                'short_description'    => ['pl' => 'Sproszkowany sok z młodego jęczmienia. Bogate źródło chlorofilu, enzymów i minerałów.'],
                'regular_price_amount' => 6700,
                'sale_price_amount'    => null,
                'vat_rate'             => 8,
                'is_active'            => true,
                'is_visible'           => true,
                'is_purchasable'       => true,
                'manages_stock'        => false,
                'weight'               => 0.15,
                'category_slug'        => 'superfoods',
            ],
            [
                'slug'                 => 'prozdrowotne-buliony-grzybowe-roslinne-pelne-smaku',
                'sku'                  => 'CUR-DIG-001',
                'type'                 => ProductType::Digital,
                'name'                 => ['pl' => 'E-book „Prozdrowotne buliony"'],
                'short_description'    => ['pl' => 'Autorskie przepisy łączące grzyby witalne z tradycyjnymi wywarami dla zdrowia jelit.'],
                'regular_price_amount' => 5500,
                'sale_price_amount'    => 3300,
                'vat_rate'             => 5,
                'is_active'            => true,
                'is_visible'           => true,
                'is_purchasable'       => true,
                'manages_stock'        => false,
                'weight'               => null,
                'category_slug'        => 'ebooki',
            ],
            [
                'slug'                 => 'irydologia-pakiet-1',
                'sku'                  => 'CUR-SRV-001',
                'type'                 => ProductType::Service,
                'name'                 => ['pl' => 'Irydologia: Pakiet I (Podstawowy)'],
                'short_description'    => ['pl' => 'Podstawowa diagnostyka stanu zdrowia online na podstawie zdjęcia tęczówki oka.'],
                'regular_price_amount' => 19900,
                'sale_price_amount'    => null,
                'vat_rate'             => 23,
                'is_active'            => true,
                'is_visible'           => true,
                'is_purchasable'       => true,
                'manages_stock'        => false,
                'weight'               => null,
                'category_slug'        => 'irydologia',
            ],
            [
                'slug'                 => 'irydologia-pakiet-2',
                'sku'                  => 'CUR-SRV-002',
                'type'                 => ProductType::Service,
                'name'                 => ['pl' => 'Irydologia: Pakiet II (Kompleksowy)'],
                'short_description'    => ['pl' => 'Rozszerzona analiza irydologiczna z pełnym, spersonalizowanym planem suplementacji grzybowej.'],
                'regular_price_amount' => 29900,
                'sale_price_amount'    => null,
                'vat_rate'             => 23,
                'is_active'            => true,
                'is_visible'           => true,
                'is_purchasable'       => true,
                'manages_stock'        => false,
                'weight'               => null,
                'category_slug'        => 'irydologia',
            ],
            // Gift wrap — required by ProductBuyBox.jsx when giftWrap option is used
            [
                'slug'                 => 'zestaw-prezentowy-curandera',
                'sku'                  => 'CUR-GIFT-001',
                'type'                 => ProductType::Physical,
                'name'                 => ['pl' => 'Pakowanie na prezent'],
                'short_description'    => ['pl' => 'Eleganckie pakowanie produktu na prezent.'],
                'regular_price_amount' => 1500,
                'sale_price_amount'    => null,
                'vat_rate'             => 23,
                'is_active'            => true,
                'is_visible'           => false,  // hidden from catalog, only added programmatically
                'is_purchasable'       => true,
                'manages_stock'        => false,
                'weight'               => 0.05,
                'category_slug'        => null,
            ],
        ];

        foreach ($products as $data) {
            $categorySlug = $data['category_slug'];
            unset($data['category_slug']);

            $product = Product::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );

            if ($categorySlug && isset($categoryModels[$categorySlug])) {
                $product->categories()->syncWithoutDetaching([$categoryModels[$categorySlug]->id]);
            }
        }

        $this->command->info('Curandera products seeded: ' . count($products) . ' records.');
    }
}
