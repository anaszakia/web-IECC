<?php

namespace App\Models\Incident;

use App\Models\Master\Facility;
use App\Models\Master\Unit;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DispatchRecommendation extends Model
{
    use HasFactory, HasUlid;

    protected $fillable = [
        'ulid',
        'incident_id',
        'unit_id',
        'rank_no',
        'score',
        'distance_m',
        'eta_seconds',
        'destination_facility_id',
        'score_breakdown',
        'chosen',
    ];

    protected $casts = [
        'rank_no' => 'integer',
        'score' => 'float',
        'distance_m' => 'integer',
        'eta_seconds' => 'integer',
        'score_breakdown' => 'array',
        'chosen' => 'boolean',
    ];

    public function incident()
    {
        return $this->belongsTo(Incident::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function destinationFacility()
    {
        return $this->belongsTo(Facility::class, 'destination_facility_id');
    }
}
