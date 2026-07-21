<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Illuminate\Contracts\Support\Htmlable;

class CustomRequestPasswordReset extends BaseRequestPasswordReset
{
    protected static ?string $slug = 'odzyskiwanie-hasla/prosba';

    protected function getEmailFormComponent(): Component
    {
        return parent::getEmailFormComponent()
            ->markAsRequired(false);
    }

    public function getSubheading(): string | Htmlable | null
    {
        return null;
    }

    public function loginAction(): \Filament\Actions\Action
    {
        return parent::loginAction()
            ->label('Wróć do logowania');
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('request')
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment($this->getFormActionsAlignment())
                    ->fullWidth($this->hasFullWidthFormActions())
                    ->key('form-actions'),

                Actions::make([
                    $this->loginAction(),
                ])
                ->alignment(\Filament\Support\Enums\Alignment::Center)
                ->key('login-action'),
            ]);
    }
}
