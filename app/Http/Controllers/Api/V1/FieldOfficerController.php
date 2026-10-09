<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Incident\IncidentAssignment;
use App\Models\Incident\IncidentMedia;
use App\Models\Incident\IncidentStatusLog;
use App\Models\Incident\PatientHandover;
use App\Models\Incident\UnitLocationSnapshot;
use App\Models\Master\Facility;
use App\Models\Master\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FieldOfficerController extends Controller
{
    /**
     * GET /api/v1/field/facilities
     * Ambil daftar Rumah Sakit & Puskesmas rujukan IGD
     */
    public function getFacilities(Request $request): JsonResponse
    {
        $facilities = Facility::whereIn('type', ['HOSPITAL', 'PUSKESMAS'])
            ->where('is_active', true)
            ->orderByRaw("FIELD(type, 'HOSPITAL', 'PUSKESMAS')")
            ->orderBy('name')
            ->get(['id', 'ulid', 'name', 'type', 'address', 'phone', 'er_beds_total', 'er_beds_available', 'er_status', 'services', 'lat', 'lng']);

        return response()->json([
            'success' => true,
            'data'    => $facilities,
        ]);
    }

    /**
     * GET /api/v1/field/tasks
     * Ambil daftar penugasan aktif untuk armada/petugas
     */
    public function getMyTasks(Request $request): JsonResponse
    {
        $unitId = $request->query('unit_id');

        $query = IncidentAssignment::with([
            'incident.media',
            'incident.aiAnalysis',
            'unit',
            'patientHandovers.facility',
        ])
        ->whereNotIn('status', ['RESOLVED', 'CANCELLED', 'REJECTED'])
        ->orderBy('created_at', 'desc');

        if ($unitId) {
            $query->where('unit_id', $unitId);
        }

        $tasks = $query->get();

        return response()->json([
            'success' => true,
            'data'    => $tasks->map(fn($t) => [
                'assignment_ulid' => $t->ulid,
                'status'          => $t->status,
                'dispatched_at'   => $t->dispatched_at?->toIso8601String(),
                'accepted_at'     => $t->accepted_at?->toIso8601String(),
                'arrived_at'      => $t->arrived_at?->toIso8601String(),
                'incident'        => [
                    'ulid'         => $t->incident->ulid,
                    'incident_no'  => $t->incident->incident_no,
                    'category'     => $t->incident->category,
                    'severity'     => $t->incident->severity,
                    'description'  => $t->incident->description,
                    'address_text' => $t->incident->address_text,
                    'lat'          => (float) $t->incident->lat,
                    'lng'          => (float) $t->incident->lng,
                    'victim_est'   => $t->incident->victim_estimate,
                    'ai_summary'   => $t->incident->aiAnalysis?->summary,
                    'first_aid'    => $t->incident->aiAnalysis?->first_aid_key,
                ],
                'unit'            => [
                    'ulid'   => $t->unit?->ulid,
                    'code'   => $t->unit?->code,
                    'type'   => $t->unit?->type,
                    'status' => $t->unit?->status,
                    'lat'    => (float) $t->unit?->lat,
                    'lng'    => (float) $t->unit?->lng,
                ],
            ]),
        ]);
    }

    /**
     * POST /api/v1/field/assignments/{ulid}/accept
     * Petugas menerima tugas darurat (F-04)
     */
    public function acceptTask(string $ulid): JsonResponse
    {
        $assignment = IncidentAssignment::with('incident', 'unit')->whereUlid($ulid)->first();

        if (!$assignment) {
            return response()->json(['success' => false, 'message' => 'Penugasan tidak ditemukan.'], 404);
        }

        $now = now();

        DB::transaction(function () use ($assignment, $now) {
            $assignment->update([
                'status'      => 'ACCEPTED',
                'accepted_at' => $now,
            ]);

            // Set waktu first_accepted_at di insiden jika belum ada
            if (!$assignment->incident->first_accepted_at) {
                $assignment->incident->update([
                    'status'            => 'ACCEPTED',
                    'first_accepted_at' => $now,
                ]);
            }

            // Catat log status
            IncidentStatusLog::create([
                'incident_id'   => $assignment->incident_id,
                'assignment_id' => $assignment->id,
                'from_status'   => 'DISPATCHED',
                'to_status'     => 'ACCEPTED',
                'actor_type'    => 'USER',
                'note'          => "Unit {$assignment->unit?->code} menerima penugasan.",
                'occurred_at'   => $now,
                'synced_at'     => $now,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Tugas berhasil diterima. Segera bersiap meluncur.',
            'status'  => 'ACCEPTED',
        ]);
    }

    /**
     * POST /api/v1/field/assignments/{ulid}/reject
     * Petugas menolak tugas darurat dengan alasan (F-04)
     */
    public function rejectTask(Request $request, string $ulid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $assignment = IncidentAssignment::with('incident', 'unit')->whereUlid($ulid)->first();

        if (!$assignment) {
            return response()->json(['success' => false, 'message' => 'Penugasan tidak ditemukan.'], 404);
        }

        DB::transaction(function () use ($assignment, $request) {
            $assignment->update([
                'status'        => 'REJECTED',
                'reject_reason' => $request->input('reason'),
            ]);

            // Kembalikan status unit ke AVAILABLE
            if ($assignment->unit) {
                $assignment->unit->update(['status' => 'AVAILABLE']);
            }

            IncidentStatusLog::create([
                'incident_id'   => $assignment->incident_id,
                'assignment_id' => $assignment->id,
                'from_status'   => 'DISPATCHED',
                'to_status'     => 'REJECTED',
                'note'          => "Penugasan ditolak oleh unit {$assignment->unit?->code}: {$request->input('reason')}",
                'occurred_at'   => now(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Penugasan ditolak. Operator akan mengalihkan ke unit lain.',
        ]);
    }

    /**
     * POST /api/v1/field/assignments/{ulid}/status
     * Pembaruan status respons lapangan (EN_ROUTE, ARRIVED, HANDLING, TRANSFERRED, RESOLVED)
     */
    public function updateStatus(Request $request, string $ulid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:EN_ROUTE,ARRIVED,HANDLING,TRANSFERRED,RESOLVED',
            'lat'    => 'nullable|numeric',
            'lng'    => 'nullable|numeric',
            'note'   => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $assignment = IncidentAssignment::with('incident', 'unit')->whereUlid($ulid)->first();

        if (!$assignment) {
            return response()->json(['success' => false, 'message' => 'Penugasan tidak ditemukan.'], 404);
        }

        $newStatus = $request->input('status');
        $oldStatus = $assignment->status;
        $now = now();

        DB::transaction(function () use ($assignment, $newStatus, $oldStatus, $request, $now) {
            $updateData = ['status' => $newStatus];
            $incidentData = ['status' => $newStatus];

            if ($newStatus === 'EN_ROUTE') {
                $updateData['en_route_at'] = $now;
            } elseif ($newStatus === 'ARRIVED') {
                $updateData['arrived_at'] = $now;
                if (!$assignment->incident->first_arrived_at) {
                    $incidentData['first_arrived_at'] = $now;
                }
            } elseif ($newStatus === 'RESOLVED') {
                $updateData['resolved_at'] = $now;
                $incidentData['resolved_at'] = $now;

                // Kembalikan status unit armada ke AVAILABLE
                if ($assignment->unit) {
                    $assignment->unit->update(['status' => 'AVAILABLE']);
                }
            }

            $assignment->update($updateData);
            $assignment->incident->update($incidentData);

            $defaultNote = match($newStatus) {
                'EN_ROUTE'    => "Petugas bergerak menuju TKP (Dalam Perjalanan)",
                'ARRIVED'     => "Petugas tiba di lokasi kejadian (TKP)",
                'HANDLING'    => "Petugas sedang melakukan tindakan penanganan di TKP",
                'TRANSFERRED' => "Korban/Pasien sedang dalam rujukan/transfer ke Rumah Sakit",
                'RESOLVED'    => "Penanganan darurat di lapangan selesai",
                default       => "Status penugasan diperbarui ke {$newStatus}",
            };

            // Catat log
            IncidentStatusLog::create([
                'incident_id'   => $assignment->incident_id,
                'assignment_id' => $assignment->id,
                'from_status'   => $oldStatus,
                'to_status'     => $newStatus,
                'lat'           => $request->input('lat'),
                'lng'           => $request->input('lng'),
                'note'          => $request->input('note') ?: $defaultNote,
                'occurred_at'   => $now,
                'synced_at'     => $now,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => "Status berhasil diperbarui ke {$newStatus}.",
            'status'  => $newStatus,
        ]);
    }

    /**
     * POST /api/v1/field/units/{ulid}/location
     * Kirim GPS berkala posisi live armada lapangan (F-07)
     */
    public function updateLocation(Request $request, string $ulid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lat'         => 'required|numeric|between:-90,90',
            'lng'         => 'required|numeric|between:-180,180',
            'speed_kmh'   => 'nullable|numeric',
            'heading'     => 'nullable|integer',
            'incident_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $unit = Unit::whereUlid($ulid)->first();

        if (!$unit) {
            return response()->json(['success' => false, 'message' => 'Unit armada tidak ditemukan.'], 404);
        }

        $lat = (float) $request->input('lat');
        $lng = (float) $request->input('lng');

        // Update live status di database unit
        $unit->update([
            'lat'          => $lat,
            'lng'          => $lng,
            'location'     => DB::raw("ST_GeomFromText('POINT({$lng} {$lat})', 0)"),
            'last_seen_at' => now(),
        ]);

        // Catat periodic snapshot
        UnitLocationSnapshot::create([
            'unit_id'     => $unit->id,
            'incident_id' => $request->input('incident_id'),
            'lat'         => $lat,
            'lng'         => $lng,
            'speed_kmh'   => $request->input('speed_kmh'),
            'heading'     => $request->input('heading'),
            'recorded_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Posisi GPS berhasil disinkronkan.',
        ]);
    }

    /**
     * POST /api/v1/field/units/{ulid}/operational-status
     * Ubah status operasional unit armada (AVAILABLE, BUSY, OFFLINE, MAINTENANCE)
     */
    public function updateUnitOperationalStatus(Request $request, string $ulid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status'     => 'required|in:AVAILABLE,BUSY,OFFLINE,MAINTENANCE',
            'crew_ready' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $unit = Unit::whereUlid($ulid)->first();

        if (!$unit) {
            return response()->json(['success' => false, 'message' => 'Unit armada tidak ditemukan.'], 404);
        }

        $status = $request->input('status');
        $updateData = ['status' => $status];

        if ($request->has('crew_ready')) {
            $updateData['crew_ready'] = $request->boolean('crew_ready');
        } elseif ($status === 'AVAILABLE') {
            $updateData['crew_ready'] = true;
        } elseif ($status === 'OFFLINE') {
            $updateData['crew_ready'] = false;
        }

        $unit->update($updateData);

        return response()->json([
            'success' => true,
            'message' => "Status unit berhasil diubah menjadi: {$status}",
            'data'    => [
                'ulid'       => $unit->ulid,
                'code'       => $unit->code,
                'status'     => $unit->status,
                'crew_ready' => (bool) $unit->crew_ready,
            ],
        ]);
    }

    /**
     * POST /api/v1/field/assignments/{ulid}/patient-handover
     * Form Pre-Arrival Notification / Data Pasien Singkat ke Rumah Sakit (F-08 & G5)
     */
    public function submitPatientHandover(Request $request, string $ulid): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'facility_id'        => 'required|exists:facilities,id',
            'gender'             => 'required|in:M,F,UNKNOWN',
            'age_estimate'       => 'nullable|integer|between:0,130',
            'condition_text'     => 'required|string|max:1000',
            'consciousness'      => 'required|in:ALERT,VERBAL,PAIN,UNRESPONSIVE',
            'requested_services' => 'nullable|array',
            'eta_seconds'        => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $assignment = IncidentAssignment::whereUlid($ulid)->first();

        if (!$assignment) {
            return response()->json(['success' => false, 'message' => 'Penugasan tidak ditemukan.'], 404);
        }

        $facilityId = $request->input('facility_id');
        if (!$facilityId || !\App\Models\Master\Facility::where('id', $facilityId)->exists()) {
            $fallbackHosp = \App\Models\Master\Facility::where('type', 'HOSPITAL')->first();
            $facilityId = $fallbackHosp ? $fallbackHosp->id : 1;
        }

        $handover = PatientHandover::create([
            'incident_id'        => $assignment->incident_id,
            'assignment_id'      => $assignment->id,
            'facility_id'        => $facilityId,
            'gender'             => $request->input('gender'),
            'age_estimate'       => $request->input('age_estimate'),
            'condition_text'     => $request->input('condition_text'), // Otomatis terenkripsi
            'consciousness'      => $request->input('consciousness'),
            'requested_services' => $request->input('requested_services', ['emergency_room']),
            'eta_seconds'        => $request->input('eta_seconds'),
            'notified_at'        => now(),
        ]);

        // Broadcast realtime event ke Reverb WebSocket (Hospital Portal & Command Center)
        try {
            event(new \App\Events\PatientHandoverCreated($handover));
        } catch (\Throwable $e) {
            \Log::warning('Handover broadcast failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Pre-Arrival Notification berhasil dikirim ke IGD Rumah Sakit.',
            'data'    => [
                'handover_ulid' => $handover->ulid,
                'notified_at'   => $handover->notified_at->toIso8601String(),
            ],
        ], 201);
    }
}
