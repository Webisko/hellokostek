<?php

use App\Domain\Analytics\AnalyticsDailyAggregationService;
use App\Domain\Imports\JsonImportService;
use App\Models\Order;
use App\Models\User;
use App\Support\StoreSettings;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:make-admin-user {email : Email administratora} {--name= : Nazwa wyswietlana} {--password= : Haslo dla nowego admina lub reset hasla istniejacego} {--promote-existing : Promuj istniejace konto do roli admina}', function (string $email) {
    $email = Str::lower(trim($email));
    $name = $this->option('name');
    $password = $this->option('password');
    $promoteExisting = (bool) $this->option('promote-existing');

    $user = User::query()->where('email', $email)->first();

    if ($user) {
        if (! $promoteExisting) {
            $this->error('Uzytkownik juz istnieje. Uzyj --promote-existing, aby nadac mu uprawnienia admina.');

            return self::FAILURE;
        }

        $attributes = [
            'is_admin' => true,
            'role' => \App\Domain\Commerce\Enums\UserRole::Admin->value,
        ];

        if (filled($name)) {
            $attributes['name'] = $name;
        }

        if (filled($password)) {
            $attributes['password'] = Hash::make((string) $password);
        }

        $user->forceFill($attributes)->save();

        $this->info("Uzytkownik {$user->email} ma teraz dostep do panelu admin.");

        return self::SUCCESS;
    }

    if (blank($password)) {
        $this->error('Dla nowego admina musisz podac --password.');

        return self::FAILURE;
    }

    $displayName = filled($name)
        ? (string) $name
        : Str::title(str_replace(['.', '_', '-'], ' ', Str::before($email, '@')));

    $user = User::query()->create([
        'name' => $displayName,
        'email' => $email,
        'password' => Hash::make((string) $password),
        'is_admin' => true,
        'role' => \App\Domain\Commerce\Enums\UserRole::Admin->value,
        'email_verified_at' => now(),
    ]);

    $this->info("Utworzono administratora {$user->email}.");

    return self::SUCCESS;
})->purpose('Tworzy lub promuje uzytkownika z dostepem do panelu Filament admin');

Artisan::command('app:import-shop-json {dataset : Typ datasetu np. products, orders, customers} {path : Sciezka do pliku JSON} {--dry-run : Walidacja bez zapisu}', function (JsonImportService $importService, string $dataset, string $path) {
    $resolvedPath = str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:\\\\/', $path)
        ? $path
        : base_path($path);

    $summary = $importService->import(
        dataset: $dataset,
        path: $resolvedPath,
        dryRun: (bool) $this->option('dry-run'),
    );

    $this->table(
        ['Dataset', 'Dry run', 'Processed', 'Created', 'Updated'],
        [[
            $summary['dataset'],
            $summary['dry_run'] ? 'yes' : 'no',
            $summary['processed'],
            $summary['created'],
            $summary['updated'],
        ]],
    );

    return self::SUCCESS;
})->purpose('Importuje wybrane datasety sklepu z plikow JSON do nowego backendu');

Artisan::command('app:aggregate-analytics-daily {--date= : Data do agregacji w formacie YYYY-MM-DD, domyslnie wczoraj}', function (AnalyticsDailyAggregationService $aggregationService) {
    $dateOption = $this->option('date');
    $date = filled($dateOption)
        ? now()->parse((string) $dateOption)->startOfDay()
        : now()->subDay()->startOfDay();

    $summary = $aggregationService->aggregateDate($date);

    $this->table(
        ['Data', 'Srodowiska', 'Raw events', 'Agregaty'],
        [[
            $summary['aggregate_date'],
            $summary['environments'],
            $summary['source_events'],
            $summary['aggregates_written'],
        ]],
    );

    return self::SUCCESS;
})->purpose('Agreguje dzienne metryki first-party analytics z raw events do lekkich raportow operacyjnych');

Artisan::command('app:import-google-reviews-snapshot {path : Sciezka do pliku JSON ze snapshotem opinii Google} {--dry-run : Walidacja bez zapisu}', function (StoreSettings $storeSettings, string $path) {
    $resolvedPath = str_starts_with($path, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:\\\\/', $path)
        ? $path
        : base_path($path);

    if (! is_file($resolvedPath)) {
        $this->error("Nie znaleziono pliku: {$resolvedPath}");

        return self::FAILURE;
    }

    $decoded = json_decode(file_get_contents($resolvedPath), true);

    if (! is_array($decoded)) {
        $this->error('Plik nie zawiera poprawnego JSON-a.');

        return self::FAILURE;
    }

    $payload = is_array($decoded['data'] ?? null)
        ? $decoded['data']
        : $decoded;

    $reviews = collect((array) ($payload['reviews'] ?? []))
        ->map(fn (mixed $review): array => [
            'author_name' => (string) data_get((array) $review, 'author_name', 'Klient Google'),
            'author_url' => data_get((array) $review, 'author_url'),
            'profile_photo_url' => data_get((array) $review, 'profile_photo_url'),
            'rating' => (int) data_get((array) $review, 'rating', 0),
            'relative_time_description' => (string) data_get((array) $review, 'relative_time_description', ''),
            'text' => (string) data_get((array) $review, 'text', ''),
            'language' => data_get((array) $review, 'language'),
            'published_at' => data_get((array) $review, 'published_at'),
        ])
        ->filter(fn (array $review): bool => $review['rating'] === 5 && filled($review['text']))
        ->values()
        ->all();

    if ($reviews === []) {
        $this->error('Snapshot nie zawiera zadnych 5-gwiazdkowych opinii do zapisania.');

        return self::FAILURE;
    }

    $snapshot = [
        'business_name' => (string) ($payload['business_name'] ?? $payload['name'] ?? 'Generic Shop'),
        'place_id' => $payload['place_id'] ?? null,
        'rating' => $payload['rating'] ?? null,
        'user_ratings_total' => $payload['user_ratings_total'] ?? null,
        'listing_url' => $payload['listing_url'] ?? $payload['url'] ?? null,
        'fetched_at' => $payload['fetched_at'] ?? now()->toIso8601String(),
        'reviews' => $reviews,
    ];

    if ((bool) $this->option('dry-run')) {
        $this->table(
            ['Business', 'Place ID', 'Reviews', 'Fetched at'],
            [[
                $snapshot['business_name'],
                $snapshot['place_id'] ?? '-',
                count($snapshot['reviews']),
                $snapshot['fetched_at'] ?? '-',
            ]],
        );

        return self::SUCCESS;
    }

    $settings = $storeSettings->model();
    $integrations = $settings->integrations ?? [];
    data_set($integrations, 'reviews.enabled', true);
    data_set($integrations, 'reviews.primary_source', 'google');
    data_set($integrations, 'reviews.sources', ['google']);
    data_set($integrations, 'reviews.google.business_name', $snapshot['business_name']);
    data_set($integrations, 'reviews.google.place_id', $snapshot['place_id']);
    data_set($integrations, 'reviews.google.snapshot', $snapshot);

    $settings->forceFill([
        'integrations' => $integrations,
    ])->save();

    $this->table(
        ['Business', 'Place ID', 'Reviews', 'Fetched at'],
        [[
            $snapshot['business_name'],
            $snapshot['place_id'] ?? '-',
            count($snapshot['reviews']),
            $snapshot['fetched_at'] ?? '-',
        ]],
    );

    $this->info('Snapshot opinii Google zostal zapisany w store settings.');

    return self::SUCCESS;
})->purpose('Importuje lokalny snapshot 5-gwiazdkowych opinii Google do store settings jako bezkosztowy fallback');

Artisan::command('app:cleanup-price-history', function () {
    $cutoff = now()->subDays(90);
    $deletedCount = \App\Models\ProductPriceHistory::query()
        ->where('recorded_at', '<', $cutoff)
        ->delete();

    $this->info("Usunięto {$deletedCount} archiwalnych wpisów historii cen starszych niż 90 dni.");

    return self::SUCCESS;
})->purpose('Usuwa archiwalne wpisy historii cen Omnibus starsze niż 90 dni');

Artisan::command('app:generate-sitemap', function () {
    $this->info('Rozpoczynanie generowania mapy witryny sitemap.xml...');
    $generator = new \App\Support\SEO\SitemapGenerator();
    $generator->generate();
    $this->info('Mapa witryny została pomyślnie wygenerowana.');

    return self::SUCCESS;
})->purpose('Generuje plik sitemap.xml w katalogu public');

Schedule::command('app:aggregate-analytics-daily')
    ->dailyAt('02:15')
    ->withoutOverlapping();

Schedule::command('app:recover-abandoned-carts')
    ->hourly()
    ->withoutOverlapping();

Schedule::command('app:cleanup-price-history')
    ->daily()
    ->withoutOverlapping();



Schedule::command('app:cleanup-abandoned-carts')
    ->daily()
    ->withoutOverlapping();

Schedule::command('app:generate-sitemap')
    ->dailyAt('03:30')
    ->withoutOverlapping();

Schedule::command('queue:work --stop-when-empty')
    ->everyMinute()
    ->withoutOverlapping();
