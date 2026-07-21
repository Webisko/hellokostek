<?php

namespace App\Filament\Resources\IrydologyCases\Pages;

use App\Filament\Resources\IrydologyCases\IrydologyCaseResource;
use App\Models\IrydologyCase;
use Illuminate\Support\Carbon;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditIrydologyCase extends EditRecord
{
    protected static string $resource = IrydologyCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $status = $data['status'] ?? null;

        if (in_array($status, [
            IrydologyCase::STATUS_PHOTOS_RECEIVED,
            IrydologyCase::STATUS_ANALYSIS_IN_PROGRESS,
            IrydologyCase::STATUS_COMPLETED,
        ], true)) {
            $data['instructions_sent_at'] ??= Carbon::now();
            $data['assets_received_at'] ??= Carbon::now();
        }

        if (in_array($status, [
            IrydologyCase::STATUS_ANALYSIS_IN_PROGRESS,
            IrydologyCase::STATUS_COMPLETED,
        ], true)) {
            $data['analysis_due_at'] ??= Carbon::now()->addDays(10);
        }

        if ($status === IrydologyCase::STATUS_COMPLETED) {
            $data['completed_at'] ??= Carbon::now();
        }

        if ($status !== IrydologyCase::STATUS_COMPLETED) {
            $data['completed_at'] = null;
        }

        if ($status === IrydologyCase::STATUS_AWAITING_PHOTOS) {
            $data['assets_received_at'] = null;
        }

        return $data;
    }
}