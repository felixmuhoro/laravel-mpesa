<?php

declare(strict_types=1);

namespace FelixMuhoro\Mpesa\Http\Middleware;

use Closure;
use FelixMuhoro\Mpesa\Exceptions\InvalidCallbackException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyMpesaCallback
{
    public function handle(Request $request, Closure $next)
    {
        $config = config('mpesa.callback', []);
        $allowedIps = $config['allowed_ips'] ?? [];
        $secretKey = $config['secret_query_key'] ?? null;
        $secretParam = $config['secret_query_param'] ?? 'key';

        if ($secretKey !== null && $secretKey !== '') {
            $incoming = $request->query($secretParam);
            if (! is_string($incoming) || ! hash_equals($secretKey, $incoming)) {
                Log::warning('M-Pesa callback rejected: bad secret key', ['ip' => $request->ip()]);
                throw new InvalidCallbackException('Invalid callback secret.');
            }
        }

        if (! empty($allowedIps) && ! in_array($request->ip(), $allowedIps, true)) {
            Log::warning('M-Pesa callback rejected: IP not allow-listed', [
                'ip'      => $request->ip(),
                'allowed' => $allowedIps,
            ]);
            throw new InvalidCallbackException('Callback source IP not allow-listed.');
        }

        return $next($request);
    }
}
