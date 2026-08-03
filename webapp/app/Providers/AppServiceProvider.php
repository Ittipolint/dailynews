<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            \App\Services\Ingestion\IngestionService::class,
            \App\Services\Ingestion\IngestionService::class
        );
        $this->app->singleton(
            \App\Services\Translation\TranslationService::class,
            \App\Services\Translation\TranslationService::class
        );
        $this->app->singleton(
            \App\Services\Delivery\DeliveryService::class,
            \App\Services\Delivery\DeliveryService::class
        );
        $this->app->singleton(
            \App\Services\Rag\GraphRagService::class,
            \App\Services\Rag\GraphRagService::class
        );
        $this->app->singleton(
            \App\Services\Search\NewsSearchService::class,
            \App\Services\Search\NewsSearchService::class
        );
    }

    public function boot(): void
    {
        // Force https for generated URLs on production. The site is served
        // behind Cloudflare which does not forward X-Forwarded-Proto, so
        // Laravel would otherwise emit http:// URLs -> mixed-content errors
        // ("Failed to fetch") in the browser.
        if (app()->environment('production') && str_starts_with(config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
