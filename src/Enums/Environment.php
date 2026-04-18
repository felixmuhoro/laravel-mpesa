<?php

declare(strict_types=1);

namespace FelixMuhoro\Mpesa\Enums;

enum Environment: string
{
    case Sandbox = 'sandbox';
    case Production = 'production';
}
