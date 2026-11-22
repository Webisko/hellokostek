<?php

namespace App\Filament\Resources\Users\Pages;

use App\Domain\Commerce\Enums\UserRole;
use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterFill(): void
    {
        $this->data['password'] = $this->data['password'] ?? '';
        $this->rememberData();
    }

    protected function handleRecordCreation(array $data): Model
    {
        $role = $data['role'] ?? UserRole::Employee->value;
        unset($data['role']);

        if ($role === UserRole::Admin->value) {
            abort_unless(auth()->user()?->isAdmin() || auth()->user()?->is_admin === true, 403);
        }

        $record = new (static::getModel())($data);
        $record->forceFill([
            'role' => $role,
            'is_admin' => $role === UserRole::Admin->value,
        ])->save();

        return $record;
    }
}
