<?php

declare(strict_types=1);

namespace FelixMuhoro\Mpesa\DTOs;

use FelixMuhoro\Mpesa\Enums\ResultCode;

class StkQueryResponse
{
    public function __construct(
        public readonly string $status,           // completed | pending | failed
        public readonly string $resultCode,
        public readonly string $resultDesc,
        public readonly string $message,
        public readonly ?string $merchantRequestId = null,
        public readonly ?string $checkoutRequestId = null,
        public readonly array $raw = [],
    ) {}

    public static function fromArray(array $data): self
    {
        $responseCode = (string) ($data['ResponseCode'] ?? '');

        if ($responseCode !== '0') {
            return self::pending(
                $responseCode,
                (string) ($data['ResponseDescription'] ?? 'Payment is being processed')
            );
        }

        $resultCode = (string) ($data['ResultCode'] ?? '');
        $resolved = ResultCode::resolve($resultCode);

        return new self(
            status:             $resolved['status'],
            resultCode:         $resultCode,
            resultDesc:         (string) ($data['ResultDesc'] ?? ''),
            message:            $resolved['message'],
            merchantRequestId:  isset($data['MerchantRequestID']) ? (string) $data['MerchantRequestID'] : null,
            checkoutRequestId:  isset($data['CheckoutRequestID']) ? (string) $data['CheckoutRequestID'] : null,
            raw:                $data,
        );
    }

    public static function pending(string $code, string $message): self
    {
        return new self(
            status:     ResultCode::STATUS_PENDING,
            resultCode: $code,
            resultDesc: $message,
            message:    $message,
        );
    }

    public function isCompleted(): bool
    {
        return $this->status === ResultCode::STATUS_COMPLETED;
    }

    public function isPending(): bool
    {
        return $this->status === ResultCode::STATUS_PENDING;
    }

    public function isFailed(): bool
    {
        return $this->status === ResultCode::STATUS_FAILED;
    }
}
