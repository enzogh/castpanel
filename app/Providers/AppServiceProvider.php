<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
// use Dedoc\Scramble\Scramble;
// use Dedoc\Scramble\Support\Generator\OpenApi;
// use Dedoc\Scramble\Support\Generator\SecurityScheme;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Enregistrer les APIs Scramble (temporairement désactivé)
        /*
        Scramble::registerApi('application', [
            'api_path' => 'api/application',
            'info' => ['version' => '1.0']
        ])->afterOpenApiGenerated(function (OpenApi $openApi) {
            $openApi->secure(SecurityScheme::http('bearer'));
        });

        Scramble::registerApi('client', [
            'api_path' => 'api/client',
            'info' => ['version' => '1.0']
        ])->afterOpenApiGenerated(function (OpenApi $openApi) {
            $openApi->secure(SecurityScheme::http('bearer'));
        });
        */
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
                'uri' => $route->uri(),
                'methods' => $route->methods(),
                'middleware' => $route->middleware(),
            ]);
        });
    }
}
