<?php

namespace App\Models\Master;

use App\Models\User;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitMember extends Model
{
    use HasFactory, HasUlid;

    protected $fillable = [
        'ulid',
        'unit_id',
        'user_id',
        'role',
        'on_duty',
    ];

    protected $casts = [
        'on_duty' => 'boolean',
    ];

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
