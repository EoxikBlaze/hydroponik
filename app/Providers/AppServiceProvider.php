<?php

namespace App\Providers;

use App\Models\Monitoring;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        // Bagikan status koneksi IoT secara realtime ke seluruh view
        View::composer('*', function ($view) {
            try {
                $view->with('iotStatus', Monitoring::connectionStatus(2));
            } catch (\Throwable $e) {
                $view->with('iotStatus', [
                    'online' => false,
                    'last_seen' => null,
                    'diff_text' => 'Database tidak terhubung',
                    'label' => 'Offline',
                ]);
            }
        });
    }
}
