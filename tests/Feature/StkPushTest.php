<?php

declare(strict_types=1);

namespace FelixMuhoro\Mpesa\Tests\Feature;

use FelixMuhoro\Mpesa\Exceptions\AuthException;
use FelixMuhoro\Mpesa\Exceptions\MpesaException;
use FelixMuhoro\Mpesa\Facades\Mpesa;
use FelixMuhoro\Mpesa\Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class StkPushTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_it_fetches_and_caches_the_oauth_token(): void
    {
        Http::fake([
            '*oauth/v1/generate*' => Http::response(['access_token' => 'abc123', 'expires_in' => 3599]),
        ]);

        $token = Mpesa::getAccessToken();

        $this->assertSame('abc123', $token);
        $this->assertSame('abc123', Mpesa::getAccessToken()); // from cache, no second HTTP call
        Http::assertSentCount(1);
    }

    public function test_it_raises_auth_exception_when_creds_missing(): void
    {
        config()->set('mpesa.consumer_key', null);
        config()->set('mpesa.consumer_secret', null);
        Cache::flush();

        $this->expectException(AuthException::class);
        Mpesa::getAccessToken();
    }

    public function test_stk_push_returns_response_dto_on_success(): void
    {
        Http::fake([
            '*oauth/v1/generate*' => Http::response(['access_token' => 't']),
            '*stkpush/v1/processrequest*' => Http::response([
                'MerchantRequestID'   => '29115-34620561-1',
                'CheckoutRequestID'   => 'ws_CO_191220191020363925',
                'ResponseCode'        => '0',
                'ResponseDescription' => 'Success. Request accepted for processing',
                'CustomerMessage'     => 'Success. Request accepted for processing',
            ]),
        ]);

        $response = Mpesa::stkPush('0712345678', 1, 'TEST', 'Test payment');

        $this->assertTrue($response->accepted());
        $this->assertSame('ws_CO_191220191020363925', $response->checkoutRequestId);
    }

    public function test_stk_push_throws_when_daraja_rejects(): void
    {
        Http::fake([
            '*oauth/v1/generate*' => Http::response(['access_token' => 't']),
            '*stkpush/v1/processrequest*' => Http::response([
                'ResponseCode'        => '1',
                'ResponseDescription' => 'Bad request',
            ], 400),
        ]);

        $this->expectException(MpesaException::class);
        Mpesa::stkPush('0712345678', 1);
    }

    public function test_stk_query_maps_completed_code(): void
    {
        Http::fake([
            '*oauth/v1/generate*' => Http::response(['access_token' => 't']),
            '*stkpushquery/v1/query*' => Http::response([
                'ResponseCode'        => '0',
                'ResponseDescription' => 'The service request has been accepted successsfully',
                'MerchantRequestID'   => '22465-59149-1',
                'CheckoutRequestID'   => 'ws_CO_000',
                'ResultCode'          => '0',
                'ResultDesc'          => 'The service request is processed successfully.',
            ]),
        ]);

        $result = Mpesa::stkQuery('ws_CO_000');
        $this->assertTrue($result->isCompleted());
    }

    public function test_stk_query_4999_is_pending_not_failed(): void
    {
        Http::fake([
            '*oauth/v1/generate*' => Http::response(['access_token' => 't']),
            '*stkpushquery/v1/query*' => Http::response([
                'ResponseCode'        => '0',
                'ResultCode'          => '4999',
                'ResultDesc'          => 'Still processing',
            ]),
        ]);

        $result = Mpesa::stkQuery('ws_CO_000');
        $this->assertTrue($result->isPending());
        $this->assertFalse($result->isFailed());
    }

    public function test_stk_query_customer_cancel_is_failed(): void
    {
        Http::fake([
            '*oauth/v1/generate*' => Http::response(['access_token' => 't']),
            '*stkpushquery/v1/query*' => Http::response([
                'ResponseCode' => '0',
                'ResultCode'   => '1032',
                'ResultDesc'   => 'Request cancelled by user',
            ]),
        ]);

        $result = Mpesa::stkQuery('ws_CO_000');
        $this->assertTrue($result->isFailed());
    }
}
