<?php

namespace App\Models\Incident;

use App\Models\Master\Agency;
use App\Models\Master\Unit;
use App\Models\User;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncidentAssignment extends Model
{
    use HasFactory, HasUlid;

    protected $fillable = [
        'ulid',
        'incident_id',
        'agency_id',
        'unit_id',
        'assigned_by',
        'status',
        'reject_reason',
        'eta_seconds',
        'distance_m',
        'dispatched_at',
        'accepted_at',
        'en_route_at',
        'arrived_at',
        'resolved_at',
    ];

    protected $casts = [
        'eta_seconds' => 'integer',
        'distance_m' => 'integer',
        'dispatched_at' => 'datetime',
        'accepted_at' => 'datetime',
        'en_route_at' => 'datetime',
        'arrived_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function incident()
    {
        return $this->belongsTo(Incident::class);
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function assignedByUser()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function statusLogs()
    {
        return $this->hasMany(IncidentStatusLog::class, 'assignment_id');
    }

    public function patientHandovers()
    {
        return $this->hasMany(PatientHandover::class, 'assignment_id');
    }
}
