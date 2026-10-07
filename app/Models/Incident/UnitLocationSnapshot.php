<?php

namespace App\Models\Incident;

use App\Models\Master\Unit;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitLocationSnapshot extends Model
{
    use HasFactory, HasUlid;

    protected $fillable = [
        'ulid',
        'unit_id',
        'incident_id',
        'lat',
        'lng',
        'speed_kmh',
        'heading',
        'recorded_at',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'speed_kmh' => 'float',
        'heading' => 'integer',
        'recorded_at' => 'datetime',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function incident()
    {
        return $this->belongsTo(Incident::class);
    }
}
