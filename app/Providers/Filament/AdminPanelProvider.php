<?php

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
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            // Pengenal unik untuk panel ini
            ->id('admin')
            // URL path untuk mengakses panel ini
            ->path('admin')
            // Halaman login default Filament
            ->login()
            // Pengaturan warna tema
            ->colors([
                'primary' => Color::Amber, // Anda bisa ganti warnanya (misal: Blue, Indigo, Emerald, dll.)
            ])
            // Judul yang tampil di tab browser
            ->brandName('Katalog Ikan CMS') // Ganti dengan nama aplikasi Anda
            // Logo (opsional)
            // ->brandLogo(asset('images/logo.png'))
            // ->favicon(asset('images/favicon.png'))
            // Menemukan Resources (seperti IkanResource, KategoriIkanResource) di direktori App/Filament/Resources
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            // Menemukan Pages (halaman kustom) di direktori App/Filament/Pages
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            // Halaman default setelah login (biasanya Dashboard)
            ->pages([
                Pages\Dashboard::class,
            ])
            // Menemukan Widgets (seperti AccountWidget, FilamentInfoWidget) di direktori App/Filament/Widgets
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            // Widget default yang tampil di Dashboard
            ->widgets([
                // Widgets\AccountWidget::class, // Widget info akun user
                // Widgets\FilamentInfoWidget::class, // Widget info versi Filament & PHP
            ])
            // Middleware yang dijalankan untuk setiap request ke panel admin
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
            // Middleware khusus untuk otentikasi (memastikan user sudah login)
            ->authMiddleware([
                Authenticate::class,
            ])
            // (Opsional) Mendefinisikan grup navigasi secara eksplisit
            // Berguna jika Anda ingin mengatur urutan atau ikon grup
            ->navigationGroups([
                'Manajemen Katalog', // Nama grup yang Anda gunakan di Resource
                // 'Settings', // Contoh grup lain
            ]);
        // Anda bisa menambahkan plugin di sini jika menggunakannya
        // ->plugins([...])
    }
}