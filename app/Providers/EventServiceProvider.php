<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

// --- IMPORT MODEL DAN OBSERVER ANDA DI SINI ---
use App\Models\Pesanan;
use App\Observers\PesananObserver;
// --- AKHIR IMPORT ---

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // Biasanya untuk event & listener, bisa dikosongkan jika tidak pakai
        // Contoh:
        // 'App\Events\SomeEvent' => [
        //     'App\Listeners\SomeListener',
        // ],
    ];

    /**
     * The model observers for your application.
     *
     * @var array<string, array<int, string>|string> // Type hint bisa sedikit berbeda
     */
    protected $observers = [
        // --- DAFTARKAN OBSERVER ANDA DI SINI ---
        // Pesanan::class => [PesananObserver::class],
        // --- AKHIR PENDAFTARAN OBSERVER ---

        // Contoh lain (jika ada):
        // \App\Models\User::class => [\App\Observers\UserObserver::class],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents(): bool
    {
        return false; // Biasanya false jika Anda mendaftarkan manual di $listen
    }
}