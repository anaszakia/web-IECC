<?php

namespace App\Models\Incident;

use App\Models\User;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncidentMedia extends Model
{
    use HasFactory, HasUlid;

    protected $table = 'incident_media';

    protected $fillable = [
        'ulid',
        'incident_id',
        'uploaded_by',
        'type',
        'disk_path',
        'mime',
        'size_bytes',
        'transcript',
    ];

    public function incident()
    {
        return $this->belongsTo(Incident::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
