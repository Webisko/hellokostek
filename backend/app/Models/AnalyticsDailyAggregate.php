<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsDailyAggregate extends Model
{
    use HasFactory;

    public const REPORT_PAGEVIEWS_TOTAL = 'pageviews_total';

    public const REPORT_VISITS_TOTAL = 'visits_total';


    public const REPORT_CHECKOUT_STARTS_TOTAL = 'checkout_starts_total';

    public const REPORT_PURCHASES_TOTAL = 'purchases_total';


    public const REPORT_LANDING_PAGE_VIEWS = 'landing_page_views';

    public const REPORT_PRODUCT_PAGE_VIEWS = 'product_page_views';

    public const REPORT_REFERRER_HOST_VIEWS = 'referrer_host_views';

    public const REPORT_UTM_CAMPAIGN_VIEWS = 'utm_campaign_views';

    protected $fillable = [
        'aggregate_date',
        'environment',
        'report_key',
        'dimension',
        'dimension_value',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'aggregate_date' => 'date',
            'value' => 'integer',
        ];
    }
}