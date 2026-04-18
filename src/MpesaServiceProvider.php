<?php

declare(strict_types=1);

namespace FelixMuhoro\Mpesa;

use FelixMuhoro\Mpesa\Contracts\MpesaInterface;
use FelixMuhoro\Mpesa\Http\Middleware\VerifyMpesaCallback;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class MpesaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/mpesa.php', 'mpesa');

        $this->app->singleton(MpesaInterface::class, function ($app) {
            return new Mpesa($app['config']->get('mpesa'), $app['cache']);
        });

        $this->app->alias(MpesaInterface::class, Mpesa::class);
        $this->app->alias(MpesaInterface::class, 'mpesa');
    }

    public function boot(): void
    {
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('mpesa.callback', VerifyMpesaCallback::class);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/mpesa.php' => config_path('mpesa.php'),
            ], 'mpesa-config');

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'mpesa-migrations');
        }

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        if ($this->app['config']->get('mpesa.callback.register_routes', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/mpesa.php');
        }
    }

    public function provides(): array
    {
        return [MpesaInterface::class, Mpesa::class, 'mpesa'];
    }
}
