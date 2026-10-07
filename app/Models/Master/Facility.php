<?php

namespace App\Models\Master;

use App\Models\Incident\PatientHandover;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    use HasFactory, HasUlid;

    protected $fillable = [
        'ulid',
        'agency_id',
        'type',
        'name',
        'address',
        'phone',
        'lat',
        'lng',
        'location',
        'services',
        'er_beds_total',
        'er_beds_available',
        'er_status',
        'er_updated_at',
        'is_active',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'services' => 'array',
        'er_beds_total' => 'integer',
        'er_beds_available' => 'integer',
        'er_updated_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function units()
    {
        return $this->hasMany(Unit::class, 'base_facility_id');
    }

    public function patientHandovers()
    {
        return $this->hasMany(PatientHandover::class);
    }
}
