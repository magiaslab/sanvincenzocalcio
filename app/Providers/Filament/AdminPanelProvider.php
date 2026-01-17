<?php

namespace App\Providers\Filament;

use App\Settings\GeneralSettings;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\Middleware\ShareErrorsFromSession;

use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;

use App\Filament\Resources\RoleResource;
use Filament\Navigation\MenuItem;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        try {
            $settings = app(GeneralSettings::class);
            $primaryColor = $settings->primary_color ?? Color::Amber;
            $brandName = $settings->site_name ?? 'San Vincenzo Calcio';
            $brandLogo = $settings->site_logo ? Storage::url($settings->site_logo) : null;
        } catch (\Throwable $e) {
            $primaryColor = Color::Amber;
            $brandName = 'San Vincenzo Calcio';
            $brandLogo = null;
        }

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Pages\Auth\Login::class)
            ->brandName($brandName)
            ->brandLogo($brandLogo)
            ->colors([
                'primary' => $primaryColor,
            ])
            ->favicon(asset('favicon.ico'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->resources([
                RoleResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
                \App\Filament\Pages\UserGuide::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\NextEventsWidget::class,
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
                FilamentShieldPlugin::make(),
                FilamentFullCalendarPlugin::make()
                    ->editable()
                    ->selectable(),
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Guida Utente')
                    ->icon('heroicon-o-book-open')
                    ->url(fn () => \App\Filament\Pages\UserGuide::getUrl())
                    ->sort(10),
            ]);
    }
}
