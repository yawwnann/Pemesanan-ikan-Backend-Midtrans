<?php
// File: app/Providers/Filament/AdminPanelProvider.php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use App\Filament\Widgets\IkanPopulerChart;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

// Pastikan kedua use statement ini ada
use App\Filament\Widgets\PesananStatsOverview;
use App\Filament\Widgets\PesananBulananChart;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->brandName('Admin Panel')
            // ->brandLogo(asset('images/logo.png'))
            // ->favicon(asset('images/favicon.png'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                    // Pastikan halaman Dashboard default atau custom Anda terdaftar
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets') // Biarkan discover aktif atau hapus jika daftar manual saja

            // --- BAGIAN PENDAFTARAN WIDGET ---
            ->widgets([
                    // Widget Default Filament (opsional, hapus komentar jika ingin ditampilkan)
                    // Widgets\AccountWidget::class,
                    // Widgets\FilamentInfoWidget::class,

                    // Widget Custom Anda:
                IkanPopulerChart::class,
                PesananStatsOverview::class,
                PesananBulananChart::class,
            ])
            // --- AKHIR BAGIAN WIDGETS ---

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
            ->navigationGroups([
                'Manajemen Katalog',
                'Transaksi', // <-- Pastikan grup ini ada jika Resource/Widget Anda menggunakannya
                // 'Settings',
            ]);
        // ->plugins([...])
    }
}