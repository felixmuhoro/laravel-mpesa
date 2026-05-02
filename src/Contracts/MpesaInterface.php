<?php

declare(strict_types=1);

namespace FelixMuhoro\Mpesa\Contracts;

use FelixMuhoro\Mpesa\DTOs\StkPushResponse;
use FelixMuhoro\Mpesa\DTOs\StkQueryResponse;

interface MpesaInterface
{
    public function getAccessToken(bool $fresh = false): string;

    public function stkPush(
        string $phone,
        int $amount,
        ?string $reference = null,
        ?string $description = null,
        ?string $callbackUrl = null
    ): StkPushResponse;

    public function stkQuery(string $checkoutRequestId): StkQueryResponse;

    public function c2bRegisterUrls(
        ?string $confirmationUrl = null,
        ?string $validationUrl = null,
        ?string $responseType = null
    ): array;

    public function c2bSimulate(string $phone, int $amount, string $billReference): array;

    public function b2cSend(
        string $phone,
        int $amount,
        string $commandId,
        string $remarks,
        ?string $occasion = null
    ): array;

    public function accountBalance(string $remarks = 'Balance query'): array;

    public function transactionStatus(
        string $transactionId,
        string $remarks = 'Status query',
        ?string $occasion = null
    ): array;

    public function reverse(
        string $transactionId,
        int $amount,
        string $remarks,
        ?string $occasion = null
    ): array;
}
