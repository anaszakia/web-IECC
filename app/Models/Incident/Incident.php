<?php

namespace App\Models\Incident;

use App\Models\Master\Agency;
use App\Models\Master\Facility;
use App\Models\Master\Unit;
use App\Models\User;
use App\Traits\HasUlid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    use HasFactory, HasUlid;

    protected $fillable = [
        'ulid',
        'incident_no',
        'reporter_id',
        'source',
        'category',
        'incident_type',
        'severity',
        'severity_source',
        'status',
        'description',
        'address_text',
        'district',
        'lat',
        'lng',
        'location',
        'location_accuracy_m',
        'victim_estimate',
        'duplicate_of_id',
        'ai_status',
        'reported_at',
        'verified_at',
        'dispatched_at',
        'first_accepted_at',
        'first_arrived_at',
        'resolved_at',
        'closed_at',
    ];

    protected $casts = [
        'lat' => 'float',
        'lng' => 'float',
        'severity' => 'integer',
        'victim_estimate' => 'integer',
        'reported_at' => 'datetime',
        'verified_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'first_accepted_at' => 'datetime',
        'first_arrived_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'NEW' => 'Laporan Baru',
            'VERIFIED' => 'Terverifikasi',
            'DISPATCHED' => 'Armada Ditugaskan',
            'ACCEPTED' => 'Ditangani Petugas',
            'ARRIVED' => 'Tiba di Lokasi',
            'RESOLVED' => 'Selesai Ditangani',
            'CLOSED' => 'Ditutup',
            'REJECTED' => 'Ditolak/Palsu',
            default => $this->status ?? 'Menunggu',
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'MEDICAL' => 'Medis / Kesehatan',
            'FIRE' => 'Kebakaran',
            'DISASTER' => 'Bencana Alam',
            'SECURITY' => 'Kamtibmas / Kriminal',
            'TRAFFIC' => 'Kecelakaan Lalu Lintas',
            default => 'Lainnya / Umum',
        };
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function media()
    {
        return $this->hasMany(IncidentMedia::class);
    }

    public function aiAnalysis()
    {
        return $this->hasOne(IncidentAiAnalysis::class);
    }

    public function assignments()
    {
        return $this->hasMany(IncidentAssignment::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(IncidentStatusLog::class);
    }

    public function dispatchRecommendations()
    {
        return $this->hasMany(DispatchRecommendation::class);
    }

    public function patientHandovers()
    {
        return $this->hasMany(PatientHandover::class);
    }

    public function duplicateOf()
    {
        return $this->belongsTo(Incident::class, 'duplicate_of_id');
    }

    public function duplicates()
    {
        return $this->hasMany(Incident::class, 'duplicate_of_id');
    }
}
