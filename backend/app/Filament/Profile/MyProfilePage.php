<?php

namespace App\Filament\Profile;

use Illuminate\Contracts\Support\Htmlable;
use Jeffgreco13\FilamentBreezy\Pages\MyProfilePage as BaseMyProfilePage;

class MyProfilePage extends BaseMyProfilePage
{
    protected static ?int $navigationSort = 30;

    public function getTitle(): string|Htmlable
    {
        return 'Mój profil i hasło';
    }

    public function getHeading(): string|Htmlable
    {
        return 'Mój profil i hasło';
    }

    public static function getNavigationLabel(): string
    {
        return 'Mój profil i hasło';
    }
}
