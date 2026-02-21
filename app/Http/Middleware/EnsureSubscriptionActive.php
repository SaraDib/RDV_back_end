<?php

namespace App\Http\Middleware;

use App\Models\CompanySubscription;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;

class EnsureSubscriptionActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user || !$user->company_id) return $next($request);

        $sub = CompanySubscription::where('company_id', $user->company_id)
            ->orderByDesc('id')
            ->first();

        //
        if (!$sub) return $next($request);

        $now = Carbon::now();
        $expired = $sub->ends_at && $sub->ends_at->lt($now);

        $isActive = in_array($sub->status, ['trial','active'], true) && !$expired;

        if (!$isActive) {
            return response()->json([
                'message' => "Subscription expired. Please renew to continue."
            ], 402); // Payment Required
        }

        return $next($request);
    }
}
