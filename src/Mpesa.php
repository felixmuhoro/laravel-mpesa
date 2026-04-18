<?php

declare(strict_types=1);

namespace FelixMuhoro\Mpesa;

use FelixMuhoro\Mpesa\Contracts\MpesaInterface;
use FelixMuhoro\Mpesa\DTOs\StkPushResponse;
use FelixMuhoro\Mpesa\DTOs\StkQueryResponse;
use FelixMuhoro\Mpesa\Enums\Environment;
use FelixMuhoro\Mpesa\Enums\ResultCode;
use FelixMuhoro\Mpesa\Exceptions\AuthException;
use FelixMuhoro\Mpesa\Exceptions\MpesaException;
use FelixMuhoro\Mpesa\Support\PhoneNumber;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;

class Mpesa implements MpesaInterface
{
    protected Environment $environment;

    public function __construct(
        protected array $config,
        protected CacheFactory $cache,
        protected ?HttpFactory $http = null
    ) {
        $this->environment = Environment::from($this->config['environment'] ?? 'sandbox');
    }

    // ─────────────────────────────────────────────────────────────
    //  OAuth
    // ─────────────────────────────────────────────────────────────

    public function getAccessToken(bool $fresh = false): string
    {
        $store = $this->cache->store($this->config['cache']['store'] ?? null);
        $key = $this->config['cache']['key'] ?? 'mpesa:access_token';

        if ($fresh) {
            $store->forget($key);
        }

        return $store->remember($key, $this->config['cache']['ttl'] ?? 3500, function () {
            return $this->fetchAccessToken();
        });
    }

    protected function fetchAccessToken(): string
    {
        $consumerKey = $this->config['consumer_key'] ?? null;
        $consumerSecret = $this->config['consumer_secret'] ?? null;

        if (! $consumerKey || ! $consumerSecret) {
            throw new AuthException(
                'M-Pesa consumer_key / consumer_secret are not configured. Set MPESA_CONSUMER_KEY and MPESA_CONSUMER_SECRET.'
            );
        }

        $credentials = base64_encode("$consumerKey:$consumerSecret");

        $response = $this->client()
            ->withHeaders(['Authorization' => "Basic $credentials"])
            ->get($this->url('oauth'));

        if (! $response->successful() || ! $response->json('access_token')) {
            throw new AuthException(
                'Could not retrieve M-Pesa OAuth token: ' . $response->body(),
                $response->status()
            );
        }

        return $response->json('access_token');
    }

    // ─────────────────────────────────────────────────────────────
    //  STK Push (Lipa Na M-Pesa Online)
    // ─────────────────────────────────────────────────────────────

    public function stkPush(
        string $phone,
        int $amount,
        ?string $reference = null,
        ?string $description = null,
        ?string $callbackUrl = null
    ): StkPushResponse {
        $phone = PhoneNumber::format($phone);
        $shortCode = $this->config['stk']['business_short_code'];
        $passkey = $this->config['stk']['passkey'];
        $partyB = $this->config['stk']['party_b'] ?? $shortCode;
        $timestamp = now()->format('YmdHis');
        $password = base64_encode("$shortCode$passkey$timestamp");

        $payload = [
            'BusinessShortCode' => $shortCode,
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'TransactionType'   => $this->config['stk']['transaction_type'] ?? 'CustomerPayBillOnline',
            'Amount'            => $amount,
            'PartyA'            => $phone,
            'PartyB'            => $partyB,
            'PhoneNumber'       => $phone,
            'CallBackURL'       => $callbackUrl ?? $this->config['stk']['callback_url'],
            'AccountReference'  => $reference ?? $this->config['stk']['default_reference'] ?? 'Payment',
            'TransactionDesc'   => $description ?? $this->config['stk']['default_description'] ?? 'Payment',
        ];

        $response = $this->authedClient()->post($this->url('stk_push'), $payload);
        $data = $response->json() ?? [];

        if (! $response->successful()) {
            throw new MpesaException(
                'STK Push rejected: ' . ($data['errorMessage'] ?? $response->body()),
                $response->status()
            );
        }

        if ((string) ($data['ResponseCode'] ?? '') !== '0') {
            throw new MpesaException(
                'STK Push rejected: ' . ($data['ResponseDescription'] ?? 'Unknown error'),
                422
            );
        }

        return StkPushResponse::fromArray($data);
    }

    public function stkQuery(string $checkoutRequestId): StkQueryResponse
    {
        $shortCode = $this->config['stk']['business_short_code'];
        $passkey = $this->config['stk']['passkey'];
        $timestamp = now()->format('YmdHis');
        $password = base64_encode("$shortCode$passkey$timestamp");

        $response = $this->authedClient()->post($this->url('stk_query'), [
            'BusinessShortCode' => $shortCode,
            'Password'          => $password,
            'Timestamp'         => $timestamp,
            'CheckoutRequestID' => $checkoutRequestId,
        ]);

        $data = $response->json() ?? [];

        // HTTP errors while polling are transient — treat as pending.
        if (! $response->successful()) {
            return StkQueryResponse::pending(
                $data['errorCode'] ?? (string) $response->status(),
                $data['errorMessage'] ?? 'Query failed, will retry'
            );
        }

        return StkQueryResponse::fromArray($data);
    }

    // ─────────────────────────────────────────────────────────────
    //  C2B
    // ─────────────────────────────────────────────────────────────

    public function c2bRegisterUrls(
        ?string $confirmationUrl = null,
        ?string $validationUrl = null,
        ?string $responseType = null
    ): array {
        $payload = array_filter([
            'ShortCode'       => $this->config['c2b']['short_code'],
            'ResponseType'    => $responseType ?? $this->config['c2b']['response_type'] ?? 'Completed',
            'ConfirmationURL' => $confirmationUrl ?? $this->config['c2b']['confirmation_url'],
            'ValidationURL'   => $validationUrl ?? $this->config['c2b']['validation_url'],
        ]);

        return $this->authedClient()->post($this->url('c2b_register'), $payload)->json() ?? [];
    }

    public function c2bSimulate(string $phone, int $amount, string $billReference): array
    {
        if ($this->environment === Environment::Production) {
            throw new MpesaException('C2B simulate is only available in sandbox.');
        }

        return $this->authedClient()->post($this->url('c2b_simulate'), [
            'ShortCode'     => $this->config['c2b']['short_code'],
            'CommandID'     => 'CustomerPayBillOnline',
            'Amount'        => $amount,
            'Msisdn'        => PhoneNumber::format($phone),
            'BillRefNumber' => $billReference,
        ])->json() ?? [];
    }

    // ─────────────────────────────────────────────────────────────
    //  B2C
    // ─────────────────────────────────────────────────────────────

    public function b2cSend(
        string $phone,
        int $amount,
        string $commandId,
        string $remarks,
        ?string $occasion = null
    ): array {
        return $this->authedClient()->post($this->url('b2c_payment'), [
            'InitiatorName'      => $this->config['b2c']['initiator_name'],
            'SecurityCredential' => $this->config['b2c']['security_credential'],
            'CommandID'          => $commandId, // SalaryPayment | BusinessPayment | PromotionPayment
            'Amount'             => $amount,
            'PartyA'             => $this->config['b2c']['short_code'],
            'PartyB'             => PhoneNumber::format($phone),
            'Remarks'            => $remarks,
            'QueueTimeOutURL'    => $this->config['b2c']['queue_timeout_url'],
            'ResultURL'          => $this->config['b2c']['result_url'],
            'Occasion'           => $occasion ?? '',
        ])->json() ?? [];
    }

    // ─────────────────────────────────────────────────────────────
    //  Account balance / transaction status / reversals
    // ─────────────────────────────────────────────────────────────

    public function accountBalance(string $remarks = 'Balance query'): array
    {
        return $this->authedClient()->post($this->url('balance'), [
            'Initiator'          => $this->config['b2c']['initiator_name'],
            'SecurityCredential' => $this->config['b2c']['security_credential'],
            'CommandID'          => 'AccountBalance',
            'PartyA'             => $this->config['b2c']['short_code'],
            'IdentifierType'     => '4',
            'Remarks'            => $remarks,
            'QueueTimeOutURL'    => $this->config['b2c']['queue_timeout_url'],
            'ResultURL'          => $this->config['b2c']['result_url'],
        ])->json() ?? [];
    }

    public function transactionStatus(
        string $transactionId,
        string $remarks = 'Status query',
        ?string $occasion = null
    ): array {
        return $this->authedClient()->post($this->url('status'), [
            'Initiator'          => $this->config['b2c']['initiator_name'],
            'SecurityCredential' => $this->config['b2c']['security_credential'],
            'CommandID'          => 'TransactionStatusQuery',
            'TransactionID'      => $transactionId,
            'PartyA'             => $this->config['b2c']['short_code'],
            'IdentifierType'     => '4',
            'Remarks'            => $remarks,
            'QueueTimeOutURL'    => $this->config['b2c']['queue_timeout_url'],
            'ResultURL'          => $this->config['b2c']['result_url'],
            'Occasion'           => $occasion ?? '',
        ])->json() ?? [];
    }

    public function reverse(
        string $transactionId,
        int $amount,
        string $remarks,
        ?string $occasion = null
    ): array {
        return $this->authedClient()->post($this->url('reversal'), [
            'Initiator'              => $this->config['b2c']['initiator_name'],
            'SecurityCredential'     => $this->config['b2c']['security_credential'],
            'CommandID'              => 'TransactionReversal',
            'TransactionID'          => $transactionId,
            'Amount'                 => $amount,
            'ReceiverParty'          => $this->config['b2c']['short_code'],
            'RecieverIdentifierType' => '11',
            'Remarks'                => $remarks,
            'QueueTimeOutURL'        => $this->config['b2c']['queue_timeout_url'],
            'ResultURL'              => $this->config['b2c']['result_url'],
            'Occasion'               => $occasion ?? '',
        ])->json() ?? [];
    }

    // ─────────────────────────────────────────────────────────────
    //  Internals
    // ─────────────────────────────────────────────────────────────

    protected function client()
    {
        return ($this->http ? $this->http : Http::getFacadeRoot())
            ->timeout($this->config['http']['timeout'] ?? 30)
            ->connectTimeout($this->config['http']['connect_timeout'] ?? 10)
            ->retry($this->config['http']['retries'] ?? 1, 200)
            ->acceptJson();
    }

    protected function authedClient()
    {
        return $this->client()->withToken($this->getAccessToken());
    }

    protected function url(string $endpoint): string
    {
        $env = $this->environment->value;
        $endpoints = $this->config['endpoints'][$env] ?? null;

        if (! $endpoints || ! isset($endpoints[$endpoint])) {
            throw new MpesaException("Unknown M-Pesa endpoint: $endpoint");
        }

        return rtrim($endpoints['base'], '/') . $endpoints[$endpoint];
    }
}
