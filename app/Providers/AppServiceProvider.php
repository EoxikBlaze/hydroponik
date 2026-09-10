<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Monitoring;

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
                $view->with('iotStatus', Monitoring::connectionStatus(5));
            } catch (\Throwable $e) {
                $view->with('iotStatus', [
                    'online'    => false,
                    'last_seen' => null,
                    'diff_text' => 'Database tidak terhubung',
                    'label'     => 'Offline',
                ]);
            }
        });
    }
}
