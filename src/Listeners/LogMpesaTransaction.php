<?php

declare(strict_types=1);

namespace FelixMuhoro\Mpesa\Listeners;

use FelixMuhoro\Mpesa\Events\PaymentFailed;
use FelixMuhoro\Mpesa\Events\PaymentSuccessful;
use FelixMuhoro\Mpesa\Events\StkPushInitiated;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\ConnectionResolverInterface as DB;
use Throwable;

/**
 * Persists STK Push + callback lifecycle into the `mpesa_transactions` table
 * shipped by the package migration.
 *
 * Writes are gated by config('mpesa.database.log_transactions'). On any
 * database error the listener swallows it — payment behavior must not be
 * affected by logging failures.
 */
class LogMpesaTransaction
{
    public function __construct(
        protected DB $db,
        protected ConfigRepository $config,
    ) {}

    public function onStkPushInitiated(StkPushInitiated $event): void
    {
        if (! $this->enabled()) {
            return;
        }

        $now = now();

        try {
            $this->table()->updateOrInsert(
                ['checkout_request_id' => $event->response->checkoutRequestId],
                [
                    'merchant_request_id' => $event->response->merchantRequestId,
                    'phone'               => $event->phone,
                    'amount'              => $event->amount,
                    'reference'           => $event->reference,
                    'description'         => $event->description,
                    'status'              => 'pending',
                    'type'                => 'stk',
                    'request_payload'     => json_encode($event->requestPayload),
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ]
            );
        } catch (Throwable) {
            // swallow — logging must not break payment flow
        }
    }

    public function onPaymentSuccessful(PaymentSuccessful $event): void
    {
        if (! $this->enabled()) {
            return;
        }

        $p = $event->payload;

        try {
            $this->table()
                ->where('checkout_request_id', $p->checkoutRequestId)
                ->update(array_filter([
                    'status'               => 'completed',
                    'result_code'          => $p->resultCode,
                    'result_desc'          => $p->resultDesc,
                    'mpesa_receipt_number' => $p->mpesaReceiptNumber,
                    'callback_payload'     => json_encode($p->raw),
                    'completed_at'         => now(),
                    'updated_at'           => now(),
                ], fn ($v) => $v !== null));
        } catch (Throwable) {
            // swallow
        }
    }

    public function onPaymentFailed(PaymentFailed $event): void
    {
        if (! $this->enabled()) {
            return;
        }

        $p = $event->payload;

        try {
            $this->table()
                ->where('checkout_request_id', $p->checkoutRequestId)
                ->update([
                    'status'           => 'failed',
                    'result_code'      => $p->resultCode,
                    'result_desc'      => $p->resultDesc,
                    'callback_payload' => json_encode($p->raw),
                    'completed_at'     => now(),
                    'updated_at'       => now(),
                ]);
        } catch (Throwable) {
            // swallow
        }
    }

    protected function enabled(): bool
    {
        return (bool) $this->config->get('mpesa.database.log_transactions', true);
    }

    protected function table()
    {
        return $this->db->connection()->table(
            $this->config->get('mpesa.database.table', 'mpesa_transactions')
        );
    }
}
