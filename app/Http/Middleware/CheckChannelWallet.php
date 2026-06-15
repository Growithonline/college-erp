<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckChannelWallet
{
    public function handle(Request $request, Closure $next): Response
    {
        $partner = auth()->guard('partner')->user();

        if (!$partner) {
            return $next($request);
        }

        $wallet = $partner->wallet;

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
