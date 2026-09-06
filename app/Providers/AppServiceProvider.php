<?php

namespace App\Providers;

use App\Services\PricingService;
use App\Services\TemplateBuilder;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TemplateBuilder::class);
        $this->app->singleton(PricingService::class);
    }

    public function boot(): void
    {
        Paginator::useTailwind();

        Blade::directive('money', function ($expression) {
            return "<?php echo app(\App\Services\PricingService::class)->format(...[$expression]); ?>";
        });
    }
}
