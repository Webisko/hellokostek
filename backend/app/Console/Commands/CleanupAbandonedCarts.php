<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

class CleanupAbandonedCarts extends Command
{
    protected $signature = 'app:cleanup-abandoned-carts {--days=30 : Liczba dni, po których porzucone szkice koszyków będą usuwane}';

    protected $description = 'Usuwa z bazy danych porzucone szkice koszyków (zamówienia o statusie draft) starsze niż określona liczba dni';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $count = Order::query()
            ->where('status', 'draft')
            ->where('updated_at', '<', $cutoff)
            ->count();

        if ($count === 0) {
            $this->info('Nie znaleziono starych szkiców koszyków do usunięcia.');
            return self::SUCCESS;
        }

        Order::query()
            ->where('status', 'draft')
            ->where('updated_at', '<', $cutoff)
            ->forceDelete();

        $this->info("Pomyślnie usunięto {$count} starych szkiców koszyków (starszych niż {$days} dni).");

        return self::SUCCESS;
    }
}
