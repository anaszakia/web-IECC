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
        $agencyId = $user?->agency_id;

        // Ambil RS untuk informasi header (kapasitas bed, status IGD)
        $hospital = null;
        if ($agencyId) {
            $hospital = Facility::where('type', 'HOSPITAL')->where('agency_id', $agencyId)->first();
        }
        if (!$hospital) {
            $hospital = Facility::where('type', 'HOSPITAL')->first();
        }

        $hospitalId = $hospital?->id;

        // Ambil seluruh pasien incoming yang belum diterima (received_at null)
        $incomingPatients = PatientHandover::with(['incident', 'assignment.unit', 'assignment.agency', 'facility'])
            ->whereNull('received_at')
            ->orderBy('notified_at', 'desc')
            ->get();

        $receivedHistory = PatientHandover::with(['incident', 'assignment.unit', 'assignment.agency', 'receiver', 'facility'])
            ->whereNotNull('received_at')
            ->orderBy('received_at', 'desc')
            ->limit(15)
            ->get();

        return view('hospital.index', compact('hospital', 'incomingPatients', 'receivedHistory'));
    }

    /**
     * Endpoint API JSON untuk realtime polling status & pasien masuk IGD
     */
    public function getIncomingData(Request $request)
    {
        $user = auth()->user();
        $agencyId = $user?->agency_id;

        $hospital = null;
        if ($agencyId) {
            $hospital = Facility::where('type', 'HOSPITAL')->where('agency_id', $agencyId)->first();
        }
        if (!$hospital) {
            $hospital = Facility::where('type', 'HOSPITAL')->first();
        }

        $hospitalId = $hospital?->id;

        // Ambil seluruh pasien incoming yang belum diterima (received_at null)
        $incomingPatients = PatientHandover::with(['incident', 'assignment.unit', 'assignment.agency', 'facility'])
            ->whereNull('received_at')
            ->orderBy('notified_at', 'desc')
            ->get();

        $receivedHistory = PatientHandover::with(['incident', 'assignment.unit', 'assignment.agency', 'receiver', 'facility'])
            ->whereNotNull('received_at')
            ->orderBy('received_at', 'desc')
            ->limit(15)
            ->get();

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
