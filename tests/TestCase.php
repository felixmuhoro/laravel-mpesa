<?php

declare(strict_types=1);

namespace FelixMuhoro\Mpesa\Tests;

use FelixMuhoro\Mpesa\MpesaServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [MpesaServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['Mpesa' => \FelixMuhoro\Mpesa\Facades\Mpesa::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('mpesa.environment', 'sandbox');
        $app['config']->set('mpesa.consumer_key', 'test_key');
        $app['config']->set('mpesa.consumer_secret', 'test_secret');
        $app['config']->set('mpesa.stk.business_short_code', '174379');
        $app['config']->set('mpesa.stk.passkey', 'test_passkey');
        $app['config']->set('mpesa.stk.callback_url', 'https://example.test/mpesa/callback/stk');
        $app['config']->set('mpesa.callback.allowed_ips', []);
        $app['config']->set('mpesa.callback.secret_query_key', null);
    }
}
