<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Plan;
use Carbon\Carbon;

class CompanySubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $basic = Plan::where('code', 'basic')->first();
        if (!$basic) return;

        $companies = DB::table('companies')->select('id')->get();

        foreach ($companies as $c) {
            DB::table('company_subscriptions')->updateOrInsert(
                ['company_id' => $c->id],
                [
                    'plan_id' => $basic->id,
                    'status' => 'trial',
                    'starts_at' => Carbon::now(),
                    'ends_at' => Carbon::now()->addDays(14),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
            );
        }
    }
}
