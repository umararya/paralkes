<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\Widgets;
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
            ->path('admin')
            ->login(Login::class)

            // ── Brand / Logo ──────────────────────────────────────────────
            ->brandLogo(asset('images/logo-paralkes-white.png'))
            ->darkModeBrandLogo(asset('images/logo-paralkes.png')) // logo putih untuk dark mode
            ->brandLogoHeight('3rem')
            // ─────────────────────────────────────────────────────────────

            // ── Skema Warna: Zinc (abu-abu premium menuju hitam) ──────────
            ->colors([
                'primary'   => Color::Zinc,
                'gray'      => Color::Zinc,
                'danger'    => Color::Rose,
                'warning'   => Color::Amber,
                'success'   => Color::Emerald,
                'info'      => Color::Sky,
            ])
            // ─────────────────────────────────────────────────────────────
            // Tambahkan di dalam method panel(), setelah ->colors([...])
            ->navigationGroups([
                'Admin',
                'Owner',
            ])
            // ── Tipografi Modern ─────────────────────────────────────────
            ->font('Plus Jakarta Sans')
            // ─────────────────────────────────────────────────────────────

            // ── Dark Mode ────────────────────────────────────────────────
            ->darkMode(true)
            // ─────────────────────────────────────────────────────────────

            // ── Lebar konten ─────────────────────────────────────────────
            ->maxContentWidth(MaxWidth::Full)
            // ─────────────────────────────────────────────────────────────

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
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
            ]);
    }
}