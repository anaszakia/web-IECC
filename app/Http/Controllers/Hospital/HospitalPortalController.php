<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use App\Models\Incident\PatientHandover;
use App\Models\Master\Facility;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HospitalPortalController extends Controller
{
    /**
     * Dashboard Portal Rumah Sakit (Daftar Pre-Arrival Pasien & Kapasitas IGD)
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $userFacilityId = $user?->facility_id;
        $agencyId = $user?->agency_id;
        $isSuperAdmin = $user?->hasRole('superadmin') || $user?->hasRole('operator');

        $allFacilities = Facility::whereIn('type', ['HOSPITAL', 'PUSKESMAS'])->where('is_active', true)->orderBy('name')->get();

        // Tentukan fasilitas yang sedang dibuka
        $selectedFacilityId = $request->query('facility_id');
        $hospital = null;

        if ($userFacilityId && !$isSuperAdmin) {
            // Jika user adalah staf RS/Puskesmas tertentu, kunci hanya ke fasilitasnya
            $hospital = Facility::find($userFacilityId);
        } elseif ($selectedFacilityId) {
            // Jika admin memilih fasilitas dari dropdown switcher
            $hospital = Facility::find($selectedFacilityId);
        } elseif ($userFacilityId) {
            $hospital = Facility::find($userFacilityId);
        } elseif ($agencyId) {
            $hospital = Facility::whereIn('type', ['HOSPITAL', 'PUSKESMAS'])->where('agency_id', $agencyId)->first();
        }

        if (!$hospital) {
            $hospital = Facility::whereIn('type', ['HOSPITAL', 'PUSKESMAS'])->first();
        }

        $hospitalId = $hospital?->id;

        // Query Incoming Patients: Filter ketat berdasarkan fasilitas rujukan yang dituju
        $incomingQuery = PatientHandover::with(['incident', 'assignment.unit', 'assignment.agency', 'facility'])
            ->whereNull('received_at');

        $historyQuery = PatientHandover::with(['incident', 'assignment.unit', 'assignment.agency', 'receiver', 'facility'])
            ->whereNotNull('received_at');

        if ($hospitalId) {
            $incomingQuery->where('facility_id', $hospitalId);
            $historyQuery->where('facility_id', $hospitalId);
        }

        $incomingPatients = $incomingQuery->orderBy('notified_at', 'desc')->get();
        $receivedHistory = $historyQuery->orderBy('received_at', 'desc')->limit(15)->get();

        return view('hospital.index', compact('hospital', 'incomingPatients', 'receivedHistory', 'allFacilities', 'isSuperAdmin'));
    }

    /**
     * Endpoint API JSON untuk realtime polling status & pasien masuk IGD
     */
    public function getIncomingData(Request $request)
    {
        $user = auth()->user();
        $userFacilityId = $user?->facility_id;
        $agencyId = $user?->agency_id;
        $isSuperAdmin = $user?->hasRole('superadmin') || $user?->hasRole('operator');

        $selectedFacilityId = $request->query('facility_id');
        $hospital = null;

        if ($userFacilityId && !$isSuperAdmin) {
            $hospital = Facility::find($userFacilityId);
        } elseif ($selectedFacilityId) {
            $hospital = Facility::find($selectedFacilityId);
        } elseif ($userFacilityId) {
            $hospital = Facility::find($userFacilityId);
        } elseif ($agencyId) {
            $hospital = Facility::whereIn('type', ['HOSPITAL', 'PUSKESMAS'])->where('agency_id', $agencyId)->first();
        }

        if (!$hospital) {
            $hospital = Facility::whereIn('type', ['HOSPITAL', 'PUSKESMAS'])->first();
        }

        $hospitalId = $hospital?->id;

        $incomingQuery = PatientHandover::with(['incident', 'assignment.unit', 'assignment.agency', 'facility'])
            ->whereNull('received_at');

        $historyQuery = PatientHandover::with(['incident', 'assignment.unit', 'assignment.agency', 'receiver', 'facility'])
            ->whereNotNull('received_at');

        if ($hospitalId) {
            $incomingQuery->where('facility_id', $hospitalId);
            $historyQuery->where('facility_id', $hospitalId);
        }

        $incomingPatients = $incomingQuery->orderBy('notified_at', 'desc')->get();
        $receivedHistory = $historyQuery->orderBy('received_at', 'desc')->limit(15)->get();

        return response()->json([
            'success'          => true,
            'hospital'         => $hospital,
            'incoming_count'   => $incomingPatients->count(),
            'incoming'         => $incomingPatients,
            'received_history' => $receivedHistory,
        ]);
    }

    /**
     * Konfirmasi Pasien Diterima di IGD (H-02)
     */
    public function markAsReceived(string $ulid)
    {
        $handover = PatientHandover::whereUlid($ulid)->firstOrFail();

        $handover->update([
            'received_at' => now(),
            'received_by' => auth()->id(),
        ]);

        // Catat di Incident Status Log
        $handover->incident->statusLogs()->create([
            'assignment_id' => $handover->assignment_id,
            'from_status'   => 'TRANSFERRED',
            'to_status'     => 'HANDOVER_COMPLETED',
            'actor_id'      => auth()->id(),
            'actor_type'    => 'USER',
            'note'          => "Pasien telah diterima oleh IGD {$handover->facility->name}.",
            'occurred_at'   => now(),
        ]);

        return redirect()->back()->with('success', 'Pasien berhasil dikonfirmasi diterima di IGD.');
    }

    /**
     * Update Kapasitas & Status Bed IGD Rumah Sakit (H-03)
     */
    public function updateCapacity(Request $request, string $ulid)
    {
        $request->validate([
            'er_beds_total'     => 'required|integer|min:0',
            'er_beds_available' => 'required|integer|min:0|lte:er_beds_total',
            'er_status'         => 'required|in:NORMAL,BUSY,FULL',
        ]);

        $facility = Facility::whereUlid($ulid)->firstOrFail();

        $facility->update([
            'er_beds_total'     => $request->input('er_beds_total'),
            'er_beds_available' => $request->input('er_beds_available'),
            'er_status'         => $request->input('er_status'),
            'er_updated_at'     => now(),
        ]);

        return redirect()->back()->with('success', 'Kapasitas IGD berhasil diperbarui.');
    }
}
