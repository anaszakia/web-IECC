<?php

namespace App\Http\Controllers\Incident;

use App\Http\Controllers\Controller;
use App\Models\Incident\Incident;
use App\Models\Master\Agency;
use App\Models\Master\Facility;
use App\Models\Master\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommandCenterController extends Controller
{
    /**
     * Tampilan Utama Command Center (Peta Kota + Antrean Insiden + Statistik Cepat)
     */
    public function index(Request $request): View
    {
        $statusFilter = $request->query('status');
        $categoryFilter = $request->query('category');

        $incidentsQuery = Incident::with(['media', 'assignments.unit', 'aiAnalysis'])
            ->orderBy('reported_at', 'desc');

        if ($statusFilter) {
            $incidentsQuery->where('status', $statusFilter);
        } else {
            // Default: Tampilkan insiden aktif (belum selesai/tutup)
            $incidentsQuery->whereNotIn('status', ['CLOSED', 'CANCELLED', 'DUPLICATE', 'FALSE_REPORT']);
        }

        if ($categoryFilter) {
            $incidentsQuery->where('category', $categoryFilter);
        }

        $activeIncidents = $incidentsQuery->limit(50)->get();

        // Data Fasilitas & Unit untuk layer peta
        $facilities = Facility::where('is_active', true)->get();
        $units = Unit::with('agency')->get();

        // Statistik Cepat Hari Ini
        $todayStats = [
            'total'     => Incident::whereDate('reported_at', today())->count(),
            'active'    => Incident::whereNotIn('status', ['CLOSED', 'CANCELLED', 'DUPLICATE', 'FALSE_REPORT'])->count(),
            'critical'  => Incident::where('severity', 4)->whereNotIn('status', ['CLOSED', 'RESOLVED'])->count(),
            'resolved'  => Incident::whereDate('resolved_at', today())->count(),
        ];

        // Payload bersih untuk Leaflet (hindari json_encode gagal dari toArray() penuh)
        $mapIncidents  = $activeIncidents->map(fn ($i) => $this->mapIncident($i))->values();
        $mapFacilities = $facilities->map(fn ($f) => $this->mapFacility($f))->values();
        $mapUnits      = $units->map(fn ($u) => $this->mapUnit($u))->values();

        return view('command_center.index', compact(
            'activeIncidents', 'facilities', 'units', 'todayStats',
            'mapIncidents', 'mapFacilities', 'mapUnits'
        ));
    }

    /**
     * Halaman Detail Insiden (Timeline, Rekomendasi Unit, Dispatcher Action)
     */
    public function show(string $ulid): View
    {
        $incident = Incident::with([
            'media',
            'aiAnalysis',
            'assignments.unit',
            'assignments.agency',
            'statusLogs.actor',
            'dispatchRecommendations.unit',
            'patientHandovers.facility',
        ])
        ->whereUlid($ulid)
        ->firstOrFail();

        $availableUnits = Unit::with('agency')
            ->where('status', 'AVAILABLE')
            ->get();

        $agencies = Agency::where('is_active', true)->get();

        return view('command_center.show', compact('incident', 'availableUnits', 'agencies'));
    }

    /**
     * API JSON Data Insiden Aktif untuk Pembaruan Peta & Polling/Echo
     */
    public function getActiveData(): JsonResponse
    {
        $incidents = Incident::whereNotIn('status', ['CLOSED', 'CANCELLED', 'DUPLICATE', 'FALSE_REPORT'])
            ->with(['assignments.unit', 'media', 'aiAnalysis', 'dispatchRecommendations.unit'])
            ->orderBy('reported_at', 'desc')
            ->get();

        $units = Unit::with('agency')->get();

        $todayStats = [
            'total'     => Incident::whereDate('reported_at', today())->count(),
            'active'    => Incident::whereNotIn('status', ['CLOSED', 'CANCELLED', 'DUPLICATE', 'FALSE_REPORT'])->count(),
            'critical'  => Incident::where('severity', 4)->whereNotIn('status', ['CLOSED', 'RESOLVED'])->count(),
            'resolved'  => Incident::whereDate('resolved_at', today())->count(),
        ];

        return response()->json([
            'success'    => true,
            'incidents'  => $incidents->map(fn ($i) => $this->mapIncident($i))->values(),
            'units'      => $units->map(fn ($u) => $this->mapUnit($u))->values(),
            'todayStats' => $todayStats,
        ]);
    }

    /**
     * Aksi Verifikasi Insiden
     */
     public function verify(Request $request, string $ulid)
    {
        $incident = Incident::whereUlid($ulid)->firstOrFail();
        
        $incident->update([
            'status'      => 'VERIFIED',
            'verified_at' => now(),
            'severity'    => $request->input('severity', $incident->severity),
            'category'    => $request->input('category', $incident->category),
            'severity_source' => 'OPERATOR',
        ]);

        $incident->statusLogs()->create([
            'from_status' => 'NEW',
            'to_status'   => 'VERIFIED',
            'actor_id'    => auth()->id(),
            'actor_type'  => 'USER',
            'note'        => 'Insiden diverifikasi oleh operator.',
            'occurred_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Insiden berhasil diverifikasi.');
    }

    /**
     * Aksi Penugasan Armada (Dispatch Unit)
     */
    public function dispatchUnit(Request $request, string $ulid)
    {
        $request->validate([
            'unit_id' => 'required|exists:units,id',
        ]);

        $incident = Incident::whereUlid($ulid)->firstOrFail();
        $unit = Unit::findOrFail($request->input('unit_id'));

        // Buat penugasan
        $assignment = $incident->assignments()->create([
            'agency_id'     => $unit->agency_id,
            'unit_id'       => $unit->id,
            'assigned_by'   => auth()->id(),
            'status'        => 'DISPATCHED',
            'dispatched_at' => now(),
        ]);

        // Update status unit menjadi BUSY
        $unit->update(['status' => 'BUSY']);

        // Tandai rekomendasi yang dipilih
        $incident->dispatchRecommendations()->where('unit_id', $unit->id)->update(['chosen' => true]);

        // Update status insiden
        $incident->update([
            'status'        => 'DISPATCHED',
            'dispatched_at' => now(),
        ]);

        // Catat Audit Status Log
        $incident->statusLogs()->create([
            'assignment_id' => $assignment->id,
            'from_status'   => $incident->status,
            'to_status'     => 'DISPATCHED',
            'actor_id'      => auth()->id(),
            'actor_type'    => 'USER',
            'note'          => "Unit {$unit->code} ({$unit->type}) ditugaskan ke lokasi.",
            'occurred_at'   => now(),
        ]);

        return redirect()->back()->with('success', "Unit {$unit->code} berhasil di-dispatch ke lokasi!");
    }

    /**
     * Helper payload peta (field minimal + bersih UTF-8)
     */
    private function mapIncident(Incident $i): array
    {
        return $this->clean([
            'id'           => $i->id,
            'ulid'         => $i->ulid,
            'incident_no'  => $i->incident_no,
            'status'       => $i->status,
            'category'     => $i->category,
            'severity'     => $i->severity,
            'description'  => $i->description,
            'address_text' => $i->address_text,
            'lat'          => $i->lat !== null ? (float) $i->lat : null,
            'lng'          => $i->lng !== null ? (float) $i->lng : null,
            'reported_at'  => $i->reported_at?->toIso8601String(),
        ]);
    }

    private function mapFacility(Facility $f): array
    {
        return $this->mapPoint(Arr::only($f->toArray(), ['id', 'name', 'type', 'address', 'lat', 'lng']));
    }

    private function mapUnit(Unit $u): array
    {
        return $this->mapPoint(Arr::only($u->toArray(), ['id', 'code', 'type', 'status', 'lat', 'lng']));
    }

    private function mapPoint(array $a): array
    {
        foreach (['lat', 'lng'] as $k) {
            $a[$k] = (isset($a[$k]) && $a[$k] !== '') ? (float) $a[$k] : null;
        }

        return $this->clean($a);
    }

    private function clean(array $row): array
    {
        return array_map(fn ($v) => is_string($v) ? mb_scrub($v) : $v, $row);
    }
}