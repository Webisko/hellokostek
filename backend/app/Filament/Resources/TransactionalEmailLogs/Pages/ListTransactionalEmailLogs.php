<?php

namespace App\Filament\Resources\TransactionalEmailLogs\Pages;

use App\Filament\Resources\TransactionalEmailLogs\TransactionalEmailLogResource;
use Filament\Resources\Pages\ListRecords;

class ListTransactionalEmailLogs extends ListRecords
{
    protected static string $resource = TransactionalEmailLogResource::class;
}