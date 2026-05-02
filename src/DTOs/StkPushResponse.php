<?php

declare(strict_types=1);

namespace FelixMuhoro\Mpesa\DTOs;

class StkPushResponse
{
    public function __construct(
        public readonly string $merchantRequestId,
        public readonly string $checkoutRequestId,
        public readonly string $responseCode,
        public readonly string $responseDescription,
        public readonly string $customerMessage,
        public readonly array $raw = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            merchantRequestId:   (string) ($data['MerchantRequestID'] ?? ''),
            checkoutRequestId:   (string) ($data['CheckoutRequestID'] ?? ''),
            responseCode:        (string) ($data['ResponseCode'] ?? ''),
            responseDescription: (string) ($data['ResponseDescription'] ?? ''),
            customerMessage:     (string) ($data['CustomerMessage'] ?? ''),
            raw:                 $data,
        );
    }

    public function accepted(): bool
    {
        return $this->responseCode === '0';
    }
}
