<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::updateOrCreate(
            ['code' => 'basic'],
            [
                'name' => 'Basic',
                'price_monthly' => 0,
                'max_agents' => 2,
                'max_services' => 10,
                'max_rdvs_per_month' => 200,
                'whatsapp_enabled' => false,
                'is_active' => true,
            ]
        );

        Plan::updateOrCreate(
            ['code' => 'pro'],
            [
                'name' => 'Pro',
                'price_monthly' => 199,
                'max_agents' => 10,
                'max_services' => 50,
                'max_rdvs_per_month' => 2000,
                'whatsapp_enabled' => true,
                'is_active' => true,
            ]
        );

        Plan::updateOrCreate(
            ['code' => 'enterprise'],
            [
                'name' => 'Enterprise',
                'price_monthly' => 999,
                'max_agents' => null,
                'max_services' => null,
                'max_rdvs_per_month' => null,
                'whatsapp_enabled' => true,
                'is_active' => true,
            ]
        );
    }
}
