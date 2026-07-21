<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionnaireSubmission extends Model
{
    use HasFactory;

    public const EMAIL_STATUS_PENDING = 'pending';

    public const EMAIL_STATUS_SENT = 'sent';

    public const EMAIL_STATUS_FAILED = 'failed';

    public const EMAIL_STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'questionnaire_key',
        'name',
        'email',
        'source',
        'consented_to_marketing',
        'consented_at',
        'answers',
        'recommended_products',
        'coupon_code',
        'result_email_status',
        'admin_notification_status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'consented_to_marketing' => 'boolean',
            'consented_at' => 'datetime',
            'answers' => 'array',
            'recommended_products' => 'array',
            'metadata' => 'array',
        ];
    }

    public function recommendedProductLabels(): array
    {
        return collect($this->recommended_products ?? [])
            ->map(function (mixed $product): ?string {
                if (is_string($product)) {
                    return filled($product) ? $product : null;
                }

                if (is_array($product)) {
                    $label = $product['name'] ?? $product['slug'] ?? null;

                    return filled($label) ? (string) $label : null;
                }

                return null;
            })
            ->filter(fn (?string $label): bool => filled($label))
            ->values()
            ->all();
    }

    public static function emailStatusOptions(): array
    {
        return [
            self::EMAIL_STATUS_PENDING => 'Oczekuje',
            self::EMAIL_STATUS_SENT => 'Wyslano',
            self::EMAIL_STATUS_FAILED => 'Blad wysylki',
            self::EMAIL_STATUS_SKIPPED => 'Pominieto',
        ];
    }

    public static function emailStatusLabel(?string $status): string
    {
        if (blank($status)) {
            return '-';
        }

        return self::emailStatusOptions()[$status] ?? (string) $status;
    }

    public static function emailStatusColor(?string $status): string
    {
        return match ($status) {
            self::EMAIL_STATUS_SENT => 'success',
            self::EMAIL_STATUS_FAILED => 'danger',
            self::EMAIL_STATUS_SKIPPED => 'warning',
            self::EMAIL_STATUS_PENDING => 'gray',
            default => 'gray',
        };
    }

    public function recommendedProductsForCustomerEmail(): array
    {
        $baseUrl = rtrim((string) config('services.storefront.url', config('app.url')), '/');

        return collect($this->recommended_products ?? [])
            ->map(function (mixed $product) use ($baseUrl): ?array {
                if (is_string($product)) {
                    return filled($product)
                        ? [
                            'label' => $product,
                            'url' => null,
                        ]
                        : null;
                }

                if (! is_array($product)) {
                    return null;
                }

                $label = $product['name'] ?? $product['slug'] ?? null;
                $slug = $product['slug'] ?? null;

                if (! filled($label)) {
                    return null;
                }

                return [
                    'label' => (string) $label,
                    'url' => filled($slug) ? $baseUrl . '/produkt/' . $slug : null,
                ];
            })
            ->filter(fn (?array $product): bool => is_array($product) && filled($product['label'] ?? null))
            ->values()
            ->all();
    }
}