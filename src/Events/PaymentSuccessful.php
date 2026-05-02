<?php

declare(strict_types=1);

namespace FelixMuhoro\Mpesa\Events;

use FelixMuhoro\Mpesa\DTOs\CallbackPayload;

class PaymentSuccessful
{
    public function __construct(
        public readonly CallbackPayload $payload,
    ) {}
}
