<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UINotification extends Model
{
    protected $table = 'ui_notifications';

    protected $fillable = [
        'company_id','rdv_id','type','title','body','status','action_url'
    ];

    public function rdv() { return $this->belongsTo(Rdv::class); }
}
