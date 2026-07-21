<?php

namespace App\Jobs;

use App\Support\SEO\SitemapGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SitemapGeneratorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        try {
            $generator = new SitemapGenerator();
            $generator->generate();
            Log::info('Mapa witryny sitemap.xml została pomyślnie wygenerowana.');
        } catch (\Throwable $e) {
            Log::error('Błąd podczas generowania mapy witryny sitemap.xml: ' . $e->getMessage());
        }
    }
}
