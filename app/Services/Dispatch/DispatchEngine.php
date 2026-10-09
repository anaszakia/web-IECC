<?php

namespace App\Services\Dispatch;

use App\Models\Incident\DispatchRecommendation;
use App\Models\Incident\Incident;
use App\Models\Master\Facility;
use App\Models\Master\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DispatchEngine
{
    /**
     * Hitung rekomendasi unit untuk sebuah insiden berdasarkan rumus deterministik PRD
     */
    public function generateRecommendations(Incident $incident): array
    {
        $maxRadius = config('dispatch.max_radius_meters', 10000);
        $maxEta = config('dispatch.max_eta_seconds', 900);
        $weights = config('dispatch.weights');
        $avgSpeedKmh = config('dispatch.average_speed_kmh', 30);

        // Jika kategori UNKNOWN atau required_units eksplisit kosong (laporan belum diverifikasi dan AI menandai UNKNOWN), jangan generate armada
        $isOperatorVerified = $incident->severity_source === 'OPERATOR' || $incident->status === 'VERIFIED';

        if (!$isOperatorVerified && ($incident->category === 'UNKNOWN' || ($incident->aiAnalysis && empty($incident->aiAnalysis->required_units) && $incident->aiAnalysis->category === 'UNKNOWN'))) {
            DispatchRecommendation::where('incident_id', $incident->id)->delete();
            return [];
        }

        $defaultUnits = match(strtoupper($incident->category ?? '')) {
            'FIRE'     => ['fire_truck', 'ambulance'],
            'TRAFFIC'  => ['ambulance', 'police_patrol'],
            'SECURITY' => ['police_patrol'],
            'DISASTER' => ['rescue_team', 'ambulance'],
            'MEDICAL'  => ['ambulance'],
            default    => ['ambulance'],
        };

        // Jika diverifikasi oleh operator, prioritaskan armada sesuai kategori pilihan operator
        if ($isOperatorVerified && $incident->category !== 'UNKNOWN') {
            $requiredUnits = $defaultUnits;
        } else {
            // Jika ada indikasi kata kunci spesifik di deskripsi
            $desc = strtolower($incident->description ?? '');
            if (str_contains($desc, 'kebakaran') || str_contains($desc, 'api') || str_contains($desc, 'damkar')) {
                $defaultUnits = ['fire_truck', 'ambulance'];
            }

            $requiredUnits = $incident->aiAnalysis?->required_units ?? $defaultUnits;
        }

        if (empty($requiredUnits)) {
            DispatchRecommendation::where('incident_id', $incident->id)->delete();
            return [];
        }
        
        // Pemetaan nama tipe unit
        $typeMapping = [
            'ambulance'     => 'AMBULANCE',
            'fire_truck'    => 'FIRE_TRUCK',
            'police_patrol' => 'POLICE_PATROL',
            'rescue_team'   => 'RESCUE_TEAM',
            'traffic_unit'  => 'TRAFFIC_UNIT',
        ];

        $targetTypes = array_map(fn($t) => $typeMapping[strtolower($t)] ?? 'AMBULANCE', $requiredUnits);

        // 1. Cari kandidat unit yang AVAILABLE dalam radius (dengan Haversine formula)
        $candidates = Unit::whereIn('type', $targetTypes)
            ->whereIn('status', ['AVAILABLE', 'BUSY'])
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->get();

        if ($candidates->isEmpty()) {
            // Fallback: ambil semua unit bertipe sama tanpa filter status ketat
            $candidates = Unit::whereIn('type', $targetTypes)->whereNotNull('lat')->get();
        }

        // 2. Pilih RS tujuan jika kasus medis
        $destinationFacility = null;
        if ($incident->category === 'MEDICAL' || in_array('AMBULANCE', $targetTypes)) {
            $destinationFacility = Facility::where('type', 'HOSPITAL')
                ->where('is_active', true)
                ->where('er_status', '!=', 'FULL')
                ->first();
        }

        $recommendations = [];

        foreach ($candidates as $unit) {
            // Hitung jarak garis lurus (Haversine) dalam meter
            $distanceM = $this->calculateHaversineDistance($incident->lat, $incident->lng, $unit->lat, $unit->lng);
            
            // Estimasi ETA dalam detik (jarak * faktor koreksi 1.3 / kecepatan rata-rata m/s)
            $speedMps = ($avgSpeedKmh * 1000) / 3600;
            $etaSeconds = (int) round(($distanceM * 1.3) / $speedMps);

            // Komponen Skor (0 sampai 1)
            $sEta = 1 - min($etaSeconds / $maxEta, 1);
            $sDist = 1 - min($distanceM / $maxRadius, 1);
            $sStatus = $unit->status === 'AVAILABLE' ? 1.0 : ($unit->status === 'BUSY' ? 0.3 : 0.0);
            $sCrew = $unit->crew_ready ? 1.0 : 0.0;
            $sMatch = in_array($unit->type, $targetTypes) ? 1.0 : 0.5;
            $sHospital = $destinationFacility ? 1.0 : 0.5;

            // Hitung bobot
            $wEta = $weights['eta'];
            $wDist = $weights['distance'];
            $wStat = $weights['status'];
            $wCrew = $weights['crew'];
            $wType = $weights['match'];
            $wHosp = ($incident->category === 'MEDICAL') ? $weights['hospital'] : 0.0;

            if ($incident->category !== 'MEDICAL') {
                $wEta += $weights['hospital']; // Redistribusi bobot RS ke ETA
            }

            // Rumus: score = 100 * (w_eta * S_eta + w_dist * S_dist + ...)
            $totalScore = 100 * (
                ($wEta * $sEta) +
                ($wDist * $sDist) +
                ($wStat * $sStatus) +
                ($wCrew * $sCrew) +
                ($wType * $sMatch) +
                ($wHosp * $sHospital)
            );

            $recommendations[] = [
                'unit_id'                 => $unit->id,
                'score'                   => round($totalScore, 2),
                'distance_m'              => (int) $distanceM,
                'eta_seconds'             => $etaSeconds,
                'destination_facility_id' => $destinationFacility?->id,
                'score_breakdown'         => [
                    's_eta'      => round($sEta, 2),
                    's_dist'     => round($sDist, 2),
                    's_status'   => $sStatus,
                    's_crew'     => $sCrew,
                    's_match'    => $sMatch,
                    's_hospital' => $sHospital,
                ],
            ];
        }

        // Urutkan berdasarkan skor tertinggi
        usort($recommendations, fn($a, $b) => $b['score'] <=> $a['score']);

        // Simpan top 5 rekomendasi ke database
        DB::transaction(function () use ($incident, $recommendations) {
            // Hapus rekomendasi lama jika ada
            DispatchRecommendation::where('incident_id', $incident->id)->delete();

            $rank = 1;
            foreach (array_slice($recommendations, 0, 5) as $rec) {
                DispatchRecommendation::create([
                    'incident_id'             => $incident->id,
                    'unit_id'                 => $rec['unit_id'],
                    'rank_no'                 => $rank++,
                    'score'                   => $rec['score'],
                    'distance_m'              => $rec['distance_m'],
                    'eta_seconds'             => $rec['eta_seconds'],
                    'destination_facility_id' => $rec['destination_facility_id'],
                    'score_breakdown'         => $rec['score_breakdown'],
                    'chosen'                  => false,
                ]);
            }
        });

        return $recommendations;
    }

    /**
     * Hitung jarak dua titik koordinat GPS menggunakan formula Haversine (meter)
     */
    protected function calculateHaversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // Radius bumi dalam meter

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
