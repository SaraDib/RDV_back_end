<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanySubscription;
use App\Models\Plan;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    // GET /api/subscription
    public function show(Request $request)
    {
        $companyId = $request->user()->company_id;

        $sub = CompanySubscription::with('plan')
            ->where('company_id', $companyId)
            ->orderByDesc('id')
            ->first();

        //
        if (!$sub) {
            $basic = Plan::where('code', 'basic')->first();
            return response()->json([
                'subscription' => null,
                'plan' => $basic,
                'status' => 'trial',
                'ends_at' => null,
                'is_active' => true,
                'limits' => $basic ? [
                    'max_agents' => $basic->max_agents,
                    'max_services' => $basic->max_services,
                    'max_rdvs_per_month' => $basic->max_rdvs_per_month,
                    'whatsapp_enabled' => (bool)$basic->whatsapp_enabled,
                ] : null,
            ]);
        }

        $now = Carbon::now();
        $expired = $sub->ends_at && $sub->ends_at->lt($now);

        $isActive = in_array($sub->status, ['trial','active'], true) && !$expired;

        return response()->json([
            'subscription' => [
                'id' => $sub->id,
                'status' => $sub->status,
                'starts_at' => optional($sub->starts_at)->toDateTimeString(),
                'ends_at' => optional($sub->ends_at)->toDateTimeString(),
            ],
            'plan' => $sub->plan,
            'is_active' => $isActive,
            'limits' => $sub->plan ? [
                'max_agents' => $sub->plan->max_agents,
                'max_services' => $sub->plan->max_services,
                'max_rdvs_per_month' => $sub->plan->max_rdvs_per_month,
                'whatsapp_enabled' => (bool)$sub->plan->whatsapp_enabled,
            ] : null,
        ]);
    }
}
