<?php

declare(strict_types=1);

namespace FelixMuhoro\Mpesa\DTOs;

/**
 * Parsed view of the STK Push callback Safaricom POSTs to your CallBackURL.
 *
 * Shape (raw): { Body: { stkCallback: { MerchantRequestID, CheckoutRequestID, ResultCode, ResultDesc, CallbackMetadata?: { Item: [{Name, Value}, ...] } } } }
 */
class CallbackPayload
{
    public function __construct(
        public readonly string $merchantRequestId,
        public readonly string $checkoutRequestId,
        public readonly string $resultCode,
        public readonly string $resultDesc,
        public readonly ?string $mpesaReceiptNumber,
        public readonly ?float $amount,
        public readonly ?string $phoneNumber,
        public readonly ?string $transactionDate,
        public readonly array $raw = [],
    ) {}

    public static function fromArray(array $payload): self
    {
        $cb = $payload['Body']['stkCallback'] ?? [];
        $metadata = [];

        foreach ($cb['CallbackMetadata']['Item'] ?? [] as $item) {
            if (isset($item['Name'])) {
                $metadata[$item['Name']] = $item['Value'] ?? null;
            }
        }

        return new self(
            merchantRequestId:  (string) ($cb['MerchantRequestID'] ?? ''),
            checkoutRequestId:  (string) ($cb['CheckoutRequestID'] ?? ''),
            resultCode:         (string) ($cb['ResultCode'] ?? ''),
            resultDesc:         (string) ($cb['ResultDesc'] ?? ''),
            mpesaReceiptNumber: isset($metadata['MpesaReceiptNumber']) ? (string) $metadata['MpesaReceiptNumber'] : null,
            amount:             isset($metadata['Amount']) ? (float) $metadata['Amount'] : null,
            phoneNumber:        isset($metadata['PhoneNumber']) ? (string) $metadata['PhoneNumber'] : null,
            transactionDate:    isset($metadata['TransactionDate']) ? (string) $metadata['TransactionDate'] : null,
            raw:                $payload,
        );
    }

    public function successful(): bool
    {
        return $this->resultCode === '0';
    }
}
