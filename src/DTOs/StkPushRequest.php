<?php

declare(strict_types=1);

namespace FelixMuhoro\Mpesa\DTOs;

class StkPushRequest
{
    public function __construct(
        public readonly string $phone,
        public readonly int $amount,
        public readonly ?string $reference = null,
        public readonly ?string $description = null,
        public readonly ?string $callbackUrl = null,
    ) {}
}
