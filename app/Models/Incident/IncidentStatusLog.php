<?php

namespace App\Models\Incident;

use App\Models\User;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncidentStatusLog extends Model
{
    use HasFactory, HasUlid;

    protected $fillable = [
        'ulid',
        'incident_id',
        'assignment_id',
        'from_status',
        'to_status',
        'actor_id',
        'actor_type',
        'lat',
        'lng',
        'note',
        'occurred_at',
        'synced_at',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'occurred_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public function incident()
    {
        return $this->belongsTo(Incident::class);
    }

    public function assignment()
    {
        return $this->belongsTo(IncidentAssignment::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
