<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCenterWallet
{
    public function handle(Request $request, Closure $next): Response
    {
        $center = auth()->guard('center')->user();

        if (!$center) {
            return $next($request);
        }

        $wallet = $center->wallet;

        if (!$wallet) {
            return $next($request);
        }

        $status = $wallet->getBlockStatus();

        if ($status['blocked']) {
            if ($request->expectsJson()) {
                return response()->json([
                    'wallet_blocked' => true,
                    'type'           => $status['type'],
                    'message'        => $status['reason'],
                ], 403);
            }

            session()->flash('wallet_blocked', $status);
        }

        return $next($request);
    }
}
