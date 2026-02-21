<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rdv extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'client_id',
        'service_id',
        'agent_id',
        'status',
        'notes',
        'motif_annulation',
        'start',
        'end',
        'series_id',
        'recurrence_freq',
        'recurrence_interval',
        'recurrence_count',
        'occurrence_index',
    ];

    protected $casts = [
        'start' => 'datetime',
        'end'   => 'datetime',
    ];

    public function client(){ return $this->belongsTo(Client::class); }
    public function service(){ return $this->belongsTo(Service::class); }
    public function agent(){ return $this->belongsTo(User::class, 'agent_id'); }


}
