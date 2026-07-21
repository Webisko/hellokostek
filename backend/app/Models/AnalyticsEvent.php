<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class AnalyticsEvent extends Model
{
    use HasFactory;

    public const EVENT_PAGE_VIEW = 'page_view';

    public const EVENT_VIEW_ITEM_LIST = 'view_item_list';

    public const EVENT_SELECT_ITEM = 'select_item';

    public const EVENT_VIEW_ITEM = 'view_item';

    public const EVENT_ADD_TO_CART = 'add_to_cart';

    public const EVENT_VIEW_CART = 'view_cart';

    public const EVENT_BEGIN_CHECKOUT = 'begin_checkout';

    public const EVENT_PURCHASE = 'purchase';

    public const EVENT_GENERATE_LEAD = 'generate_lead';

    protected $fillable = [
        'event_name',
        'event_id',
        'deduplication_key',
        'occurred_at',
        'environment',
        'hostname',
        'pathname',
        'page_type',
        'referrer_host',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'visit_id',
        'pageview_id',
        'currency',
        'value',
        'properties',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'value' => 'decimal:2',
            'properties' => 'array',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function supportedEventNames(): array
    {
        return [
            self::EVENT_PAGE_VIEW,
            self::EVENT_VIEW_ITEM_LIST,
            self::EVENT_SELECT_ITEM,
            self::EVENT_VIEW_ITEM,
            self::EVENT_ADD_TO_CART,
            self::EVENT_VIEW_CART,
            self::EVENT_BEGIN_CHECKOUT,
            self::EVENT_PURCHASE,
            self::EVENT_GENERATE_LEAD,
        ];
    }

    public static function deduplicationKeyFor(string $environment, string $eventName, string $eventId, array $properties = []): string
    {
        $normalizedEnvironment = mb_strtolower(trim($environment));
        $normalizedEventName = mb_strtolower(trim($eventName));

        if ($normalizedEventName === self::EVENT_PURCHASE) {
            $orderNumber = Arr::get($properties, 'order_number');

            if (filled($orderNumber)) {
                return sprintf('%s:%s:order:%s', $normalizedEnvironment, $normalizedEventName, $orderNumber);
            }
        }

        if ($normalizedEventName === self::EVENT_GENERATE_LEAD) {
            $submissionId = Arr::get($properties, 'submission_id');

            if (filled($submissionId)) {
                return sprintf('%s:%s:submission:%s', $normalizedEnvironment, $normalizedEventName, $submissionId);
            }
        }

        return sprintf('%s:%s:event:%s', $normalizedEnvironment, $normalizedEventName, $eventId);
    }
}