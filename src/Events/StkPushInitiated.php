<?php

declare(strict_types=1);

namespace FelixMuhoro\Mpesa\Events;

use FelixMuhoro\Mpesa\DTOs\StkPushResponse;

class StkPushInitiated
{
    public function __construct(
        public readonly string $phone,
        public readonly int $amount,
        public readonly string $reference,
        public readonly string $description,
        public readonly StkPushResponse $response,
        public readonly array $requestPayload = [],
    ) {}
}
