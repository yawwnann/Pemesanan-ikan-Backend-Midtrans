<?php
// File: app/Providers/Filament/AdminPanelProvider.php

namespace App\Providers\Filament;

use App\Filament\Resources\UserResource;
use App\Filament\Widgets\IkanPopulerChart;
use App\Filament\Widgets\PesananBulananChart;
use App\Filament\Widgets\PesananStatsOverview;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth; // Pastikan Auth facade diimport
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default() // Menandakan ini panel default jika hanya ada satu
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->brandName('Katalog Ikan CMS') // Nama aplikasi Anda
            // ->brandLogo(asset('images/logo.png')) // Opsional logo
            // ->favicon(asset('images/favicon.png')) // Opsional favicon

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class, // Halaman Dashboard
            ])
            // ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets') // Bisa dinonaktifkan jika mendaftar manual di bawah

            // Pendaftaran Widget Dashboard Eksplisit
            ->widgets([
                    // Nonaktifkan widget default jika tidak perlu
                    // Widgets\AccountWidget::class,
                    // Widgets\FilamentInfoWidget::class,

                    // Widget custom Anda
                PesananStatsOverview::class,
                PesananBulananChart::class,
                IkanPopulerChart::class,
            ])

            // Item Menu User Tambahan
            ->userMenuItems([
                MenuItem::make()
                    ->label('Edit Profil Saya')
                    ->url(fn(): string => UserResource::getUrl('edit', ['record' => Auth::user()]))
                    ->icon('heroicon-o-user-circle'),
                // Item lain bisa ditambahkan di sini
            ])

            // Middleware standar Filament/Laravel
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

            // Middleware otentikasi
            ->authMiddleware([
                Authenticate::class,
            ])

            // Grup Navigasi di Sidebar
            ->navigationGroups([
                'Manajemen Katalog',
                'Transaksi',
                // Tambahkan grup lain jika perlu
            ]);
        // ->plugins([...]) // Jika pakai plugin
    }
}