<?php

namespace App\Providers;

use App\Services\AiService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // AiService depends on runtime settings, so the container
        // builds it from the settings table instead of primitives.
        $this->app->bind(AiService::class, fn (): AiService => AiService::fromSettings());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
