<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\IncidentCreated;
use App\Http\Controllers\Controller;
use App\Models\Incident\Incident;
use App\Models\Incident\IncidentMedia;
use App\Models\Incident\IncidentStatusLog;
use App\Services\IncidentIdGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class IncidentReportController extends Controller
{
    /**
     * POST /api/v1/incidents
     * Simpan laporan insiden dari warga dan broadcast ke dashboard secara cepat
     */
    public function store(Request $request): JsonResponse
    {
        Log::info('Incoming Incident Report Payload:', [
            'has_files'    => array_keys($request->allFiles()),
            'input_keys'   => array_keys($request->all()),
            'photos_count' => is_array($request->input('photos')) ? count($request->input('photos')) : ($request->filled('photos') ? 1 : 0),
            'has_video'    => $request->hasFile('video') || $request->filled('video') || $request->filled('video_base64') || $request->filled('video_url'),
            'has_audio'    => $request->hasFile('audio') || $request->hasFile('voice') || $request->filled('audio') || $request->filled('audio_base64') || $request->filled('voice_url'),
        ]);

        $validator = Validator::make($request->all(), [
            'category'            => 'nullable|string|max:100',
            'description'         => 'nullable|string|max:5000',
            'address_text'        => 'nullable|string|max:1000',
            'address'             => 'nullable|string|max:1000',
            'lat'                 => 'nullable|numeric',
            'lng'                 => 'nullable|numeric',
            'location_accuracy_m' => 'nullable|numeric',
            'victim_estimate'     => 'nullable|integer|min:0',
            'photos'              => 'nullable',
            'photos.*'            => 'nullable',
            'media'               => 'nullable',
            'media.*'             => 'nullable',
            'video'               => 'nullable',
            'video_url'           => 'nullable',
            'video_base64'        => 'nullable',
            'audio'               => 'nullable',
            'voice'               => 'nullable',
            'voice_url'           => 'nullable',
            'audio_base64'        => 'nullable',
            'caller_name'         => 'nullable|string|max:150',
            'caller_phone'        => 'nullable|string|max:50',
            'urgency'             => 'nullable|string|max:50',
            'source'              => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            Log::warning('Validasi Laporan Mobile Gagal:', [
                'errors'  => $validator->errors()->toArray(),
                'payload' => $request->all(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal: ' . implode(', ', $validator->errors()->all()),
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $incident = DB::transaction(function () use ($request) {
                $incidentNo = IncidentIdGenerator::generate();
                $lat = (float) ($request->input('lat') ?: -6.7784619);
                $lng = (float) ($request->input('lng') ?: 111.2278509);
                $addressText = $request->input('address_text') ?: ($request->input('address') ?: 'Terdeteksi via GPS');
                $cat = strtoupper($request->input('category', 'UNKNOWN'));
                $validCategories = ['MEDICAL', 'FIRE', 'DISASTER', 'SECURITY', 'TRAFFIC'];
                $category = in_array($cat, $validCategories) ? $cat : 'UNKNOWN';

                // 1. Buat Record Insiden
                $incident = Incident::create([
                    'incident_no'         => $incidentNo,
                    'reporter_id'         => auth('sanctum')->id() ?? null,
                    'source'              => $request->input('source', 'APP'),
                    'category'            => $category,
                    'severity'            => null,
                    'status'              => 'NEW',
                    'description'         => $request->input('description'),
                    'address_text'        => $addressText,
                    'lat'                 => $lat,
                    'lng'                 => $lng,
                    'location'            => DB::raw("ST_GeomFromText('POINT({$lng} {$lat})', 0)"),
                    'location_accuracy_m' => $request->input('location_accuracy_m'),
                    'victim_estimate'     => $request->input('victim_estimate'),
                    'ai_status'           => 'PENDING',
                    'reported_at'         => now(),
                ]);

                // 2. Simpan Foto Multipart (jika ada file uploaded)
                if ($request->hasFile('photos')) {
                    foreach ($request->file('photos') as $photo) {
                        $path = $photo->store("incidents/{$incident->id}", 'public');
                        IncidentMedia::create([
                            'incident_id' => $incident->id,
                            'uploaded_by' => auth('sanctum')->id() ?? null,
                            'type'        => 'PHOTO',
                            'disk_path'   => $path,
                            'mime'        => $photo->getClientMimeType(),
                            'size_bytes'  => $photo->getSize(),
                        ]);
                    }
                }

                // 3. Simpan Video Multipart (jika ada file uploaded)
                if ($request->hasFile('video')) {
                    $video = $request->file('video');
                    $path = $video->store("incidents/{$incident->id}", 'public');
                    IncidentMedia::create([
                        'incident_id' => $incident->id,
                        'uploaded_by' => auth('sanctum')->id() ?? null,
                        'type'        => 'VIDEO',
                        'disk_path'   => $path,
                        'mime'        => $video->getClientMimeType() ?: 'video/mp4',
                        'size_bytes'  => $video->getSize(),
                    ]);
                }

                // Simpan Video Base64 / URI / video_url (jika dikirim via JSON/string)
                $videoInput = $request->input('video') ?? $request->input('video_base64') ?? $request->input('video_url');
                if (is_string($videoInput) && !empty($videoInput)) {
                    if (str_starts_with($videoInput, 'data:video') || str_contains($videoInput, ';base64,') || strlen($videoInput) > 300) {
                        $data = $videoInput;
                        $mime = 'video/mp4';
                        if (str_contains($videoInput, ';base64,')) {
                            @list($typePart, $dataPart) = explode(';base64,', $videoInput);
                            $data = $dataPart;
                            $mime = str_replace('data:', '', $typePart);
                        }
                        $decoded = base64_decode($data);
                        if ($decoded !== false && strlen($decoded) > 0) {
                            $ext = str_contains($mime, 'quicktime') ? 'mov' : (str_contains($mime, 'webm') ? 'webm' : 'mp4');
                            $filename = "incidents/{$incident->id}/video_" . time() . ".{$ext}";
                            Storage::disk('public')->put($filename, $decoded);

                            IncidentMedia::create([
                                'incident_id' => $incident->id,
                                'uploaded_by' => auth('sanctum')->id() ?? null,
                                'type'        => 'VIDEO',
                                'disk_path'   => $filename,
                                'mime'        => $mime,
                                'size_bytes'  => strlen($decoded),
                            ]);
                        }
                    }
                }

                // Simpan Audio / Voice Note Multipart (jika ada)
                if ($request->hasFile('audio') || $request->hasFile('voice')) {
                    $audio = $request->file('audio') ?? $request->file('voice');
                    $path = $audio->store("incidents/{$incident->id}", 'public');
                    IncidentMedia::create([
                        'incident_id' => $incident->id,
                        'uploaded_by' => auth('sanctum')->id() ?? null,
                        'type'        => 'AUDIO',
                        'disk_path'   => $path,
                        'mime'        => $audio->getClientMimeType() ?: 'audio/mp4',
                        'size_bytes'  => $audio->getSize(),
                    ]);
                }

                // Simpan Audio Base64 / voice_url
                $audioInput = $request->input('audio') ?? $request->input('audio_base64') ?? $request->input('voice') ?? $request->input('voice_url');
                if (is_string($audioInput) && !empty($audioInput)) {
                    if (str_starts_with($audioInput, 'data:audio') || str_contains($audioInput, ';base64,') || strlen($audioInput) > 500) {
                        $data = $audioInput;
                        $mime = 'audio/mp4';
                        if (str_contains($audioInput, ';base64,')) {
                            @list($typePart, $dataPart) = explode(';base64,', $audioInput);
                            $data = $dataPart;
                            $mime = str_replace('data:', '', $typePart);
                        }
                        $decoded = base64_decode($data);
                        if ($decoded !== false && strlen($decoded) > 0) {
                            $ext = str_contains($mime, 'wav') ? 'wav' : (str_contains($mime, 'mp3') ? 'mp3' : 'm4a');
                            $filename = "incidents/{$incident->id}/voice_" . time() . ".{$ext}";
                            Storage::disk('public')->put($filename, $decoded);

                            IncidentMedia::create([
                                'incident_id' => $incident->id,
                                'uploaded_by' => auth('sanctum')->id() ?? null,
                                'type'        => 'AUDIO',
                                'disk_path'   => $filename,
                                'mime'        => $mime,
                                'size_bytes'  => strlen($decoded),
                            ]);
                        }
                    }
                }

                // Simpan Foto Base64 (jika dikirim via JSON array 'photos' atau 'media')
                $photoInputs = $request->input('photos') ?? $request->input('media');
                if (is_array($photoInputs)) {
                    foreach ($photoInputs as $idx => $photoData) {
                        if (is_string($photoData)) {
                            $data = $photoData;
                            $mime = 'image/jpeg';
                            $ext = 'jpg';
                            if (str_contains($photoData, ';base64,')) {
                                @list($typePart, $dataPart) = explode(';base64,', $photoData);
                                $data = $dataPart;
                                $mime = str_replace('data:', '', $typePart);
                                $ext = str_contains($mime, 'png') ? 'png' : 'jpg';
                            }
                            $decoded = base64_decode($data);
                            if ($decoded !== false && strlen($decoded) > 0) {
                                $filename = "incidents/{$incident->id}/photo_{$idx}_" . time() . ".{$ext}";
                                Storage::disk('public')->put($filename, $decoded);

                                IncidentMedia::create([
                                    'incident_id' => $incident->id,
                                    'uploaded_by' => auth('sanctum')->id() ?? null,
                                    'type'        => str_starts_with($mime, 'video') ? 'VIDEO' : 'PHOTO',
                                    'disk_path'   => $filename,
                                    'mime'        => $mime,
                                    'size_bytes'  => strlen($decoded),
                                ]);
                            }
                        }
                    }
                }

                // 3. Catat Status Log Awal
                IncidentStatusLog::create([
                    'incident_id' => $incident->id,
                    'from_status' => null,
                    'to_status'   => 'NEW',
                    'actor_id'    => auth('sanctum')->id() ?? null,
                    'actor_type'  => 'USER',
                    'lat'         => $lat,
                    'lng'         => $lng,
                    'note'        => 'Laporan darurat diterima oleh sistem.',
                    'occurred_at' => now(),
                    'synced_at'   => now(),
                ]);

                return $incident;
            });

            // Load media untuk broadcast
            $incident->load('media');

            // 4. Buat Rekomendasi Dispatch Default Instan & Broadcast langsung agar CC dan User tidak menunggu AI
            try {
                $dispatchEngine = app(\App\Services\Dispatch\DispatchEngine::class);
                $dispatchEngine->generateRecommendations($incident);
            } catch (\Throwable $e) {
                Log::warning('Default dispatch engine error: ' . $e->getMessage());
            }

            // 5. Broadcast Real-time Event ke Command Center via Reverb/WS
            try {
                event(new IncidentCreated($incident));
            } catch (\Throwable $e) {
                Log::warning('Broadcast failed: ' . $e->getMessage());
            }

            // 6. Jalankan AI Analysis Multimodal di Background Job (Async) agar tidak memblokir respon mobile app
            try {
                \App\Jobs\AnalyzeIncidentJob::dispatch($incident);
            } catch (\Throwable $e) {
                Log::warning('Eksekusi AI Job dispatch gagal: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Laporan darurat berhasil dikirim dan sedang ditangani operator.',
                'data'    => [
                    'ulid'        => $incident->ulid,
                    'incident_no' => $incident->incident_no,
                    'status'      => $incident->status,
                    'reported_at' => $incident->reported_at->toIso8601String(),
                ],
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Gagal membuat insiden: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat memproses laporan.',
            ], 500);
        }
    }

    /**
     * GET /api/v1/incidents/{ulid}
     * Cek status laporan warga berdasarkan ULID
     */
    public function show(string $ulid): JsonResponse
    {
        $incident = Incident::with(['media', 'statusLogs' => fn($q) => $q->orderBy('occurred_at', 'desc')])
            ->whereUlid($ulid)
            ->first();

        if (!$incident) {
            return response()->json([
                'success' => false,
                'message' => 'Insiden tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => [
                'ulid'         => $incident->ulid,
                'incident_no'  => $incident->incident_no,
                'status'       => $incident->status,
                'category'     => $incident->category,
                'severity'     => $incident->severity,
                'description'  => $incident->description,
                'address_text' => $incident->address_text,
                'lat'          => (float) $incident->lat,
                'lng'          => (float) $incident->lng,
                'reported_at'  => $incident->reported_at?->toIso8601String(),
                'verified_at'  => $incident->verified_at?->toIso8601String(),
                'dispatched_at'=> $incident->dispatched_at?->toIso8601String(),
                'resolved_at'  => $incident->resolved_at?->toIso8601String(),
                'media'        => $incident->media->map(fn($m) => [
                    'ulid' => $m->ulid,
                    'type' => $m->type,
                    'url'  => asset('storage/' . $m->disk_path),
                ]),
                'timeline'     => $incident->statusLogs->map(fn($l) => [
                    'status'      => $l->to_status,
                    'note'        => $l->note,
                    'occurred_at' => $l->occurred_at?->toIso8601String(),
                ]),
            ],
        ]);
    }

    /**
     * GET /api/v1/incidents
     * Ambil riwayat laporan milik user warga yang login
     */
    public function getMyIncidents(Request $request): JsonResponse
    {
        $userId = auth('sanctum')->id();
        $incidents = Incident::with(['media', 'assignments.unit', 'statusLogs' => fn($q) => $q->orderBy('occurred_at', 'desc')])
            ->when($userId, fn($q) => $q->where('reporter_id', $userId))
            ->orderBy('created_at', 'desc')
            ->take(30)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $incidents->map(function ($incident) {
                return [
                    'ulid'         => $incident->ulid,
                    'incident_no'  => $incident->incident_no,
                    'status'       => $incident->status,
                    'category'     => $incident->category,
                    'severity'     => $incident->severity,
                    'description'  => $incident->description,
                    'address_text' => $incident->address_text,
                    'lat'          => (float) $incident->lat,
                    'lng'          => (float) $incident->lng,
                    'ai_status'    => $incident->ai_status,
                    'reported_at'  => $incident->reported_at?->toIso8601String() ?: $incident->created_at?->toIso8601String(),
                    'media_count'  => $incident->media->count(),
                    'first_media'  => $incident->media->first() ? asset('storage/' . $incident->media->first()->disk_path) : null,
                    'media'        => $incident->media->map(fn($m) => [
                        'ulid' => $m->ulid,
                        'type' => $m->type,
                        'url'  => asset('storage/' . $m->disk_path),
                    ]),
                    'assigned_unit'=> $incident->assignments->first()?->unit?->code,
                    'timeline'     => $incident->statusLogs->map(fn($l) => [
                        'status'      => $l->to_status,
                        'note'        => $l->note,
                        'occurred_at' => $l->occurred_at?->toIso8601String(),
                    ]),
                ];
            }),
        ]);
    }
}
