<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'code','name','price_monthly',
        'max_agents','max_services','max_rdvs_per_month',
        'whatsapp_enabled','is_active'
    ];
}
