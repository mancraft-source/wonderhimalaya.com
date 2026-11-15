<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Dashboard;
use App\Filament\Support\BrandColors;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
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
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->passwordReset()
            // Profile (and two-factor setup) inside the normal layout, so the sidebar stays.
            ->profile(EditProfile::class, isSimple: false)
            ->multiFactorAuthentication(
                [AppAuthentication::make()->recoverable()],
                isRequired: app()->isProduction(),
            )
            ->brandName('Wonder Himalaya')
            ->brandLogo(fn () => view('filament.brand'))
            ->brandLogoHeight('2.75rem')
            ->favicon(asset('favicon.png'))
            // Hand-tuned brand scales (Color::hex() would turn Juniper into a bright teal) — see BrandColors.
            ->colors([
                'primary' => BrandColors::JUNIPER,
                'gray' => BrandColors::GRAY,
                'warning' => BrandColors::SAFFRON,
                'danger' => BrandColors::TERRACOTTA,
                'info' => BrandColors::GLACIER,
            ])
            ->font('Inter')
            // Light / Dark / System switch in the user menu; follows the device until someone picks one.
            ->darkMode()
            ->defaultThemeMode(ThemeMode::System)
            ->spa()
            ->sidebarCollapsibleOnDesktop()
            ->globalSearch()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->unsavedChangesAlerts()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->navigationGroups([
                NavigationGroup::make('CRM'),
                NavigationGroup::make('Sales'),
                NavigationGroup::make('Catalogue'),
                NavigationGroup::make('Content'),
                NavigationGroup::make('SEO'),
                NavigationGroup::make('Settings')->collapsed(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
