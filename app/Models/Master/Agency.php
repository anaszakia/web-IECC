<?php

namespace App\Models\Master;

use App\Models\Incident\IncidentAssignment;
use App\Models\User;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Agency extends Model
{
    use HasFactory, HasUlid;

    protected $fillable = [
        'ulid',
        'code',
        'name',
        'type',
        'phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function units()
    {
        return $this->hasMany(Unit::class);
    }

    public function facilities()
    {
        return $this->hasMany(Facility::class);
    }

    public function incidentAssignments()
    {
        return $this->hasMany(IncidentAssignment::class);
    }
}
