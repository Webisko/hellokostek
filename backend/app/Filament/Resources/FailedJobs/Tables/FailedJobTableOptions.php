<?php

namespace App\Filament\Resources\FailedJobs\Tables;

use App\Models\FailedJob;

class FailedJobTableOptions
{
    public static function queueOptions(): array
    {
        return FailedJob::query()
            ->orderBy('queue')
            ->distinct()
            ->pluck('queue', 'queue')
            ->all();
    }
}