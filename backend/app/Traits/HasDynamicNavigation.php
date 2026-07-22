<?php

namespace App\Traits;

use App\Support\StoreSettings;
use Illuminate\Support\Str;

trait HasDynamicNavigation
{
    public static function getTitleCasePluralModelLabel(): string
    {
        return Str::ucfirst(static::getPluralModelLabel());
    }

    public static function getTitleCaseModelLabel(): string
    {
        return Str::ucfirst(static::getModelLabel());
    }

    public static function getNavigationLabel(): string
    {
        $default = static::$navigationLabel ?? parent::getNavigationLabel();
        $classBasename = class_basename(static::class);
        return app(StoreSettings::class)->resourceNavigationLabel($classBasename, $default);
    }

    public static function getNavigationGroup(): ?string
    {
        $default = static::$navigationGroup ?? parent::getNavigationGroup();
        $classBasename = class_basename(static::class);
        return app(StoreSettings::class)->resourceNavigationGroup($classBasename, $default);
    }

    public static function getNavigationSort(): ?int
    {
        $default = static::$navigationSort ?? parent::getNavigationSort();
        $classBasename = class_basename(static::class);
        return app(StoreSettings::class)->resourceNavigationSort($classBasename, $default);
    }

    public static function shouldRegisterNavigation(): bool
    {
        $classBasename = class_basename(static::class);
        return app(StoreSettings::class)->resourceNavigationVisible($classBasename, parent::shouldRegisterNavigation());
    }
}