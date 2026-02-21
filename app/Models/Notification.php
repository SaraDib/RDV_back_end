<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'company_id','rdv_id','type','status','phone','message','tries'
    ];

    public function rdv() { return $this->belongsTo(Rdv::class); }
}
