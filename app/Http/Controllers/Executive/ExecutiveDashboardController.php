<?php

namespace App\Http\Controllers\Executive;

use App\Http\Controllers\Controller;
use App\Models\Incident\Incident;
use App\Models\Incident\IncidentAssignment;
use App\Models\Master\Agency;
use App\Models\Master\Unit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExecutiveDashboardController extends Controller
{
    /**
     * Executive Dashboard untuk Walikota / Pimpinan Daerah (E-01 s/d E-04 & Bagian 20 KPI)
     */
    public function index(Request $request): View
    {
        $today = Carbon::today();
        $startOfWeek = Carbon::now()->startOfWeek();

        // 1. Ringkasan Insiden
        $totalIncidents = Incident::count();
        $todayIncidents = Incident::whereDate('reported_at', $today)->count();
        $criticalIncidents = Incident::where('severity', 4)->count();
        $resolvedIncidents = Incident::where('status', 'RESOLVED')->orWhere('status', 'CLOSED')->count();

        // 2. Perhitungan Metrik KPI Response Time (Detik ke Menit)
        $avgResponseMinutes = 0;
        $resolvedRows = Incident::whereNotNull('first_arrived_at')
            ->whereNotNull('reported_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, reported_at, first_arrived_at)) as avg_seconds')
            ->first();

        if ($resolvedRows && $resolvedRows->avg_seconds) {
            $avgResponseMinutes = round($resolvedRows->avg_seconds / 60, 1);
        }

        // 3. Ketersediaan Armada per Instansi (Available vs Busy)
        $unitsStats = Unit::select('type', 'status', DB::raw('count(*) as count'))
            ->groupBy('type', 'status')
            ->get();

        $agencies = Agency::withCount(['units', 'incidentAssignments'])->get();

        // 4. Tren Insiden 7 Hari Terakhir
        $dailyTrend = Incident::where('reported_at', '>=', Carbon::now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(reported_at) as date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->pluck('count', 'date')
            ->toArray();

        // 5. Komposisi Kategori Insiden
        $categoryBreakdown = Incident::select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        // 6. Data Titik Heatmap untuk Peta Sebaran
        $heatmapPoints = Incident::select('lat', 'lng', 'severity', 'category')
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->limit(200)
            ->get();

        return view('executive.index', compact(
            'totalIncidents',
            'todayIncidents',
            'criticalIncidents',
            'resolvedIncidents',
            'avgResponseMinutes',
            'unitsStats',
            'agencies',
            'dailyTrend',
            'categoryBreakdown',
            'heatmapPoints'
        ));
    }
}
