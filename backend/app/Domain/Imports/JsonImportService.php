<?php

namespace App\Domain\Imports;

use App\Models\BlogPost;
use App\Models\ContentPage;
use App\Models\Coupon;
use App\Models\CustomerProfile;
use App\Models\FaqItem;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\OrderFulfillmentAction;
use App\Models\OrderItem;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\RedirectRule;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;

class JsonImportService
{
    /**
     * @return array{dataset: string, dry_run: bool, processed: int, created: int, updated: int}
     */
    public function import(string $dataset, string $path, bool $dryRun = false): array
    {
        if (! is_file($path)) {
            throw new InvalidArgumentException('Plik importu nie istnieje: ' . $path);
        }

        $json = json_decode((string) file_get_contents($path), true);
        if (! is_array($json)) {
            throw new InvalidArgumentException('Plik JSON ma nieprawidlowy format.');
        }

        $records = $this->normalizeRecords($json);

        $summary = [
            'dataset' => $dataset,
            'dry_run' => $dryRun,
            'processed' => count($records),
            'created' => 0,
            'updated' => 0,
        ];

        foreach ($records as $record) {
            [$created, $updated] = $this->importRecord($dataset, $record, $dryRun);
            $summary['created'] += $created;
            $summary['updated'] += $updated;
        }

        return $summary;
    }

    /**
     * @param  array<int|string, mixed>  $json
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRecords(array $json): array
    {
        if (array_is_list($json)) {
            return $json;
        }

        $records = $json['data'] ?? null;
        if (is_array($records) && array_is_list($records)) {
            return $records;
        }

        throw new InvalidArgumentException('JSON importu musi byc lista rekordow lub zawierac pole data[].');
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array{0:int,1:int}
     */
    private function importRecord(string $dataset, array $record, bool $dryRun): array
    {
        return match ($dataset) {
            'product-categories' => $this->importProductCategory($record, $dryRun),
            'products' => $this->importProduct($record, $dryRun),
            'content-pages' => $this->importContentPage($record, $dryRun),
            'blog-posts' => $this->importBlogPost($record, $dryRun),
            'faq-items' => $this->importFaqItem($record, $dryRun),
            'coupons' => $this->importCoupon($record, $dryRun),
            'newsletter-subscribers' => $this->importNewsletterSubscriber($record, $dryRun),
            'redirect-rules' => $this->importRedirectRule($record, $dryRun),
            'customers' => $this->importCustomer($record, $dryRun),
            'orders' => $this->importOrder($record, $dryRun),
            default => throw new InvalidArgumentException('Nieobslugiwany dataset importu: ' . $dataset),
        };
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array{0:int,1:int}
     */
    private function importProductCategory(array $record, bool $dryRun): array
    {
        $slug = $this->requireString($record, 'slug');

        return $this->upsert(
            modelClass: ProductCategory::class,
            unique: ['slug' => $slug],
            attributes: [
                'name' => $this->requireString($record, 'name'),
                'description' => Arr::get($record, 'description'),
                'seo_title' => Arr::get($record, 'seo_title'),
                'seo_description' => Arr::get($record, 'seo_description'),
                'sort_order' => (int) Arr::get($record, 'sort_order', 0),
                'is_active' => (bool) Arr::get($record, 'is_active', true),
            ],
            dryRun: $dryRun,
        );
    }

    /**
     * @param  array<string, mixed>  $record
     * @return array{0:int,1:int}
     */
    private function importProduct(array $record, bool $dryRun): array
    {
        $slug = $this->requireString($record, 'slug');

        [$created, $updated] = $this->upsert(
            modelClass: Product::class,
            unique: ['slug' => $slug],
            attributes: [
                'sku' => Arr::get($record, 'sku'),
                'type' => $this->requireString($record, 'type'),
                'name' => $this->requireString($record, 'name'),
                'short_description' => Arr::get($record, 'short_description'),
                'description' => Arr::get($record, 'description'),
                'featured_image_path' => Arr::get($record, 'featured_image_path'),
                'currency' => Arr::get($record, 'currency', 'PLN'),
                'regular_price_amount' => (int) Arr::get($record, 'regular_price_amount', 0),
                'sale_price_amount' => Arr::has($record, 'sale_price_amount') ? (int) Arr::get($record, 'sale_price_amount') : null,
                'stock_quantity' => Arr::has($record, 'stock_quantity') ? (int) Arr::get($record, 'stock_quantity') : null,
                'manages_stock' => (bool) Arr::get($record, 'manages_stock', false),
                'is_active' => (bool) Arr::get($record, 'is_active', true),
                'is_visible' => (bool) Arr::get($record, 'is_visible', true),
                'is_purchasable' => (bool) Arr::get($record, 'is_purchasable', true),
                'seo_title' => Arr::get($record, 'seo_title'),
                'seo_description' => Arr::get($record, 'seo_description'),
                'published_at' => $this->nullableDate(Arr::get($record, 'published_at')),
                'metadata' => Arr::get($record, 'metadata', []),
            ],
            dryRun: $dryRun,
        );

        if (! $dryRun) {
            $product = Product::query()->where('slug', $slug)->firstOrFail();
            $categorySlugs = array_values(array_filter(Arr::get($record, 'category_slugs', [])));
            if ($categorySlugs !== []) {
                $categoryIds = ProductCategory::query()->whereIn('slug', $categorySlugs)->pluck('id')->all();
                $product->categories()->sync($categoryIds);
            }
        }

        return [$created, $updated];
    }

    private function importContentPage(array $record, bool $dryRun): array
    {
        return $this->upsertBySlug(ContentPage::class, $record, $dryRun, [
            'title' => $this->requireString($record, 'title'),
            'excerpt' => Arr::get($record, 'excerpt'),
            'content' => Arr::get($record, 'content'),
            'template' => Arr::get($record, 'template', 'default'),
            'seo_title' => Arr::get($record, 'seo_title'),
            'seo_description' => Arr::get($record, 'seo_description'),
            'is_active' => (bool) Arr::get($record, 'is_active', true),
            'published_at' => $this->nullableDate(Arr::get($record, 'published_at')),
            'metadata' => Arr::get($record, 'metadata', []),
        ]);
    }

    private function importBlogPost(array $record, bool $dryRun): array
    {
        return $this->upsertBySlug(BlogPost::class, $record, $dryRun, [
            'title' => $this->requireString($record, 'title'),
            'excerpt' => Arr::get($record, 'excerpt'),
            'content' => Arr::get($record, 'content'),
            'author_name' => Arr::get($record, 'author_name'),
            'cover_image_url' => Arr::get($record, 'cover_image_url'),
            'seo_title' => Arr::get($record, 'seo_title'),
            'seo_description' => Arr::get($record, 'seo_description'),
            'is_active' => (bool) Arr::get($record, 'is_active', true),
            'published_at' => $this->nullableDate(Arr::get($record, 'published_at')),
            'metadata' => Arr::get($record, 'metadata', []),
        ]);
    }

    private function importFaqItem(array $record, bool $dryRun): array
    {
        return $this->upsert(
            modelClass: FaqItem::class,
            unique: [
                'question' => $this->requireString($record, 'question'),
                'group_name' => Arr::get($record, 'group_name'),
            ],
            attributes: [
                'answer' => $this->requireString($record, 'answer'),
                'sort_order' => (int) Arr::get($record, 'sort_order', 0),
                'is_active' => (bool) Arr::get($record, 'is_active', true),
                'metadata' => Arr::get($record, 'metadata', []),
            ],
            dryRun: $dryRun,
        );
    }

    private function importCoupon(array $record, bool $dryRun): array
    {
        return $this->upsert(
            modelClass: Coupon::class,
            unique: ['code' => Str::upper($this->requireString($record, 'code'))],
            attributes: [
                'name' => Arr::get($record, 'name'),
                'discount_type' => Arr::get($record, 'discount_type', 'percentage'),
                'value' => (int) Arr::get($record, 'value', 0),
                'currency' => Arr::get($record, 'currency', 'PLN'),
                'minimum_subtotal_amount' => Arr::has($record, 'minimum_subtotal_amount') ? (int) Arr::get($record, 'minimum_subtotal_amount') : null,
                'usage_limit' => Arr::has($record, 'usage_limit') ? (int) Arr::get($record, 'usage_limit') : null,
                'usage_limit_per_customer' => Arr::has($record, 'usage_limit_per_customer') ? (int) Arr::get($record, 'usage_limit_per_customer') : null,
                'starts_at' => $this->nullableDate(Arr::get($record, 'starts_at')),
                'ends_at' => $this->nullableDate(Arr::get($record, 'ends_at')),
                'is_active' => (bool) Arr::get($record, 'is_active', true),
                'metadata' => Arr::get($record, 'metadata', []),
            ],
            dryRun: $dryRun,
        );
    }

    private function importNewsletterSubscriber(array $record, bool $dryRun): array
    {
        return $this->upsert(
            modelClass: NewsletterSubscriber::class,
            unique: ['email' => Str::lower($this->requireString($record, 'email'))],
            attributes: [
                'first_name' => Arr::get($record, 'first_name'),
                'last_name' => Arr::get($record, 'last_name'),
                'source' => Arr::get($record, 'source'),
                'consented_at' => $this->nullableDate(Arr::get($record, 'consented_at')),
                'unsubscribed_at' => $this->nullableDate(Arr::get($record, 'unsubscribed_at')),
                'is_active' => (bool) Arr::get($record, 'is_active', true),
                'metadata' => Arr::get($record, 'metadata', []),
            ],
            dryRun: $dryRun,
        );
    }

    private function importRedirectRule(array $record, bool $dryRun): array
    {
        $sourcePath = '/' . ltrim($this->requireString($record, 'source_path'), '/');

        return $this->upsert(
            modelClass: RedirectRule::class,
            unique: ['source_path' => $sourcePath],
            attributes: [
                'target_path' => $this->requireString($record, 'target_path'),
                'status_code' => (int) Arr::get($record, 'status_code', 301),
                'is_active' => (bool) Arr::get($record, 'is_active', true),
                'hit_count' => (int) Arr::get($record, 'hit_count', 0),
                'last_hit_at' => $this->nullableDate(Arr::get($record, 'last_hit_at')),
                'metadata' => Arr::get($record, 'metadata', []),
            ],
            dryRun: $dryRun,
        );
    }

    private function importCustomer(array $record, bool $dryRun): array
    {
        $email = Str::lower($this->requireString($record, 'email'));
        $existingUser = User::query()->where('email', $email)->first();

        [$created, $updated] = $this->upsert(
            modelClass: User::class,
            unique: ['email' => $email],
            attributes: [
                'name' => $this->requireString($record, 'name'),
                'password' => Hash::make((string) ($record['password'] ?? Str::random(32))),
                'is_admin' => Arr::has($record, 'is_admin')
                    ? (bool) Arr::get($record, 'is_admin')
                    : (bool) ($existingUser?->is_admin ?? false),
                'email_verified_at' => $this->nullableDate(Arr::get($record, 'email_verified_at')),
            ],
            dryRun: $dryRun,
        );

        if (! $dryRun) {
            $user = User::query()->where('email', $email)->firstOrFail();
            CustomerProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'segment' => Arr::get($record, 'segment', 'regular'),
                    'phone' => Arr::get($record, 'phone'),
                    'completed_orders_count' => (int) Arr::get($record, 'completed_orders_count', 0),
                    'marketing_consent_at' => $this->nullableDate(Arr::get($record, 'marketing_consent_at')),
                    'last_order_at' => $this->nullableDate(Arr::get($record, 'last_order_at')),
                    'metadata' => Arr::get($record, 'metadata', []),
                ],
            );
        }

        return [$created, $updated];
    }

    private function importOrder(array $record, bool $dryRun): array
    {
        $number = $this->requireString($record, 'number');

        if ($dryRun) {
            $this->requireString($record, 'customer_email');

            return Order::query()->where('number', $number)->exists() ? [0, 1] : [1, 0];
        }

        return DB::transaction(function () use ($record, $number): array {
            $user = User::query()->where('email', Str::lower((string) Arr::get($record, 'customer_email')))->first();
            $coupon = filled(Arr::get($record, 'coupon_code'))
                ? Coupon::query()->where('code', Str::upper((string) Arr::get($record, 'coupon_code')))->first()
                : null;

            $exists = Order::query()->where('number', $number)->exists();

            $order = Order::query()->updateOrCreate(
                ['number' => $number],
                [
                    'user_id' => $user?->id,
                    'coupon_id' => $coupon?->id,
                    'status' => Arr::get($record, 'status', 'placed'),
                    'payment_status' => Arr::get($record, 'payment_status', 'pending'),
                    'fulfillment_status' => Arr::get($record, 'fulfillment_status', 'pending'),
                    'currency' => Arr::get($record, 'currency', 'PLN'),
                    'customer_segment' => Arr::get($record, 'customer_segment', 'regular'),
                    'customer_email' => $this->requireString($record, 'customer_email'),
                    'customer_first_name' => Arr::get($record, 'customer_first_name'),
                    'customer_last_name' => Arr::get($record, 'customer_last_name'),
                    'customer_phone' => Arr::get($record, 'customer_phone'),
                    'subtotal_amount' => (int) Arr::get($record, 'subtotal_amount', 0),
                    'discount_amount' => (int) Arr::get($record, 'discount_amount', 0),
                    'shipping_amount' => (int) Arr::get($record, 'shipping_amount', 0),
                    'tax_amount' => (int) Arr::get($record, 'tax_amount', 0),
                    'total_amount' => (int) Arr::get($record, 'total_amount', 0),
                    'shipping_method_code' => Arr::get($record, 'shipping_method_code'),
                    'shipping_method_name' => Arr::get($record, 'shipping_method_name'),
                    'billing_address' => Arr::get($record, 'billing_address'),
                    'shipping_address' => Arr::get($record, 'shipping_address'),
                    'placed_at' => $this->nullableDate(Arr::get($record, 'placed_at')),
                    'notes' => Arr::get($record, 'notes'),
                    'metadata' => Arr::get($record, 'metadata', []),
                ],
            );

            $order->items()->delete();
            $order->paymentTransactions()->delete();
            $order->fulfillmentActions()->delete();

            foreach ((array) Arr::get($record, 'items', []) as $itemRecord) {
                $product = filled(Arr::get($itemRecord, 'product_slug'))
                    ? Product::query()->where('slug', (string) Arr::get($itemRecord, 'product_slug'))->first()
                    : null;

                $item = OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $product?->id,
                    'product_type' => Arr::get($itemRecord, 'product_type', 'physical'),
                    'sku' => Arr::get($itemRecord, 'sku'),
                    'name' => Arr::get($itemRecord, 'name'),
                    'quantity' => (int) Arr::get($itemRecord, 'quantity', 1),
                    'unit_price_amount' => (int) Arr::get($itemRecord, 'unit_price_amount', 0),
                    'regular_unit_price_amount' => (int) Arr::get($itemRecord, 'regular_unit_price_amount', 0),
                    'discount_amount' => (int) Arr::get($itemRecord, 'discount_amount', 0),
                    'tax_amount' => (int) Arr::get($itemRecord, 'tax_amount', 0),
                    'total_amount' => (int) Arr::get($itemRecord, 'total_amount', 0),
                    'metadata' => Arr::get($itemRecord, 'metadata', []),
                ]);

                foreach ((array) Arr::get($itemRecord, 'fulfillment_actions', []) as $actionRecord) {
                    OrderFulfillmentAction::query()->create([
                        'order_id' => $order->id,
                        'order_item_id' => $item->id,
                        'action_type' => Arr::get($actionRecord, 'action_type', 'physical_shipping'),
                        'status' => Arr::get($actionRecord, 'status', 'pending'),
                        'title' => Arr::get($actionRecord, 'title'),
                        'instructions' => Arr::get($actionRecord, 'instructions'),
                        'due_at' => $this->nullableDate(Arr::get($actionRecord, 'due_at')),
                        'completed_at' => $this->nullableDate(Arr::get($actionRecord, 'completed_at')),
                        'metadata' => Arr::get($actionRecord, 'metadata', []),
                    ]);
                }
            }

            foreach ((array) Arr::get($record, 'payment_transactions', []) as $transactionRecord) {
                PaymentTransaction::query()->create([
                    'order_id' => $order->id,
                    'provider' => Arr::get($transactionRecord, 'provider'),
                    'status' => Arr::get($transactionRecord, 'status', 'pending'),
                    'amount' => (int) Arr::get($transactionRecord, 'amount', 0),
                    'currency' => Arr::get($transactionRecord, 'currency', $order->currency),
                    'external_session_id' => Arr::get($transactionRecord, 'external_session_id'),
                    'redirect_url' => Arr::get($transactionRecord, 'redirect_url'),
                    'error_code' => Arr::get($transactionRecord, 'error_code'),
                    'error_message' => Arr::get($transactionRecord, 'error_message'),
                    'request_payload' => Arr::get($transactionRecord, 'request_payload', []),
                    'response_payload' => Arr::get($transactionRecord, 'response_payload', []),
                    'initiated_at' => $this->nullableDate(Arr::get($transactionRecord, 'initiated_at')),
                    'confirmed_at' => $this->nullableDate(Arr::get($transactionRecord, 'confirmed_at')),
                    'failed_at' => $this->nullableDate(Arr::get($transactionRecord, 'failed_at')),
                    'metadata' => Arr::get($transactionRecord, 'metadata', []),
                ]);
            }

            foreach ((array) Arr::get($record, 'fulfillment_actions', []) as $actionRecord) {
                OrderFulfillmentAction::query()->create([
                    'order_id' => $order->id,
                    'order_item_id' => null,
                    'action_type' => Arr::get($actionRecord, 'action_type', 'physical_shipping'),
                    'status' => Arr::get($actionRecord, 'status', 'pending'),
                    'title' => Arr::get($actionRecord, 'title'),
                    'instructions' => Arr::get($actionRecord, 'instructions'),
                    'due_at' => $this->nullableDate(Arr::get($actionRecord, 'due_at')),
                    'completed_at' => $this->nullableDate(Arr::get($actionRecord, 'completed_at')),
                    'metadata' => Arr::get($actionRecord, 'metadata', []),
                ]);
            }

            return $exists ? [0, 1] : [1, 0];
        });
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     * @param  array<string, mixed>  $record
     * @param  array<string, mixed>  $attributes
     * @return array{0:int,1:int}
     */
    private function upsertBySlug(string $modelClass, array $record, bool $dryRun, array $attributes): array
    {
        return $this->upsert(
            modelClass: $modelClass,
            unique: ['slug' => $this->requireString($record, 'slug')],
            attributes: $attributes,
            dryRun: $dryRun,
        );
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     * @param  array<string, mixed>  $unique
     * @param  array<string, mixed>  $attributes
     * @return array{0:int,1:int}
     */
    private function upsert(string $modelClass, array $unique, array $attributes, bool $dryRun): array
    {
        $exists = $modelClass::query()->where($unique)->exists();

        if ($dryRun) {
            return $exists ? [0, 1] : [1, 0];
        }

        $modelClass::query()->updateOrCreate($unique, $attributes);

        return $exists ? [0, 1] : [1, 0];
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function requireString(array $record, string $key): string
    {
        $value = Arr::get($record, $key);

        if (! is_string($value) || blank($value)) {
            throw new InvalidArgumentException('Brakuje wymaganego pola: ' . $key);
        }

        return $value;
    }

    private function nullableDate(mixed $value): mixed
    {
        return filled($value) ? $value : null;
    }
}