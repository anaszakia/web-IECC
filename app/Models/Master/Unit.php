<?php

namespace App\Models\Master;

use App\Models\Incident\IncidentAssignment;
use App\Models\Incident\UnitLocationSnapshot;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory, HasUlid;

    protected $fillable = [
        'ulid',
        'agency_id',
        'code',
        'type',
        'status',
        'crew_ready',
        'base_facility_id',
        'lat',
        'lng',
        'location',
        'last_seen_at',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'crew_ready' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function baseFacility()
    {
        return $this->belongsTo(Facility::class, 'base_facility_id');
    }

    public function members()
    {
        return $this->hasMany(UnitMember::class);
    }

    public function assignments()
    {
        return $this->hasMany(IncidentAssignment::class);
    }

    public function locationSnapshots()
    {
        return $this->hasMany(UnitLocationSnapshot::class);
    }
}
