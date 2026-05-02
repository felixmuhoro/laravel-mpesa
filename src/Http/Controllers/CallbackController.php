<?php

declare(strict_types=1);

namespace FelixMuhoro\Mpesa\Http\Controllers;

use FelixMuhoro\Mpesa\DTOs\CallbackPayload;
use FelixMuhoro\Mpesa\Events\PaymentFailed;
use FelixMuhoro\Mpesa\Events\PaymentSuccessful;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class CallbackController extends Controller
{
    /**
     * STK Push callback — Safaricom POSTs here after the customer
     * approves / cancels the STK prompt on their handset.
     */
    public function stk(Request $request): JsonResponse
    {
        $payload = CallbackPayload::fromArray($request->all());

        Log::info('mpesa.callback.stk', [
            'checkout'  => $payload->checkoutRequestId,
            'merchant'  => $payload->merchantRequestId,
            'result'    => $payload->resultCode,
            'desc'      => $payload->resultDesc,
            'receipt'   => $payload->mpesaReceiptNumber,
        ]);

        if ($payload->successful()) {
            event(new PaymentSuccessful($payload));
        } else {
            event(new PaymentFailed($payload));
        }

        // Safaricom expects a success acknowledgment even on failed payments —
        // the ResultCode inside the payload represents the payment outcome.
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    /**
     * C2B Validation — optional; only invoked if you registered a ValidationURL
     * AND your paybill has external validation enabled via Safaricom.
     */
    public function c2bValidate(Request $request): JsonResponse
    {
        Log::info('mpesa.callback.c2b.validate', $request->all());

        // Accept everything by default. Override this route in your app to add
        // business rules (e.g. reject unknown bill refs).
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    /**
     * C2B Confirmation — paybill/till receipt notifications.
     */
    public function c2bConfirm(Request $request): JsonResponse
    {
        Log::info('mpesa.callback.c2b.confirm', $request->all());

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    /**
     * B2C Result callback.
     */
    public function b2cResult(Request $request): JsonResponse
    {
        Log::info('mpesa.callback.b2c.result', $request->all());

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    /**
     * B2C Queue Timeout callback — Safaricom could not queue the request in time.
     */
    public function b2cTimeout(Request $request): JsonResponse
    {
        Log::warning('mpesa.callback.b2c.timeout', $request->all());

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}
