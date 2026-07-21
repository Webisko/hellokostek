# Database Schema Reference

This file is a cache of the database schema for the Laravel Filament CMS Boilerplate project. Generated on 2026-07-15 13:47:52.

## Table: `admin_activity_logs`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `actor_id` | `INTEGER` | Yes | `NULL` |  |
| `subject_type` | `varchar` | No | `NULL` |  |
| `subject_id` | `INTEGER` | No | `NULL` |  |
| `event` | `varchar` | No | `NULL` |  |
| `summary` | `varchar` | No | `NULL` |  |
| `old_values` | `TEXT` | Yes | `NULL` |  |
| `new_values` | `TEXT` | Yes | `NULL` |  |
| `metadata` | `TEXT` | Yes | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `admin_activity_logs_subject_type_subject_id_index` | No | `subject_type`, `subject_id` |

---

## Table: `analytics_daily_aggregates`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `aggregate_date` | `date` | No | `NULL` |  |
| `environment` | `varchar` | No | `NULL` |  |
| `report_key` | `varchar` | No | `NULL` |  |
| `dimension` | `varchar` | No | `''` |  |
| `dimension_value` | `varchar` | No | `''` |  |
| `value` | `INTEGER` | No | `'0'` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `analytics_daily_aggregates_environment_aggregate_date_index` | No | `environment`, `aggregate_date` |
| `analytics_daily_aggregates_report_key_aggregate_date_index` | No | `report_key`, `aggregate_date` |
| `analytics_daily_aggregates_unique` | Yes | `aggregate_date`, `environment`, `report_key`, `dimension`, `dimension_value` |

---

## Table: `analytics_events`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `event_name` | `varchar` | No | `NULL` |  |
| `event_id` | `varchar` | No | `NULL` |  |
| `deduplication_key` | `varchar` | No | `NULL` |  |
| `occurred_at` | `datetime` | Yes | `NULL` |  |
| `environment` | `varchar` | No | `NULL` |  |
| `hostname` | `varchar` | No | `NULL` |  |
| `pathname` | `varchar` | No | `NULL` |  |
| `page_type` | `varchar` | No | `NULL` |  |
| `referrer_host` | `varchar` | Yes | `NULL` |  |
| `utm_source` | `varchar` | Yes | `NULL` |  |
| `utm_medium` | `varchar` | Yes | `NULL` |  |
| `utm_campaign` | `varchar` | Yes | `NULL` |  |
| `utm_content` | `varchar` | Yes | `NULL` |  |
| `utm_term` | `varchar` | Yes | `NULL` |  |
| `visit_id` | `varchar` | Yes | `NULL` |  |
| `pageview_id` | `varchar` | Yes | `NULL` |  |
| `currency` | `varchar` | Yes | `NULL` |  |
| `value` | `numeric` | Yes | `NULL` |  |
| `properties` | `TEXT` | Yes | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `analytics_events_deduplication_key_unique` | Yes | `deduplication_key` |
| `analytics_events_referrer_host_occurred_at_index` | No | `referrer_host`, `occurred_at` |
| `analytics_events_pathname_occurred_at_index` | No | `pathname`, `occurred_at` |
| `analytics_events_page_type_occurred_at_index` | No | `page_type`, `occurred_at` |
| `analytics_events_environment_event_name_occurred_at_index` | No | `environment`, `event_name`, `occurred_at` |

---

## Table: `back_in_stock_subscriptions`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `email` | `varchar` | No | `NULL` |  |
| `product_id` | `INTEGER` | No | `NULL` |  |
| `product_variant_id` | `INTEGER` | Yes | `NULL` |  |
| `status` | `varchar` | No | `'pending'` |  |
| `notified_at` | `datetime` | Yes | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `back_in_stock_subscriptions_email_status_index` | No | `email`, `status` |

---

## Table: `blog_posts`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `slug` | `varchar` | No | `NULL` |  |
| `title` | `TEXT` | No | `NULL` |  |
| `excerpt` | `TEXT` | Yes | `NULL` |  |
| `content` | `TEXT` | Yes | `NULL` |  |
| `author_name` | `varchar` | Yes | `NULL` |  |
| `cover_image_url` | `varchar` | Yes | `NULL` |  |
| `seo_title` | `varchar` | Yes | `NULL` |  |
| `seo_description` | `TEXT` | Yes | `NULL` |  |
| `is_active` | `tinyint(1)` | No | `'1'` |  |
| `published_at` | `datetime` | Yes | `NULL` |  |
| `metadata` | `TEXT` | Yes | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |
| `is_noindex` | `tinyint(1)` | No | `'0'` |  |
| `deleted_at` | `datetime` | Yes | `NULL` |  |
| `is_ai_generated` | `tinyint(1)` | No | `'0'` |  |
| `ai_disclosure_text` | `TEXT` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `blog_posts_slug_unique` | Yes | `slug` |
| `blog_posts_is_active_published_at_index` | No | `is_active`, `published_at` |

---

## Table: `breezy_sessions`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `authenticatable_type` | `varchar` | No | `NULL` |  |
| `authenticatable_id` | `INTEGER` | No | `NULL` |  |
| `panel_id` | `varchar` | Yes | `NULL` |  |
| `two_factor_secret` | `TEXT` | Yes | `NULL` |  |
| `two_factor_recovery_codes` | `TEXT` | Yes | `NULL` |  |
| `two_factor_confirmed_at` | `datetime` | Yes | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `breezy_sessions_authenticatable_type_authenticatable_id_index` | No | `authenticatable_type`, `authenticatable_id` |

---

## Table: `cache`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `key` | `varchar` | No | `NULL` | PK |
| `value` | `TEXT` | No | `NULL` |  |
| `expiration` | `INTEGER` | No | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `cache_expiration_index` | No | `expiration` |
| `sqlite_autoindex_cache_1` | Yes | `key` |

---

## Table: `cache_locks`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `key` | `varchar` | No | `NULL` | PK |
| `owner` | `varchar` | No | `NULL` |  |
| `expiration` | `INTEGER` | No | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `cache_locks_expiration_index` | No | `expiration` |
| `sqlite_autoindex_cache_locks_1` | Yes | `key` |

---

## Table: `cart_items`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `cart_id` | `INTEGER` | No | `NULL` |  |
| `product_id` | `INTEGER` | No | `NULL` |  |
| `product_variant_id` | `INTEGER` | Yes | `NULL` |  |
| `quantity` | `INTEGER` | No | `'1'` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

---

## Table: `carts`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `user_id` | `INTEGER` | Yes | `NULL` |  |
| `session_token` | `varchar` | Yes | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `carts_session_token_index` | No | `session_token` |

---

## Table: `contact_inquiries`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `name` | `varchar` | No | `NULL` |  |
| `email` | `varchar` | No | `NULL` |  |
| `phone` | `varchar` | Yes | `NULL` |  |
| `subject` | `varchar` | Yes | `NULL` |  |
| `message` | `TEXT` | No | `NULL` |  |
| `payload` | `TEXT` | Yes | `NULL` |  |
| `status` | `varchar` | No | `'new'` |  |
| `admin_notes` | `TEXT` | Yes | `NULL` |  |
| `ip_address` | `varchar` | Yes | `NULL` |  |
| `user_agent` | `varchar` | Yes | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `contact_inquiries_created_at_index` | No | `created_at` |
| `contact_inquiries_status_index` | No | `status` |

---

## Table: `content_pages`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `slug` | `varchar` | No | `NULL` |  |
| `title` | `TEXT` | No | `NULL` |  |
| `excerpt` | `TEXT` | Yes | `NULL` |  |
| `content` | `TEXT` | Yes | `NULL` |  |
| `template` | `varchar` | No | `'default'` |  |
| `seo_title` | `varchar` | Yes | `NULL` |  |
| `seo_description` | `TEXT` | Yes | `NULL` |  |
| `is_active` | `tinyint(1)` | No | `'1'` |  |
| `published_at` | `datetime` | Yes | `NULL` |  |
| `metadata` | `TEXT` | Yes | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |
| `hero_image_path` | `varchar` | Yes | `NULL` |  |
| `sort_order` | `INTEGER` | No | `'0'` |  |
| `is_noindex` | `tinyint(1)` | No | `'0'` |  |
| `deleted_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `content_pages_slug_unique` | Yes | `slug` |
| `content_pages_is_active_sort_order_index` | No | `is_active`, `sort_order` |
| `content_pages_is_active_published_at_index` | No | `is_active`, `published_at` |

---

## Table: `cookie_consents`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `consent_token` | `varchar` | No | `NULL` |  |
| `consent_choices` | `TEXT` | No | `NULL` |  |
| `banner_version` | `varchar` | No | `NULL` |  |
| `user_agent` | `varchar` | Yes | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `cookie_consents_consent_token_index` | No | `consent_token` |

---

## Table: `coupons`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `code` | `varchar` | No | `NULL` |  |
| `name` | `varchar` | Yes | `NULL` |  |
| `discount_type` | `varchar` | No | `'percentage'` |  |
| `value` | `INTEGER` | No | `NULL` |  |
| `currency` | `varchar` | No | `'PLN'` |  |
| `minimum_subtotal_amount` | `INTEGER` | Yes | `NULL` |  |
| `usage_limit` | `INTEGER` | Yes | `NULL` |  |
| `usage_limit_per_customer` | `INTEGER` | Yes | `NULL` |  |
| `starts_at` | `datetime` | Yes | `NULL` |  |
| `ends_at` | `datetime` | Yes | `NULL` |  |
| `is_active` | `tinyint(1)` | No | `'1'` |  |
| `metadata` | `TEXT` | Yes | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |
| `deleted_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `coupons_code_unique` | Yes | `code` |
| `coupons_is_active_starts_at_ends_at_index` | No | `is_active`, `starts_at`, `ends_at` |

---

## Table: `customer_addresses`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `user_id` | `INTEGER` | No | `NULL` |  |
| `name` | `varchar` | No | `'Home'` |  |
| `company_name` | `varchar` | Yes | `NULL` |  |
| `nip` | `varchar` | Yes | `NULL` |  |
| `first_name` | `varchar` | No | `NULL` |  |
| `last_name` | `varchar` | No | `NULL` |  |
| `address_line_1` | `varchar` | No | `NULL` |  |
| `address_line_2` | `varchar` | Yes | `NULL` |  |
| `postal_code` | `varchar` | No | `NULL` |  |
| `city` | `varchar` | No | `NULL` |  |
| `country_code` | `varchar` | No | `'PL'` |  |
| `phone` | `varchar` | Yes | `NULL` |  |
| `is_default_shipping` | `tinyint(1)` | No | `'0'` |  |
| `is_default_billing` | `tinyint(1)` | No | `'0'` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `customer_addresses_user_id_is_default_billing_index` | No | `user_id`, `is_default_billing` |
| `customer_addresses_user_id_is_default_shipping_index` | No | `user_id`, `is_default_shipping` |

---

## Table: `customer_profiles`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `user_id` | `INTEGER` | No | `NULL` |  |
| `segment` | `varchar` | No | `'regular'` |  |
| `phone` | `varchar` | Yes | `NULL` |  |
| `completed_orders_count` | `INTEGER` | No | `'0'` |  |
| `marketing_consent_at` | `datetime` | Yes | `NULL` |  |
| `last_order_at` | `datetime` | Yes | `NULL` |  |
| `metadata` | `TEXT` | Yes | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |
| `deleted_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `customer_profiles_user_id_unique` | Yes | `user_id` |
| `customer_profiles_segment_completed_orders_count_index` | No | `segment`, `completed_orders_count` |

---

## Table: `email_templates`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `key` | `varchar` | No | `NULL` |  |
| `name` | `varchar` | No | `NULL` |  |
| `subject` | `varchar` | No | `NULL` |  |
| `body_html` | `TEXT` | No | `NULL` |  |
| `placeholders` | `TEXT` | Yes | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `email_templates_key_unique` | Yes | `key` |

---

## Table: `failed_jobs`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `uuid` | `varchar` | No | `NULL` |  |
| `connection` | `TEXT` | No | `NULL` |  |
| `queue` | `TEXT` | No | `NULL` |  |
| `payload` | `TEXT` | No | `NULL` |  |
| `exception` | `TEXT` | No | `NULL` |  |
| `failed_at` | `datetime` | No | `CURRENT_TIMESTAMP` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `failed_jobs_uuid_unique` | Yes | `uuid` |

---

## Table: `faq_items`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `question` | `varchar` | No | `NULL` |  |
| `answer` | `TEXT` | No | `NULL` |  |
| `group_name` | `varchar` | Yes | `NULL` |  |
| `sort_order` | `INTEGER` | No | `'0'` |  |
| `is_active` | `tinyint(1)` | No | `'1'` |  |
| `metadata` | `TEXT` | Yes | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `faq_items_is_active_sort_order_index` | No | `is_active`, `sort_order` |

---

## Table: `integration_logs`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `order_id` | `INTEGER` | Yes | `NULL` |  |
| `integration` | `varchar` | No | `NULL` |  |
| `event` | `varchar` | No | `NULL` |  |
| `direction` | `varchar` | No | `'outgoing'` |  |
| `status` | `varchar` | No | `'info'` |  |
| `external_reference` | `varchar` | Yes | `NULL` |  |
| `error_message` | `TEXT` | Yes | `NULL` |  |
| `request_payload` | `TEXT` | Yes | `NULL` |  |
| `response_payload` | `TEXT` | Yes | `NULL` |  |
| `metadata` | `TEXT` | Yes | `NULL` |  |
| `occurred_at` | `datetime` | Yes | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `integration_logs_direction_occurred_at_index` | No | `direction`, `occurred_at` |
| `integration_logs_order_id_created_at_index` | No | `order_id`, `created_at` |
| `integration_logs_integration_status_index` | No | `integration`, `status` |

---

## Table: `invoices`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `order_id` | `INTEGER` | No | `NULL` |  |
| `number` | `varchar` | No | `NULL` |  |
| `issue_date` | `date` | No | `NULL` |  |
| `due_date` | `date` | No | `NULL` |  |
| `total_amount` | `INTEGER` | No | `NULL` |  |
| `tax_amount` | `INTEGER` | No | `NULL` |  |
| `pdf_path` | `varchar` | Yes | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `invoices_number_unique` | Yes | `number` |

---

## Table: `job_batches`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `varchar` | No | `NULL` | PK |
| `name` | `varchar` | No | `NULL` |  |
| `total_jobs` | `INTEGER` | No | `NULL` |  |
| `pending_jobs` | `INTEGER` | No | `NULL` |  |
| `failed_jobs` | `INTEGER` | No | `NULL` |  |
| `failed_job_ids` | `TEXT` | No | `NULL` |  |
| `options` | `TEXT` | Yes | `NULL` |  |
| `cancelled_at` | `INTEGER` | Yes | `NULL` |  |
| `created_at` | `INTEGER` | No | `NULL` |  |
| `finished_at` | `INTEGER` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `sqlite_autoindex_job_batches_1` | Yes | `id` |

---

## Table: `jobs`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `queue` | `varchar` | No | `NULL` |  |
| `payload` | `TEXT` | No | `NULL` |  |
| `attempts` | `INTEGER` | No | `NULL` |  |
| `reserved_at` | `INTEGER` | Yes | `NULL` |  |
| `available_at` | `INTEGER` | No | `NULL` |  |
| `created_at` | `INTEGER` | No | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `jobs_queue_index` | No | `queue` |

---

## Table: `migrations`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `migration` | `varchar` | No | `NULL` |  |
| `batch` | `INTEGER` | No | `NULL` |  |

---

## Table: `newsletter_campaigns`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `subject` | `varchar` | No | `NULL` |  |
| `body_html` | `TEXT` | No | `NULL` |  |
| `status` | `varchar` | No | `'draft'` |  |
| `sent_to_count` | `INTEGER` | No | `'0'` |  |
| `sent_at` | `datetime` | Yes | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

---

## Table: `newsletter_subscribers`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `email` | `varchar` | No | `NULL` |  |
| `first_name` | `varchar` | Yes | `NULL` |  |
| `last_name` | `varchar` | Yes | `NULL` |  |
| `source` | `varchar` | Yes | `NULL` |  |
| `consented_at` | `datetime` | Yes | `NULL` |  |
| `unsubscribed_at` | `datetime` | Yes | `NULL` |  |
| `is_active` | `tinyint(1)` | No | `'1'` |  |
| `metadata` | `TEXT` | Yes | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |
| `status` | `varchar` | No | `'pending'` |  |
| `double_opt_in_token` | `varchar` | Yes | `NULL` |  |
| `double_opt_in_ip` | `varchar` | Yes | `NULL` |  |
| `double_opt_in_confirmed_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `newsletter_subscribers_double_opt_in_token_index` | No | `double_opt_in_token` |
| `newsletter_subscribers_status_index` | No | `status` |
| `newsletter_subscribers_email_unique` | Yes | `email` |
| `newsletter_subscribers_is_active_consented_at_index` | No | `is_active`, `consented_at` |

---

## Table: `order_fulfillment_actions`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `order_id` | `INTEGER` | No | `NULL` |  |
| `order_item_id` | `INTEGER` | Yes | `NULL` |  |
| `action_type` | `varchar` | No | `NULL` |  |
| `status` | `varchar` | No | `'pending'` |  |
| `title` | `varchar` | No | `NULL` |  |
| `instructions` | `TEXT` | No | `NULL` |  |
| `due_at` | `datetime` | Yes | `NULL` |  |
| `completed_at` | `datetime` | Yes | `NULL` |  |
| `metadata` | `TEXT` | Yes | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `order_fulfillment_actions_order_item_id_action_type_unique` | Yes | `order_item_id`, `action_type` |
| `order_fulfillment_actions_action_type_status_index` | No | `action_type`, `status` |
| `order_fulfillment_actions_order_id_status_index` | No | `order_id`, `status` |

---

## Table: `order_items`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `order_id` | `INTEGER` | No | `NULL` |  |
| `product_id` | `INTEGER` | Yes | `NULL` |  |
| `product_type` | `varchar` | No | `NULL` |  |
| `sku` | `varchar` | Yes | `NULL` |  |
| `name` | `varchar` | No | `NULL` |  |
| `quantity` | `INTEGER` | No | `NULL` |  |
| `unit_price_amount` | `INTEGER` | No | `NULL` |  |
| `regular_unit_price_amount` | `INTEGER` | Yes | `NULL` |  |
| `discount_amount` | `INTEGER` | No | `'0'` |  |
| `tax_amount` | `INTEGER` | No | `'0'` |  |
| `total_amount` | `INTEGER` | No | `NULL` |  |
| `metadata` | `TEXT` | Yes | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |
| `product_variant_id` | `INTEGER` | Yes | `NULL` |  |

---

## Table: `order_return_items`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `order_return_id` | `INTEGER` | No | `NULL` |  |
| `order_item_id` | `INTEGER` | No | `NULL` |  |
| `quantity` | `INTEGER` | No | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

---

## Table: `order_returns`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `order_id` | `INTEGER` | No | `NULL` |  |
| `user_id` | `INTEGER` | Yes | `NULL` |  |
| `return_number` | `varchar` | No | `NULL` |  |
| `status` | `varchar` | No | `'pending'` |  |
| `reason` | `TEXT` | No | `NULL` |  |
| `refund_amount` | `INTEGER` | Yes | `NULL` |  |
| `tracking_number` | `varchar` | Yes | `NULL` |  |
| `metadata` | `TEXT` | Yes | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |
| `deleted_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `order_returns_user_id_index` | No | `user_id` |
| `order_returns_return_number_unique` | Yes | `return_number` |
| `order_returns_order_id_status_index` | No | `order_id`, `status` |

---

## Table: `orders`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `number` | `varchar` | No | `NULL` |  |
| `user_id` | `INTEGER` | Yes | `NULL` |  |
| `coupon_id` | `INTEGER` | Yes | `NULL` |  |
| `status` | `varchar` | No | `'draft'` |  |
| `payment_status` | `varchar` | No | `'pending'` |  |
| `fulfillment_status` | `varchar` | No | `'pending'` |  |
| `currency` | `varchar` | No | `'PLN'` |  |
| `customer_segment` | `varchar` | No | `'regular'` |  |
| `customer_email` | `varchar` | No | `NULL` |  |
| `customer_first_name` | `varchar` | No | `NULL` |  |
| `customer_last_name` | `varchar` | No | `NULL` |  |
| `customer_phone` | `varchar` | Yes | `NULL` |  |
| `subtotal_amount` | `INTEGER` | No | `'0'` |  |
| `discount_amount` | `INTEGER` | No | `'0'` |  |
| `shipping_amount` | `INTEGER` | No | `'0'` |  |
| `tax_amount` | `INTEGER` | No | `'0'` |  |
| `total_amount` | `INTEGER` | No | `'0'` |  |
| `shipping_method_code` | `varchar` | Yes | `NULL` |  |
| `shipping_method_name` | `varchar` | Yes | `NULL` |  |
| `billing_address` | `TEXT` | Yes | `NULL` |  |
| `shipping_address` | `TEXT` | Yes | `NULL` |  |
| `placed_at` | `datetime` | Yes | `NULL` |  |
| `notes` | `TEXT` | Yes | `NULL` |  |
| `metadata` | `TEXT` | Yes | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |
| `billing_company_name` | `varchar` | Yes | `NULL` |  |
| `billing_nip` | `varchar` | Yes | `NULL` |  |
| `wants_invoice` | `tinyint(1)` | No | `'0'` |  |
| `tracking_number` | `varchar` | Yes | `NULL` |  |
| `carrier` | `varchar` | Yes | `NULL` |  |
| `shipped_at` | `datetime` | Yes | `NULL` |  |
| `is_privileged_entrepreneur` | `tinyint(1)` | No | `'0'` |  |
| `deleted_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `orders_number_unique` | Yes | `number` |
| `orders_customer_email_index` | No | `customer_email` |
| `orders_payment_status_fulfillment_status_index` | No | `payment_status`, `fulfillment_status` |
| `orders_status_placed_at_index` | No | `status`, `placed_at` |

---

## Table: `passkeys`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `authenticatable_type` | `varchar` | No | `NULL` |  |
| `authenticatable_id` | `INTEGER` | No | `NULL` |  |
| `panel_id` | `varchar` | Yes | `NULL` |  |
| `name` | `TEXT` | No | `NULL` |  |
| `credential_id` | `TEXT` | No | `NULL` |  |
| `data` | `TEXT` | No | `NULL` |  |
| `last_used_at` | `datetime` | Yes | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `passkeys_authenticatable_type_authenticatable_id_index` | No | `authenticatable_type`, `authenticatable_id` |

---

## Table: `password_reset_tokens`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `email` | `varchar` | No | `NULL` | PK |
| `token` | `varchar` | No | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `sqlite_autoindex_password_reset_tokens_1` | Yes | `email` |

---

## Table: `payment_transactions`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `order_id` | `INTEGER` | No | `NULL` |  |
| `provider` | `varchar` | No | `NULL` |  |
| `status` | `varchar` | No | `NULL` |  |
| `amount` | `INTEGER` | No | `NULL` |  |
| `currency` | `varchar` | No | `'PLN'` |  |
| `external_session_id` | `varchar` | Yes | `NULL` |  |
| `redirect_url` | `TEXT` | Yes | `NULL` |  |
| `error_code` | `varchar` | Yes | `NULL` |  |
| `error_message` | `TEXT` | Yes | `NULL` |  |
| `request_payload` | `TEXT` | Yes | `NULL` |  |
| `response_payload` | `TEXT` | Yes | `NULL` |  |
| `initiated_at` | `datetime` | Yes | `NULL` |  |
| `confirmed_at` | `datetime` | Yes | `NULL` |  |
| `failed_at` | `datetime` | Yes | `NULL` |  |
| `metadata` | `TEXT` | Yes | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `payment_transactions_provider_external_session_id_unique` | Yes | `provider`, `external_session_id` |
| `payment_transactions_order_id_created_at_index` | No | `order_id`, `created_at` |
| `payment_transactions_provider_status_index` | No | `provider`, `status` |

---

## Table: `personal_access_tokens`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `tokenable_type` | `varchar` | No | `NULL` |  |
| `tokenable_id` | `INTEGER` | No | `NULL` |  |
| `name` | `TEXT` | No | `NULL` |  |
| `token` | `varchar` | No | `NULL` |  |
| `abilities` | `TEXT` | Yes | `NULL` |  |
| `last_used_at` | `datetime` | Yes | `NULL` |  |
| `expires_at` | `datetime` | Yes | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `personal_access_tokens_expires_at_index` | No | `expires_at` |
| `personal_access_tokens_token_unique` | Yes | `token` |
| `personal_access_tokens_tokenable_type_tokenable_id_index` | No | `tokenable_type`, `tokenable_id` |

---

## Table: `product_attribute_product`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `product_id` | `INTEGER` | No | `NULL` |  |
| `product_attribute_id` | `INTEGER` | No | `NULL` |  |
| `value` | `TEXT` | Yes | `NULL` |  |
| `sort_order` | `INTEGER` | No | `'0'` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `product_attribute_product_product_id_sort_order_index` | No | `product_id`, `sort_order` |
| `product_attribute_product_product_id_product_attribute_id_unique` | Yes | `product_id`, `product_attribute_id` |

---

## Table: `product_attribute_product_category`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `product_attribute_id` | `INTEGER` | No | `NULL` | PK |
| `product_category_id` | `INTEGER` | No | `NULL` | PK |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `sqlite_autoindex_product_attribute_product_category_1` | Yes | `product_attribute_id`, `product_category_id` |

---

## Table: `product_attributes`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `slug` | `varchar` | No | `NULL` |  |
| `name` | `varchar` | No | `NULL` |  |
| `value_type` | `varchar` | No | `'text'` |  |
| `sort_order` | `INTEGER` | No | `'0'` |  |
| `is_active` | `tinyint(1)` | No | `'1'` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `product_attributes_slug_unique` | Yes | `slug` |
| `product_attributes_is_active_sort_order_index` | No | `is_active`, `sort_order` |

---

## Table: `product_bundle_items`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `bundle_product_id` | `INTEGER` | No | `NULL` |  |
| `product_id` | `INTEGER` | No | `NULL` |  |
| `quantity` | `INTEGER` | No | `'1'` |  |
| `sort_order` | `INTEGER` | No | `'0'` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |
| `product_variant_id` | `INTEGER` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `bundle_items_unique` | Yes | `bundle_product_id`, `product_id`, `product_variant_id` |

---

## Table: `product_categories`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `slug` | `varchar` | No | `NULL` |  |
| `name` | `TEXT` | No | `NULL` |  |
| `description` | `TEXT` | Yes | `NULL` |  |
| `seo_title` | `varchar` | Yes | `NULL` |  |
| `seo_description` | `TEXT` | Yes | `NULL` |  |
| `sort_order` | `INTEGER` | No | `'0'` |  |
| `is_active` | `tinyint(1)` | No | `'1'` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |
| `is_noindex` | `tinyint(1)` | No | `'0'` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `product_categories_slug_unique` | Yes | `slug` |

---

## Table: `product_custom_prices`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `product_id` | `INTEGER` | No | `NULL` |  |
| `product_variant_id` | `INTEGER` | Yes | `NULL` |  |
| `customer_segment` | `varchar` | Yes | `NULL` |  |
| `user_id` | `INTEGER` | Yes | `NULL` |  |
| `price_amount` | `INTEGER` | No | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

---

## Table: `product_option_values`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `product_option_id` | `INTEGER` | No | `NULL` |  |
| `value` | `varchar` | No | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

---

## Table: `product_options`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `product_id` | `INTEGER` | No | `NULL` |  |
| `name` | `varchar` | No | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

---

## Table: `product_price_histories`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `product_id` | `INTEGER` | No | `NULL` |  |
| `regular_price_amount` | `INTEGER` | No | `NULL` |  |
| `sale_price_amount` | `INTEGER` | Yes | `NULL` |  |
| `recorded_at` | `datetime` | No | `CURRENT_TIMESTAMP` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |
| `product_variant_id` | `INTEGER` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `idx_variant_recorded_at` | No | `product_variant_id`, `recorded_at` |
| `product_price_histories_product_id_recorded_at_index` | No | `product_id`, `recorded_at` |

---

## Table: `product_product_category`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `product_id` | `INTEGER` | No | `NULL` | PK |
| `product_category_id` | `INTEGER` | No | `NULL` | PK |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `sqlite_autoindex_product_product_category_1` | Yes | `product_id`, `product_category_id` |

---

## Table: `product_relations`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `product_id` | `INTEGER` | No | `NULL` |  |
| `related_product_id` | `INTEGER` | No | `NULL` |  |
| `relation_type` | `varchar` | No | `'similar'` |  |
| `sort_order` | `INTEGER` | No | `'0'` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `product_relations_product_id_relation_type_sort_order_index` | No | `product_id`, `relation_type`, `sort_order` |
| `product_relations_unique` | Yes | `product_id`, `related_product_id`, `relation_type` |

---

## Table: `product_reviews`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `product_id` | `INTEGER` | Yes | `NULL` |  |
| `customer_email` | `varchar` | No | `NULL` |  |
| `customer_name` | `varchar` | No | `NULL` |  |
| `rating` | `INTEGER` | No | `NULL` |  |
| `comment` | `TEXT` | Yes | `NULL` |  |
| `is_verified_purchase` | `tinyint(1)` | No | `'0'` |  |
| `is_approved` | `tinyint(1)` | No | `'0'` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `product_reviews_product_id_is_approved_index` | No | `product_id`, `is_approved` |

---

## Table: `product_variant_option_value`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `product_variant_id` | `INTEGER` | No | `NULL` | PK |
| `product_option_value_id` | `INTEGER` | No | `NULL` | PK |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `sqlite_autoindex_product_variant_option_value_1` | Yes | `product_variant_id`, `product_option_value_id` |

---

## Table: `product_variants`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `product_id` | `INTEGER` | No | `NULL` |  |
| `sku` | `varchar` | Yes | `NULL` |  |
| `regular_price_amount` | `INTEGER` | No | `NULL` |  |
| `sale_price_amount` | `INTEGER` | Yes | `NULL` |  |
| `vat_rate` | `INTEGER` | No | `'23'` |  |
| `stock_quantity` | `INTEGER` | Yes | `NULL` |  |
| `manages_stock` | `tinyint(1)` | No | `'0'` |  |
| `is_active` | `tinyint(1)` | No | `'1'` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `product_variants_sku_unique` | Yes | `sku` |

---

## Table: `products`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `slug` | `varchar` | No | `NULL` |  |
| `sku` | `varchar` | Yes | `NULL` |  |
| `type` | `varchar` | No | `NULL` |  |
| `name` | `TEXT` | No | `NULL` |  |
| `short_description` | `TEXT` | Yes | `NULL` |  |
| `description` | `TEXT` | Yes | `NULL` |  |
| `currency` | `varchar` | No | `'PLN'` |  |
| `regular_price_amount` | `INTEGER` | No | `NULL` |  |
| `sale_price_amount` | `INTEGER` | Yes | `NULL` |  |
| `stock_quantity` | `INTEGER` | Yes | `NULL` |  |
| `manages_stock` | `tinyint(1)` | No | `'0'` |  |
| `is_active` | `tinyint(1)` | No | `'1'` |  |
| `is_visible` | `tinyint(1)` | No | `'1'` |  |
| `is_purchasable` | `tinyint(1)` | No | `'1'` |  |
| `seo_title` | `varchar` | Yes | `NULL` |  |
| `seo_description` | `TEXT` | Yes | `NULL` |  |
| `published_at` | `datetime` | Yes | `NULL` |  |
| `metadata` | `TEXT` | Yes | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |
| `featured_image_path` | `varchar` | Yes | `NULL` |  |
| `is_new` | `tinyint(1)` | No | `'0'` |  |
| `is_bestseller` | `tinyint(1)` | No | `'0'` |  |
| `is_recommended` | `tinyint(1)` | No | `'0'` |  |
| `is_promoted` | `tinyint(1)` | No | `'0'` |  |
| `is_seasonal` | `tinyint(1)` | No | `'0'` |  |
| `is_clearance` | `tinyint(1)` | No | `'0'` |  |
| `show_on_homepage` | `tinyint(1)` | No | `'0'` |  |
| `show_in_bestsellers` | `tinyint(1)` | No | `'0'` |  |
| `show_in_new_arrivals` | `tinyint(1)` | No | `'0'` |  |
| `show_in_recommended` | `tinyint(1)` | No | `'0'` |  |
| `manual_tags` | `TEXT` | Yes | `NULL` |  |
| `gallery_image_paths` | `TEXT` | Yes | `NULL` |  |
| `vat_rate` | `INTEGER` | No | `'23'` |  |
| `is_noindex` | `tinyint(1)` | No | `'0'` |  |
| `hs_code` | `varchar` | Yes | `NULL` |  |
| `is_shipped_from_outside_eu` | `tinyint(1)` | No | `'0'` |  |
| `gpsr_manufacturer_name` | `varchar` | Yes | `NULL` |  |
| `gpsr_manufacturer_address` | `varchar` | Yes | `NULL` |  |
| `gpsr_manufacturer_email` | `varchar` | Yes | `NULL` |  |
| `gpsr_responsible_name` | `varchar` | Yes | `NULL` |  |
| `gpsr_responsible_address` | `varchar` | Yes | `NULL` |  |
| `gpsr_responsible_email` | `varchar` | Yes | `NULL` |  |
| `gpsr_safety_warnings` | `TEXT` | Yes | `NULL` |  |
| `gpsr_document_path` | `varchar` | Yes | `NULL` |  |
| `digital_compatibility` | `varchar` | Yes | `NULL` |  |
| `digital_interoperability` | `varchar` | Yes | `NULL` |  |
| `digital_drm` | `varchar` | Yes | `NULL` |  |
| `digital_updates_info` | `varchar` | Yes | `NULL` |  |
| `deleted_at` | `datetime` | Yes | `NULL` |  |
| `weight` | `numeric` | Yes | `NULL` |  |
| `is_ai_generated` | `tinyint(1)` | No | `'0'` |  |
| `ai_disclosure_text` | `TEXT` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `products_type_is_active_index` | No | `type`, `is_active` |
| `products_slug_unique` | Yes | `slug` |
| `products_sku_unique` | Yes | `sku` |
| `products_show_on_homepage_is_active_index` | No | `show_on_homepage`, `is_active` |
| `products_is_visible_published_at_index` | No | `is_visible`, `published_at` |
| `products_is_recommended_is_active_index` | No | `is_recommended`, `is_active` |
| `products_is_new_is_active_index` | No | `is_new`, `is_active` |
| `products_is_bestseller_is_active_index` | No | `is_bestseller`, `is_active` |

---

## Table: `redirect_rules`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `source_path` | `varchar` | No | `NULL` |  |
| `target_path` | `varchar` | No | `NULL` |  |
| `status_code` | `INTEGER` | No | `'301'` |  |
| `is_active` | `tinyint(1)` | No | `'1'` |  |
| `hit_count` | `INTEGER` | No | `'0'` |  |
| `last_hit_at` | `datetime` | Yes | `NULL` |  |
| `metadata` | `TEXT` | Yes | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `redirect_rules_source_path_unique` | Yes | `source_path` |
| `redirect_rules_is_active_status_code_index` | No | `is_active`, `status_code` |

---

## Table: `sessions`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `varchar` | No | `NULL` | PK |
| `user_id` | `INTEGER` | Yes | `NULL` |  |
| `ip_address` | `varchar` | Yes | `NULL` |  |
| `user_agent` | `TEXT` | Yes | `NULL` |  |
| `payload` | `TEXT` | No | `NULL` |  |
| `last_activity` | `INTEGER` | No | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `sessions_last_activity_index` | No | `last_activity` |
| `sessions_user_id_index` | No | `user_id` |
| `sqlite_autoindex_sessions_1` | Yes | `id` |

---

## Table: `store_settings`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `store_name` | `varchar` | No | `'Generic Shop'` |  |
| `currency` | `varchar` | No | `'PLN'` |  |
| `free_shipping_threshold` | `INTEGER` | No | `'25000'` |  |
| `wholesale_minimum_regular_price_multiplier` | `numeric` | No | `'0.7'` |  |
| `cod_only_method` | `varchar` | Yes | `NULL` |  |
| `support_email` | `varchar` | Yes | `NULL` |  |
| `admin_notification_email` | `varchar` | Yes | `NULL` |  |
| `order_notification_email` | `varchar` | Yes | `NULL` |  |
| `mail_from_name` | `varchar` | Yes | `NULL` |  |
| `mail_from_address` | `varchar` | Yes | `NULL` |  |
| `shipping_methods` | `TEXT` | Yes | `NULL` |  |
| `integrations` | `TEXT` | Yes | `NULL` |  |
| `seo` | `TEXT` | Yes | `NULL` |  |
| `metadata` | `TEXT` | Yes | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |
| `allow_guest_checkout` | `tinyint(1)` | No | `'1'` |  |
| `shipping_zones` | `TEXT` | Yes | `NULL` |  |
| `product_reviews_enabled` | `tinyint(1)` | No | `'1'` |  |
| `general_reviews_enabled` | `tinyint(1)` | No | `'1'` |  |
| `general_reviews_source` | `varchar` | No | `'both'` |  |
| `exchange_rates` | `TEXT` | Yes | `NULL` |  |
| `cookie_banner_enabled` | `tinyint(1)` | No | `'0'` |  |
| `google_tag_manager_id` | `varchar` | Yes | `NULL` |  |
| `google_analytics_id` | `varchar` | Yes | `NULL` |  |
| `facebook_pixel_id` | `varchar` | Yes | `NULL` |  |
| `cookie_banner_title` | `varchar` | Yes | `NULL` |  |
| `cookie_banner_description` | `TEXT` | Yes | `NULL` |  |
| `announcement_enabled` | `tinyint(1)` | No | `'0'` |  |
| `announcement_text` | `TEXT` | Yes | `NULL` |  |
| `custom_head_scripts` | `TEXT` | Yes | `NULL` |  |
| `global_noindex` | `tinyint(1)` | No | `'0'` |  |
| `maintenance_mode_enabled` | `tinyint(1)` | No | `'0'` |  |
| `maintenance_mode_allowed_ips` | `TEXT` | Yes | `NULL` |  |
| `maintenance_mode_message` | `TEXT` | Yes | `NULL` |  |
| `eu_import_flat_duty_enabled` | `tinyint(1)` | No | `'0'` |  |

---

## Table: `transactional_email_logs`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `order_id` | `INTEGER` | Yes | `NULL` |  |
| `email_type` | `varchar` | No | `NULL` |  |
| `recipient` | `varchar` | No | `NULL` |  |
| `subject` | `varchar` | No | `NULL` |  |
| `status` | `varchar` | No | `'pending'` |  |
| `sent_at` | `datetime` | Yes | `NULL` |  |
| `error_message` | `TEXT` | Yes | `NULL` |  |
| `payload` | `TEXT` | Yes | `NULL` |  |
| `metadata` | `TEXT` | Yes | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `transactional_email_logs_email_type_status_index` | No | `email_type`, `status` |

---

## Table: `users`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `name` | `varchar` | No | `NULL` |  |
| `email` | `varchar` | No | `NULL` |  |
| `email_verified_at` | `datetime` | Yes | `NULL` |  |
| `password` | `varchar` | No | `NULL` |  |
| `remember_token` | `varchar` | Yes | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |
| `is_admin` | `tinyint(1)` | No | `'0'` |  |
| `role` | `varchar` | No | `'customer'` |  |
| `deleted_at` | `datetime` | Yes | `NULL` |  |
| `two_factor_secret` | `TEXT` | Yes | `NULL` |  |
| `two_factor_recovery_codes` | `TEXT` | Yes | `NULL` |  |
| `two_factor_confirmed_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `users_role_index` | No | `role` |
| `users_email_unique` | Yes | `email` |

---

## Table: `wishlists`

| Column | Type | Nullable | Default | Key |
| --- | --- | --- | --- | --- |
| `id` | `INTEGER` | No | `NULL` | PK |
| `user_id` | `INTEGER` | No | `NULL` |  |
| `product_id` | `INTEGER` | No | `NULL` |  |
| `created_at` | `datetime` | Yes | `NULL` |  |
| `updated_at` | `datetime` | Yes | `NULL` |  |

### Indexes

| Index Name | Unique | Columns |
| --- | --- | --- |
| `wishlists_user_id_product_id_unique` | Yes | `user_id`, `product_id` |

---

