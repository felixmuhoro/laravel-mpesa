<?php

declare(strict_types=1);

namespace FelixMuhoro\Mpesa\Facades;

use FelixMuhoro\Mpesa\Contracts\MpesaInterface;
use FelixMuhoro\Mpesa\DTOs\StkPushResponse;
use FelixMuhoro\Mpesa\DTOs\StkQueryResponse;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string getAccessToken(bool $fresh = false)
 * @method static StkPushResponse stkPush(string $phone, int $amount, ?string $reference = null, ?string $description = null, ?string $callbackUrl = null)
 * @method static StkQueryResponse stkQuery(string $checkoutRequestId)
 * @method static array c2bRegisterUrls(?string $confirmationUrl = null, ?string $validationUrl = null, ?string $responseType = null)
 * @method static array c2bSimulate(string $phone, int $amount, string $billReference)
 * @method static array b2cSend(string $phone, int $amount, string $commandId, string $remarks, ?string $occasion = null)
 * @method static array accountBalance(string $remarks = 'Balance query')
 * @method static array transactionStatus(string $transactionId, string $remarks = 'Status query', ?string $occasion = null)
 * @method static array reverse(string $transactionId, int $amount, string $remarks, ?string $occasion = null)
 */
class Mpesa extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MpesaInterface::class;
    }
}
