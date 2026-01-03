<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // 👈 1. JANGAN LUPA IMPORT INI!

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 👈 2. Tambahkan Logika Paksa HTTPS ini
        // Cek: Kalau environment-nya 'production' (Railway), paksa pakai HTTPS.
        if($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}