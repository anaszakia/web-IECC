<?php

namespace App\Models\Incident;

use App\Models\Master\Facility;
use App\Models\User;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientHandover extends Model
{
    use HasFactory, HasUlid;

    protected $fillable = [
        'ulid',
        'incident_id',
        'assignment_id',
        'facility_id',
        'gender',
        'age_estimate',
        'condition_text',
        'consciousness',
        'requested_services',
        'eta_seconds',
        'notified_at',
        'received_at',
        'received_by',
    ];

    protected $casts = [
        'age_estimate' => 'integer',
        'condition_text' => 'encrypted',
        'requested_services' => 'array',
        'eta_seconds' => 'integer',
        'notified_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function incident()
    {
        return $this->belongsTo(Incident::class);
    }

    public function assignment()
    {
        return $this->belongsTo(IncidentAssignment::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
