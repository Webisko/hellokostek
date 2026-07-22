<?php

namespace App\Providers\Filament;

use App\Filament\Pages\StoreDashboard;
use App\Filament\Resources\Orders\OrderResource;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path(app(\App\Support\StoreSettings::class)->adminPath())
            ->login(\App\Filament\Pages\Auth\CustomLogin::class)
            ->passwordReset(
                \App\Filament\Pages\Auth\CustomRequestPasswordReset::class,
                \App\Filament\Pages\Auth\CustomResetPassword::class,
            )
            ->passwordResetRoutePrefix('odzyskiwanie-hasla')
            ->passwordResetRequestRouteSlug('prosba')
            ->passwordResetRouteSlug('reset')
            ->darkMode()
            ->defaultThemeMode(ThemeMode::Light)
            ->favicon(asset('favicon.ico'))
            ->homeUrl(fn (): string => StoreDashboard::getUrl())
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): string => view('filament.components.custom-styles')->render(),
            )
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => view('filament.components.topbar-theme-switcher')->render(),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => view('filament.components.broadcast-listener')->render(),
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_FOOTER,
                fn (): string => view('filament.components.sidebar-collapse-button')->render(),
            )
            ->colors([
                'primary' => Color::hex('#E0115F'),
                'magenta' => Color::hex('#E0115F'),
                'lime' => Color::hex('#C4F013'),
            ])
            ->font(
                'Instrument Sans',
                url: asset('css/fonts.css'),
                provider: \Filament\FontProviders\LocalFontProvider::class,
            )
            ->brandName(fn (): string => app(\App\Support\StoreSettings::class)->adminBrandName())
            ->brandLogo(fn (): ?string => app(\App\Support\StoreSettings::class)->adminLogoUrl())
            ->favicon(fn (): ?string => app(\App\Support\StoreSettings::class)->adminFaviconUrl())
            ->maxContentWidth('full')
            ->breadcrumbs(false)
            ->sidebarCollapsibleOnDesktop()
            ->spa()
            ->navigationGroups(app(\App\Support\StoreSettings::class)->navigationGroups())
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->resources([
                \App\Filament\Resources\MediaResource\MediaResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                StoreDashboard::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                \Jeffgreco13\FilamentBreezy\BreezyCore::make()
                    ->myProfile(
                        shouldRegisterUserMenu: true,
                        shouldRegisterNavigation: false,
                        hasAvatars: false,
                        slug: 'moj-profil'
                    )
                    ->enableTwoFactorAuthentication(force: false),
            ]);
    }
}



