<?php

namespace App\Models\Incident;

use App\Models\User;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncidentAiAnalysis extends Model
{
    use HasFactory, HasUlid;

    protected $fillable = [
        'ulid',
        'incident_id',
        'provider',
        'model',
        'category',
        'incident_type',
        'severity',
        'victim_estimate',
        'critical_victim',
        'required_units',
        'summary',
        'first_aid_key',
        'confidence',
        'raw_response',
        'latency_ms',
        'token_input',
        'token_output',
        'error_message',
        'overridden_by',
    ];

    protected $casts = [
        'critical_victim' => 'boolean',
        'required_units' => 'array',
        'raw_response' => 'array',
        'confidence' => 'float',
    ];

    public function incident()
    {
        return $this->belongsTo(Incident::class);
    }

    public function overriddenByUser()
    {
        return $this->belongsTo(User::class, 'overridden_by');
    }
}
