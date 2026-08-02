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
        //
    }
}
