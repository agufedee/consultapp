<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;
use Illuminate\Support\HtmlString;


class ConsultorioPanelProvider extends PanelProvider
{

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('consultorio')
            ->path('consultorio')
            ->login()
            ->favicon(asset('images/logo.png'))
            ->brandName('ConsultApp')
            ->brandLogo(fn() => new HtmlString('
            <div style="display: flex; align-items: center; gap: 15px;">
                <img 
                    src="' . asset('images/logo.png') . '" 
                    alt="Logo ConsultApp" 
                    style="height: 40px; width: auto;" 
                />
                
                <span style="font-size: 30px; font-weight: bold; color: inherit; line-height: 1;">
                    ConsultApp
                </span>
            </div>
        '))

            ->brandLogoHeight('5rem')
            ->spa()

            ->colors([
                'primary' => Color::Green,
            ])
            ->plugin(
                FilamentFullCalendarPlugin::make()
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')

            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
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
            ]);
    }
}
