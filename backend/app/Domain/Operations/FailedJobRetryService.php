<?php

namespace App\Domain\Operations;

use App\Models\FailedJob;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

class FailedJobRetryService
{
    public function retry(FailedJob $failedJob): void
    {
        if (! filled($failedJob->uuid)) {
            throw new RuntimeException('Nie mozna ponowic joba bez identyfikatora UUID.');
        }

        Artisan::call('queue:retry', [
            'id' => [$failedJob->uuid],
        ]);

        if (FailedJob::query()->whereKey($failedJob->getKey())->exists()) {
            $message = trim(Artisan::output());

            throw new RuntimeException($message !== ''
                ? $message
                : "Nie udalo sie ponowic joba [{$failedJob->uuid}].");
        }
    }
}