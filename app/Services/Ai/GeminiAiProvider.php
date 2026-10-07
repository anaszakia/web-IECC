<?php

namespace App\Services\Ai;

use App\Models\Incident\Incident;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiAiProvider implements AiProviderInterface
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;
    protected int $timeout;

    public function __construct()
    {
        $this->apiKey = config('ai.gemini.api_key', env('GEMINI_API_KEY', ''));
        $this->model = config('ai.gemini.model', env('GEMINI_MODEL', 'gemini-2.5-flash'));
        $this->baseUrl = config('ai.gemini.base_url', env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'));
        $this->timeout = (int) config('ai.timeout', env('AI_TIMEOUT', 15));
    }

    public function classifyIncident(Incident $incident): array
    {
        $startTime = microtime(true);

        // Jika API Key kosong atau AI dinonaktifkan, gunakan fallback cerdas berbasis kata kunci
        if (empty($this->apiKey) || !config('ai.enabled')) {
            return $this->heuristicFallback($incident, microtime(true) - $startTime);
        }

        try {
            $systemInstruction = "Anda adalah asisten AI Decision Intelligence untuk Integrated Emergency Command Center (IECC) di Indonesia.
Tugas: Lakukan analisis kegawatdaruratan menyeluruh dari laporan warga, teks deskripsi, serta media terlampir (foto visual, rekaman suara, atau video).
Aturan Bahasa:
- Seluruh teks pada 'summary', 'media_analysis', 'incident_detail', 'risk_analysis', dan 'detected_location' WAJIB menggunakan BAHASA INDONESIA yang lugas, terstruktur, profesional, dan mudah dipahami operator.

Instruksi Multimodal Khusus:
1. PENTING: Analisis visual foto/video (misal kondisi kobaran api, jenis kendaraan yang ringsek, luka korban, senjata, kepulan asap, kondisi cuaca).
2. PENTING: Dengarkan rekaman suara/video untuk mengekstrak suara latar (sirine, teriakan, ledakan, kobaran api) dan informasi verbal pelapor (alamat jalan, nomor rumah, patokan bangunan, jumlah korban).
3. Jika terdapat tanda kebakaran / api / asap pada video/audio/teks, WAJIB tetapkan category: 'FIRE' dan required_units: ['fire_truck', 'ambulance'].
4. Buat rincian analisis yang sangat mendalam dan informatif:
   - summary: Ringkasan eksekutif komprehensif situasi darurat (2-3 kalimat).
   - media_analysis: Rincian temuan dari analisis visual foto/video atau audio (misal: 'Terlihat 1 unit sepeda motor matic ringsek di tepi jalan dengan genangan oli, tidak terlihat kobaran api, terdengar suara minta tolong warga').
   - incident_detail: Uraian kronologi/kondisi kejadian secara spesifik berdasarkan bukti yang ada.
   - risk_analysis: Potensi bahaya lanjutan jika tidak segera ditangani (misal: kemacetan total, risiko kebakaran merambat, syok hipovolemik).
   - detected_location: Alamat atau patokan spesifik yang berhasil diidentifikasi dari media/teks.
   - required_units: Armada yang wajib dikerahkan (['ambulance', 'fire_truck', 'police_patrol', 'rescue_team', 'traffic_unit']).
   - first_aid_key: Panduan pertolongan pertama ('cpr', 'burn_wound', 'choking', 'bleeding', 'recovery_position', atau null).";

            $userPrompt = "LAPORAN DARURAT WARGA:
Kategori yang Dipilih: {$incident->category}
Deskripsi Kejadian: {$incident->description}
Alamat/Patokan Lokasi GPS: {$incident->address_text}
Estimasi Korban Awal: {$incident->victim_estimate}";

            // Siapkan parts untuk multimodal Gemini (Teks + Media Inline)
            $contentParts = [
                ['text' => $userPrompt],
            ];

            // Muat relasi media jika belum
            if (!$incident->relationLoaded('media')) {
                $incident->load('media');
            }

            // Lampirkan media foto, audio, dan video (inlineData base64 untuk Gemini Flash - prioritaskan max 2 file terpenting agar cepat)
            if ($incident->media && $incident->media->count() > 0) {
                foreach ($incident->media->take(2) as $mediaItem) {
                    $diskPath = $mediaItem->disk_path;
                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($diskPath)) {
                        $fileData = \Illuminate\Support\Facades\Storage::disk('public')->get($diskPath);
                        $fileSize = strlen($fileData);

                        // Batasi media maksimal 3MB per file untuk kecepatan transfer & inferensi real-time
                        if ($fileSize > 0 && $fileSize < 3 * 1024 * 1024) {
                            $mimeType = $mediaItem->mime ?: 'image/jpeg';
                            if (str_starts_with($mimeType, 'image/') || str_starts_with($mimeType, 'audio/') || str_starts_with($mimeType, 'video/')) {
                                $contentParts[] = [
                                    'inlineData' => [
                                        'mimeType' => $mimeType,
                                        'data'     => base64_encode($fileData),
                                    ],
                                ];
                            }
                        }
                    }
                }
            }

            // Langsung gunakan standard generateContent Gemini Flash yang sangat cepat (< 1.5 - 2 detik)
            $modelName = $this->model ?: 'gemini-2.5-flash';
            $url = "{$this->baseUrl}/models/{$modelName}:generateContent?key={$this->apiKey}";

            $response = Http::timeout($this->timeout)->post($url, [
                'systemInstruction' => [
                    'parts' => [['text' => $systemInstruction]],
                ],
                'contents' => [
                    [
                        'role'  => 'user',
                        'parts' => $contentParts,
                    ],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'temperature'      => 0.1,
                    'maxOutputTokens'  => 800,
                ],
            ]);

            // Jika model utama gagal atau timeout, coba fallback cepat ke gemini-1.5-flash
            if (!$response->successful() && $modelName !== 'gemini-1.5-flash') {
                Log::warning("Gemini primary model {$modelName} returned status " . $response->status() . ", trying fallback gemini-1.5-flash...");
                $fallbackUrl = "{$this->baseUrl}/models/gemini-1.5-flash:generateContent?key={$this->apiKey}";
                $response = Http::timeout(6)->post($fallbackUrl, [
                    'systemInstruction' => [
                        'parts' => [['text' => $systemInstruction]],
                    ],
                    'contents' => [
                        [
                            'role'  => 'user',
                            'parts' => $contentParts,
                        ],
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'temperature'      => 0.1,
                        'maxOutputTokens'  => 800,
                    ],
                ]);
            }

            if ($response && $response->successful()) {
                $body = $response->json();
                $rawText = $body['candidates'][0]['content']['parts'][0]['text'] ?? '{}';

                // Bersihkan markdown fence jika ada
                $cleanJson = trim($rawText);
                if (str_starts_with($cleanJson, '```json')) {
                    $cleanJson = substr($cleanJson, 7);
                }
                if (str_starts_with($cleanJson, '```')) {
                    $cleanJson = substr($cleanJson, 3);
                }
                if (str_ends_with($cleanJson, '```')) {
                    $cleanJson = substr($cleanJson, 0, -3);
                }
                $cleanJson = trim($cleanJson);

                $parsed = is_array($cleanJson) ? $cleanJson : json_decode($cleanJson, true) ?? [];

                if (!empty($parsed['detected_location']) && (empty($incident->address_text) || $incident->address_text === 'Lokasi GPS')) {
                    $incident->update(['address_text' => $parsed['detected_location']]);
                }

                $mappedCategory = strtoupper($parsed['category'] ?? '');
                if (str_contains($mappedCategory, 'KEBAKARAN') || str_contains($mappedCategory, 'FIRE')) {
                    $mappedCategory = 'FIRE';
                } elseif (str_contains($mappedCategory, 'MEDIS') || str_contains($mappedCategory, 'MEDICAL')) {
                    $mappedCategory = 'MEDICAL';
                } elseif (str_contains($mappedCategory, 'LALU LINTAS') || str_contains($mappedCategory, 'TRAFFIC') || str_contains($mappedCategory, 'KECELAKAAN')) {
                    $mappedCategory = 'TRAFFIC';
                } elseif (str_contains($mappedCategory, 'BENCANA') || str_contains($mappedCategory, 'DISASTER')) {
                    $mappedCategory = 'DISASTER';
                } elseif (str_contains($mappedCategory, 'KEAMANAN') || str_contains($mappedCategory, 'KRIMINAL') || str_contains($mappedCategory, 'SECURITY')) {
                    $mappedCategory = 'SECURITY';
                }

                // Normalisasi armada yang dibutuhkan
                $rawUnits = (array) ($parsed['required_units'] ?? []);
                $normalizedUnits = [];
                foreach ($rawUnits as $unitStr) {
                    $u = strtolower((string)$unitStr);
                    if (str_contains($u, 'damkar') || str_contains($u, 'pemadam') || str_contains($u, 'fire')) {
                        $normalizedUnits[] = 'fire_truck';
                    } elseif (str_contains($u, 'ambulan') || str_contains($u, 'medis')) {
                        $normalizedUnits[] = 'ambulance';
                    } elseif (str_contains($u, 'polisi') || str_contains($u, 'patroli')) {
                        $normalizedUnits[] = 'police_patrol';
                    } elseif (str_contains($u, 'rescue') || str_contains($u, 'sar') || str_contains($u, 'basarnas')) {
                        $normalizedUnits[] = 'rescue_team';
                    } elseif (str_contains($u, 'lantas') || str_contains($u, 'traffic')) {
                        $normalizedUnits[] = 'traffic_unit';
                    } else {
                        $normalizedUnits[] = $unitStr;
                    }
                }
                $normalizedUnits = array_values(array_unique(array_filter($normalizedUnits)));
                if (empty($normalizedUnits)) {
                    $normalizedUnits = ($mappedCategory === 'FIRE') ? ['fire_truck', 'ambulance'] : ['ambulance'];
                }

                return [
                    'category'          => in_array($mappedCategory, ['MEDICAL', 'FIRE', 'DISASTER', 'SECURITY', 'TRAFFIC']) ? $mappedCategory : ($incident->category ?: 'UNKNOWN'),
                    'incident_type'     => $parsed['incident_type'] ?? (strtolower($mappedCategory) . '_incident'),
                    'severity'          => (int) ($parsed['severity'] ?? 3),
                    'victim_estimate'   => (int) ($parsed['victim_estimate'] ?? 0),
                    'critical_victim'   => (bool) ($parsed['critical_victim'] ?? false),
                    'detected_location' => $parsed['detected_location'] ?? null,
                    'required_units'    => $normalizedUnits,
                    'summary'           => substr($parsed['summary'] ?? '', 0, 700),
                    'media_analysis'    => $parsed['media_analysis'] ?? null,
                    'incident_detail'   => $parsed['incident_detail'] ?? null,
                    'risk_analysis'     => $parsed['risk_analysis'] ?? null,
                    'first_aid_key'     => $parsed['first_aid_key'] ?? null,
                    'confidence'        => (float) ($parsed['confidence'] ?? 0.90),
                    'raw_response'      => $body,
                    'latency_ms'        => (int) round((microtime(true) - $startTime) * 1000),
                    'token_input'       => $body['usageMetadata']['promptTokenCount'] ?? null,
                    'token_output'      => $body['usageMetadata']['candidatesTokenCount'] ?? null,
                    'error_message'     => null,
                ];
            }

            Log::warning('Gemini API returned error or empty response');
            return $this->heuristicFallback($incident, microtime(true) - $startTime, 'Gemini API Error');

        } catch (\Throwable $e) {
            Log::warning('Gemini API call failed: ' . $e->getMessage());
            return $this->heuristicFallback($incident, microtime(true) - $startTime, $e->getMessage());
        }
    }

    /**
     * Fallback deterministik berbasis aturan teks jika AI API offline / gagal
     */
    protected function heuristicFallback(Incident $incident, float $elapsedSeconds, ?string $errorMessage = null): array
    {
        $desc = strtolower(($incident->description ?? '') . ' ' . ($incident->category ?? ''));
        $hasMedia = $incident->media()->count() > 0;

        $category = 'UNKNOWN';
        $severity = 3;
        $requiredUnits = ['fire_truck', 'ambulance'];
        $firstAid = null;
        $critical = false;

        if (str_contains($desc, 'kebakaran') || str_contains($desc, 'api') || str_contains($desc, 'asap') || $incident->category === 'FIRE') {
            $category = 'FIRE';
            $requiredUnits = ['fire_truck', 'ambulance'];
            $severity = str_contains($desc, 'terjebak') || str_contains($desc, 'besar') ? 4 : 3;
            $firstAid = 'burn_wound';
        } elseif (str_contains($desc, 'tabrakan') || str_contains($desc, 'kecelakaan') || str_contains($desc, 'motor') || str_contains($desc, 'mobil') || $incident->category === 'TRAFFIC') {
            $category = 'TRAFFIC';
            $requiredUnits = ['ambulance', 'police_patrol'];
            $severity = str_contains($desc, 'pingsan') || str_contains($desc, 'darah') ? 4 : 3;
            $firstAid = 'bleeding';
        } elseif (str_contains($desc, 'jantung') || str_contains($desc, 'stroke') || str_contains($desc, 'pingsan') || str_contains($desc, 'melahirkan') || $incident->category === 'MEDICAL') {
            $category = 'MEDICAL';
            $requiredUnits = ['ambulance'];
            $severity = str_contains($desc, 'henti napas') || str_contains($desc, 'tidak sadar') ? 4 : 3;
            $firstAid = str_contains($desc, 'jantung') ? 'cpr' : 'recovery_position';
            $critical = $severity === 4;
        } elseif (str_contains($desc, 'banjir') || str_contains($desc, 'longsor') || str_contains($desc, 'pohon tumbang') || $incident->category === 'DISASTER') {
            $category = 'DISASTER';
            $requiredUnits = ['rescue_team'];
            $severity = 2;
        } elseif (str_contains($desc, 'maling') || str_contains($desc, 'begal') || str_contains($desc, 'perampokan') || $incident->category === 'SECURITY') {
            $category = 'SECURITY';
            $requiredUnits = ['police_patrol'];
            $severity = 3;
        } else {
            // Default jika ada video/foto dan belum terdeteksi spesifik: kirim Damkar & Ambulans Tim Reaksi Cepat
            $category = $incident->category !== 'UNKNOWN' && !empty($incident->category) ? $incident->category : 'FIRE';
            $requiredUnits = ['fire_truck', 'ambulance'];
        }

        return [
            'category'        => $category !== 'UNKNOWN' ? $category : ($incident->category ?: 'UNKNOWN'),
            'incident_type'   => strtolower($category) . '_case',
            'severity'        => $severity,
            'victim_estimate' => $critical ? 1 : 0,
            'critical_victim' => $critical,
            'required_units'  => $requiredUnits,
            'summary'         => 'Klasifikasi aturan fallback: ' . substr($incident->description ?? 'Laporan diterima', 0, 150),
            'first_aid_key'   => $firstAid,
            'confidence'      => 0.70,
            'raw_response'    => ['fallback' => true],
            'latency_ms'      => (int) round($elapsedSeconds * 1000),
            'token_input'     => 0,
            'token_output'    => 0,
            'error_message'   => $errorMessage,
        ];
    }
}
