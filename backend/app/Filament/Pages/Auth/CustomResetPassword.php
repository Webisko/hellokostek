<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\PasswordReset\ResetPassword as BaseResetPassword;
use Filament\Schemas\Components\Component;

class CustomResetPassword extends BaseResetPassword
{
    protected static ?string $slug = 'odzyskiwanie-hasla/reset';

    protected function getEmailFormComponent(): Component
    {
        return parent::getEmailFormComponent()
            ->markAsRequired(false);
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->markAsRequired(false);
    }

    protected function getPasswordConfirmationFormComponent(): Component
    {
        return parent::getPasswordConfirmationFormComponent()
            ->markAsRequired(false);
    }
}
