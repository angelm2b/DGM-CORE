<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\Facades\Gate;
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
        // Acceso a la documentación (Scramble) en entornos no-local: se concede
        // a quien haya validado la clave admin (ver DocsAccesoController).
        Gate::define('viewApiDocs', function ($user = null) {
            return session('docs_admin_ok') === true;
        });

        // Documenta el esquema de autenticación Bearer (token Sanctum del integrador).
        Scramble::configure()->withDocumentTransformers(function (OpenApi $openApi) {
            $openApi->secure(
                SecurityScheme::http('bearer')
            );
        });
    }
}
