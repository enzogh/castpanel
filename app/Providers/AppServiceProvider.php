<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

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
        // Activer le débogage des requêtes SQL en mode développement
        if (app()->environment('local', 'development')) {
            DB::listen(function ($query) {
                Log::info('SQL Query', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                ]);
            });
        }

        // Log des routes pour débogage
        Route::matched(function ($route) {
            Log::info('Route matched', [
                'name' => $route->getName(),
                'uri' => $route->uri(),
                'parameters' => $route->parameters(),
                'middleware' => $route->middleware(),
            ]);
        });
    }
}
