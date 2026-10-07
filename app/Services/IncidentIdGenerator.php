<?php

namespace App\Services;

use App\Models\Incident\Incident;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

class IncidentIdGenerator
{
    /**
     * Generate format: INC-YYYY-MM-DD-NNNNNN (e.g. INC-2026-10-04-000001)
     */
    public static function generate(): string
    {
        $dateStr = Carbon::now()->format('Y-m-d');
        $dateKey = Carbon::now()->format('Ymd');
        $redisKey = "incident:seq:{$dateKey}";

        try {
            // Coba gunakan Redis INCR untuk performa tinggi & atomic
            $seq = Redis::incr($redisKey);
            if ($seq === 1) {
                // Set TTL 2 hari (172800 detik)
                Redis::expire($redisKey, 172800);
            }
        } catch (\Throwable $e) {
            // Fallback ke hitungan database jika Redis offline
            $todayCount = Incident::whereDate('created_at', Carbon::today())->count();
            $seq = $todayCount + 1;
        }

        $formattedSeq = str_pad((string) $seq, 6, '0', STR_PAD_LEFT);

        return "INC-{$dateStr}-{$formattedSeq}";
    }
}
