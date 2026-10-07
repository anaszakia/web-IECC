<?php

namespace App\Models\System;

use App\Models\User;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory, HasUlid;

    protected $fillable = [
        'ulid',
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'ip_address',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
