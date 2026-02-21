<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Service;
use App\Models\User;
use App\Models\Rdv;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $companyId = $user->company_id;

        $isAgent = (($user->role ?? '') === 'agent');

        // base query rdvs
        $rdvQ = Rdv::where('company_id', $companyId);
        if ($isAgent) {
            $rdvQ->where('agent_id', $user->id);
        }

        $todayStart = Carbon::today();
        $todayEnd = Carbon::today()->endOfDay();

        $stats = [
            // for admin only (optional)
            'clients' => $isAgent ? null : Client::where('company_id', $companyId)->count(),
            'services' => $isAgent ? null : Service::where('company_id', $companyId)->count(),
            'agents' => $isAgent ? null : User::where('company_id', $companyId)->where('role', 'agent')->count(),

            // always for both
            'rdv_total' => (clone $rdvQ)->count(),
            'rdv_today' => (clone $rdvQ)->whereBetween('start', [$todayStart, $todayEnd])->count(),
            'rdv_confirmed' => (clone $rdvQ)->where('status', 'confirme')->count(),
            'rdv_cancelled' => (clone $rdvQ)->where('status', 'annule')->count(),
        ];

        // chart: RDV this week (Mon..Sun) counts per day
        $startWeek = Carbon::now()->startOfWeek(); // Monday by default in many locales
        $endWeek = Carbon::now()->endOfWeek();

        $labels = [];
        $series = [];

        for ($d = $startWeek->copy(); $d->lte($endWeek); $d->addDay()) {
            $labels[] = $d->format('D'); // Mon Tue...
            $count = (clone $rdvQ)
                ->whereBetween('start', [$d->copy()->startOfDay(), $d->copy()->endOfDay()])
                ->count();
            $series[] = $count;
        }

        // next rdvs
        $nextRdvs = (clone $rdvQ)
            ->with(['client', 'service', 'agent'])
            ->where('start', '>=', Carbon::now())
            ->orderBy('start')
            ->limit(50)
            ->get();

        return response()->json([
            'stats' => $stats,
            'chart' => [
                'labels' => $labels,
                'series' => $series,
            ],
            'next_rdvs' => $nextRdvs,
        ]);
    }
}
