<?php

namespace App\Filament\Resources\Users\Pages;

use App\Domain\Commerce\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $role = $data['role'] ?? null;
        unset($data['role']);

        if ($role !== null) {
            $currentRoleValue = $record->role instanceof UserRole ? $record->role->value : $record->role;
            if ($role === UserRole::Admin->value && $currentRoleValue !== UserRole::Admin->value) {
                abort_unless(auth()->user()?->isAdmin() || auth()->user()?->is_admin === true, 403);
            }
            $record->forceFill([
                'role' => $role,
                'is_admin' => $role === UserRole::Admin->value,
            ]);
        }

        $record->update($data);

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
