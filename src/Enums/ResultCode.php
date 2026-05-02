<?php

declare(strict_types=1);

namespace FelixMuhoro\Mpesa\Enums;

/**
 * Exhaustive M-Pesa Daraja result code dictionary.
 *
 * Maps Safaricom's cryptic numeric codes into three outcomes:
 *  - completed  : terminal success
 *  - pending    : no final result yet, keep polling
 *  - failed     : terminal failure (stop polling)
 *
 * This is battle-tested against real production traffic. Notably:
 *  - Code "4999" is undocumented but means "still processing" (NOT failed)
 *  - Any unknown code is treated as pending, never failed
 */
class ResultCode
{
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PENDING   = 'pending';
    public const STATUS_FAILED    = 'failed';

    /**
     * @var array<string, array{status: string, message: string}>
     */
    protected static array $map = [
        '0'    => ['status' => self::STATUS_COMPLETED, 'message' => 'Payment completed successfully.'],
        '1'    => ['status' => self::STATUS_FAILED,    'message' => 'Insufficient M-Pesa balance. Please top up and try again.'],
        '2'    => ['status' => self::STATUS_FAILED,    'message' => 'Amount is below the minimum M-Pesa transaction limit (KES 1).'],
        '3'    => ['status' => self::STATUS_FAILED,    'message' => 'Amount exceeds the maximum M-Pesa transaction limit.'],
        '4'    => ['status' => self::STATUS_FAILED,    'message' => 'Would exceed your daily M-Pesa transfer limit (KES 500,000).'],
        '8'    => ['status' => self::STATUS_FAILED,    'message' => 'Would exceed the maximum account balance.'],
        '17'   => ['status' => self::STATUS_FAILED,    'message' => 'Duplicate transaction. Wait at least 2 minutes between requests for the same amount.'],
        '1019' => ['status' => self::STATUS_FAILED,    'message' => 'Transaction expired. Customer did not respond in time.'],
        '1025' => ['status' => self::STATUS_FAILED,    'message' => 'An error occurred sending the STK push. Please try again.'],
        '1032' => ['status' => self::STATUS_FAILED,    'message' => 'Customer cancelled the M-Pesa payment request.'],
        '1037' => ['status' => self::STATUS_FAILED,    'message' => 'Customer phone could not be reached. Check network and retry.'],
        '2001' => ['status' => self::STATUS_FAILED,    'message' => 'Wrong M-Pesa PIN entered.'],
        '2028' => ['status' => self::STATUS_FAILED,    'message' => 'Payment configuration error on the merchant side.'],
        '8006' => ['status' => self::STATUS_FAILED,    'message' => 'Customer M-Pesa account is locked. Safaricom must unlock it (100 / 200).'],
        '4999' => ['status' => self::STATUS_PENDING,   'message' => 'Transaction is still being processed.'],
    ];

    public static function resolve(string|int|null $code): array
    {
        $key = (string) $code;

        if ($key === '' || $key === 'null') {
            return ['status' => self::STATUS_PENDING, 'message' => 'Waiting for payment confirmation...', 'code' => $key];
        }

        if (isset(self::$map[$key])) {
            return self::$map[$key] + ['code' => $key];
        }

        // Any unknown code is treated as pending — NEVER assume failure for unknown codes.
        return [
            'status'  => self::STATUS_PENDING,
            'message' => "Transaction status pending (code $key).",
            'code'    => $key,
        ];
    }

    public static function isCompleted(string|int|null $code): bool
    {
        return self::resolve($code)['status'] === self::STATUS_COMPLETED;
    }

    public static function isFailed(string|int|null $code): bool
    {
        return self::resolve($code)['status'] === self::STATUS_FAILED;
    }

    public static function isPending(string|int|null $code): bool
    {
        return self::resolve($code)['status'] === self::STATUS_PENDING;
    }
}
