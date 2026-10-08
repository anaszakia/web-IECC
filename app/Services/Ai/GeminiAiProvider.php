<?php

namespace App\Services\Ai;

use App\Models\Incident\Incident;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GeminiAiProvider implements AiProviderInterface
{
    private const CATEGORIES = ['FIRE', 'MEDICAL', 'TRAFFIC', 'DISASTER', 'SECURITY', 'UNKNOWN'];

    private const UNITS = ['fire_truck', 'ambulance', 'police_patrol', 'rescue_team', 'traffic_unit'];

    private const FIRST_AID = ['burn_wound', 'bleeding', 'cpr', 'choking', 'recovery_position', 'none'];

    private const DEFAULT_UNITS = [
        'FIRE'     => ['fire_truck', 'ambulance'],
        'TRAFFIC'  => ['ambulance', 'police_patrol'],
        'MEDICAL'  => ['ambulance'],
        'DISASTER' => ['rescue_team', 'ambulance'],
        'SECURITY' => ['police_patrol'],
        'UNKNOWN'  => [],
    ];

    private const MAX_IMAGES = 3;
    private const MAX_IMAGE_SIDE = 1280;                 // px, sisi terpanjang setelah resize
    private const RAW_FALLBACK_BYTES = 4 * 1024 * 1024;  // dikirim apa adanya hanya jika <= 4 MB
    private const LOW_CONFIDENCE = 0.6;                  // di bawah ini wajib dicek operator

    protected string $apiKey;
    protected array $models;
    protected string $baseUrl;
    protected int $timeout;
    protected bool $structuredOutput;

    public function __construct()
    {
        $this->apiKey = (string) config('ai.gemini.api_key', '');

        $models = config('ai.gemini.models', []);
        if (is_string($models)) {
            $models = explode(',', $models);
        }
        $this->models = array_values(array_filter(array_map('trim', (array) $models)));
        if (empty($this->models)) {
            $this->models = ['gemini-3.5-flash'];
        }

        $this->baseUrl = rtrim((string) config('ai.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $this->timeout = (int) config('ai.timeout', 30);
        $this->structuredOutput = (bool) config('ai.gemini.structured_output', true);
    }

    public function classifyIncident(Incident $incident): array
    {
        $startTime = microtime(true);

        if ($this->apiKey === '' || ! config('ai.enabled')) {
            return $this->heuristicFallback($incident, $startTime, 'AI nonaktif atau API key kosong');
        }

        try {
            if (! $incident->relationLoaded('media')) {
                $incident->load('media');
            }

            [$images, $nonImage, $skipped] = $this->collectMedia($incident);

            $content = [['type' => 'text', 'text' => $this->buildPrompt($incident, count($images), $nonImage, $skipped)]];
            foreach ($images as $image) {
                $content[] = $image;
            }

            [$body, $modelUsed, $error] = $this->callGemini($content);

            if ($body === null) {
                return $this->heuristicFallback($incident, $startTime, $error);
            }

            $parsed = $this->decodeJson($this->extractText($body));
            if (empty($parsed)) {
                Log::warning('Gemini: respons tidak dapat di-parse', ['model' => $modelUsed, 'incident_id' => $incident->id]);
                return $this->heuristicFallback($incident, $startTime, "{$modelUsed}: respons tidak dapat dibaca");
            }

            $category   = $this->normalizeCategory((string) ($parsed['category'] ?? ''));
            $units      = $this->normalizeUnits((array) ($parsed['required_units'] ?? []), $category);
            $confidence = isset($parsed['confidence']) && is_numeric($parsed['confidence'])
                ? max(0.0, min(1.0, (float) $parsed['confidence']))
                : 0.5;
            $severity   = max(1, min(5, (int) ($parsed['severity'] ?? 3)));
            $location   = trim((string) ($parsed['detected_location'] ?? ''));
            $firstAid   = $parsed['first_aid_key'] ?? null;
            $firstAid   = in_array($firstAid, self::FIRST_AID, true) && $firstAid !== 'none' ? $firstAid : null;

            if ($location !== '' && (empty($incident->address_text) || $incident->address_text === 'Lokasi GPS')) {
                $incident->update(['address_text' => $location]);
            }

            $usage = $body['usage'] ?? [];
            $latencyMs = (int) round((microtime(true) - $startTime) * 1000);

            Log::info('Gemini classify', [
                'incident_id' => $incident->id,
                'model'       => $modelUsed,
                'category'    => $category,
                'confidence'  => $confidence,
                'images'      => count($images),
                'latency_ms'  => $latencyMs,
            ]);

            return [
                'category'          => $category,
                'incident_type'     => $parsed['incident_type'] ?? (strtolower($category) . '_incident'),
                'severity'          => $severity,
                'victim_estimate'   => max(0, (int) ($parsed['victim_estimate'] ?? 0)),
                'critical_victim'   => (bool) ($parsed['critical_victim'] ?? false),
                'detected_location' => $location !== '' ? $location : null,
                'required_units'    => $units,
                'summary'           => mb_substr((string) ($parsed['summary'] ?? ''), 0, 700),
                'media_analysis'    => $parsed['media_analysis'] ?? null,
                'incident_detail'   => $parsed['incident_detail'] ?? null,
                'risk_analysis'     => $parsed['risk_analysis'] ?? null,
                'first_aid_key'     => $firstAid,
                'confidence'        => $confidence,
                'needs_review'      => $category === 'UNKNOWN' || $confidence < self::LOW_CONFIDENCE,
                'is_fallback'       => false,
                'model_used'        => $modelUsed,
                'raw_response'      => $this->sanitizeRaw($body),
                'latency_ms'        => $latencyMs,
                'token_input'       => $usage['total_input_tokens'] ?? null,
                // token berpikir ikut ditagihkan sebagai output
                'token_output'      => isset($usage['total_output_tokens']) || isset($usage['total_thought_tokens'])
                    ? (int) ($usage['total_output_tokens'] ?? 0) + (int) ($usage['total_thought_tokens'] ?? 0)
                    : null,
                'error_message'     => null,
            ];
        } catch (\Throwable $e) {
            Log::warning('Gemini classify gagal: ' . $e->getMessage(), ['incident_id' => $incident->id]);
            return $this->heuristicFallback($incident, $startTime, mb_substr($e->getMessage(), 0, 240));
        }
    }

    /**
     * Kirim ke Interactions API (skema steps). Coba model satu per satu.
     *
     * @return array{0: ?array, 1: ?string, 2: ?string} [body, model_dipakai, pesan_error]
     */
    protected function callGemini(array $content): array
    {
        $url = "{$this->baseUrl}/interactions";
        $lastError = null;

        foreach ($this->models as $model) {
            $useStructured = $this->structuredOutput;

            for ($attempt = 1; $attempt <= 2; $attempt++) {
                $payload = [
                    'model' => $model,
                    'input' => [['type' => 'user_input', 'content' => $content]],
                ];

                if ($useStructured) {
                    $payload['response_format'] = [
                        'type'      => 'text',
                        'mime_type' => 'application/json',
                        'schema'    => $this->responseSchema(),
                    ];
                }

                try {
                    $response = Http::timeout($this->timeout)
                        ->acceptJson()
                        ->withHeaders(['x-goog-api-key' => $this->apiKey])
                        ->post($url, $payload);
                } catch (ConnectionException $e) {
                    // timeout / koneksi putus: jangan ulangi model yang sama, langsung model berikutnya
                    $lastError = "{$model}: koneksi/timeout";
                    Log::warning('Gemini koneksi gagal', ['model' => $model, 'error' => mb_substr($e->getMessage(), 0, 200)]);
                    break;
                }

                if ($response->successful()) {
                    return [$response->json(), $model, null];
                }

                $status = $response->status();
                $lastError = "{$model}: HTTP {$status}";
                Log::warning('Gemini error', [
                    'model'  => $model,
                    'status' => $status,
                    'body'   => mb_substr($response->body(), 0, 300),
                ]);

                // Jika response_format ditolak, ulangi tanpa structured output
                if ($status === 400 && $useStructured && preg_match('/response_format|schema|mime_type/i', $response->body())) {
                    $useStructured = false;
                    continue;
                }

                // Masalah kredensial: ganti model tidak membantu
                if (in_array($status, [401, 403], true)) {
                    return [null, null, $lastError];
                }

                // Beban/gangguan sementara: tunggu sebentar, coba lagi sekali, lalu model berikutnya
                if (in_array($status, [429, 500, 502, 503, 504], true)) {
                    if ($attempt < 2) {
                        usleep(1_000_000);
                    }
                    continue;
                }

                // 400/404 dan lainnya: model ini tidak bisa dipakai, coba model berikutnya
                break;
            }
        }

        return [null, null, $lastError ?? 'Tidak ada respons dari Gemini'];
    }

    protected function systemPrompt(): string
    {
        return <<<'PROMPT'
Anda adalah asisten AI Decision Intelligence untuk Integrated Emergency Command Center (IECC) di Indonesia.
Tugas: menganalisis laporan warga (teks dan foto terlampir) lalu mengklasifikasikan kejadiannya untuk operator.

ATURAN BAHASA
Seluruh teks pada 'summary', 'media_analysis', 'incident_detail', 'risk_analysis', dan 'detected_location' WAJIB dalam BAHASA INDONESIA yang lugas, terstruktur, dan profesional.

ATURAN ANALISIS
1. Analisis HANYA dari teks dan foto yang benar-benar terlampir. Audio dan video TIDAK dapat Anda proses; jangan menebak isinya.
2. Nilai kejadian secara independen dari foto dan teks. Kategori pilihan pelapor belum tentu benar dan boleh Anda abaikan.
3. Pada 'media_analysis' tulis apa yang benar-benar terlihat (kobaran api, kendaraan rusak, kondisi korban, asap, banjir, dll). Jangan mengarang detail yang tidak terlihat.
4. Jika foto TIDAK menunjukkan kejadian darurat (foto produk/iklan, selfie, tangkapan layar, gambar kosong, terlalu gelap atau buram, tidak relevan) dan teks juga tidak menjelaskan kejadian, tetapkan category 'UNKNOWN', severity 1, required_units [], confidence di bawah 0.4, dan jelaskan alasannya di 'media_analysis'.
5. Jika informasi ambigu, pilih confidence rendah (di bawah 0.6) daripada menebak dengan yakin.

ATURAN KATEGORI DAN UNIT
- Api / asap / rumah atau kendaraan terbakar: category 'FIRE', required_units ['fire_truck', 'ambulance'].
- Kecelakaan kendaraan / tabrakan: category 'TRAFFIC', required_units ['ambulance', 'police_patrol'].
- Darurat medis murni: category 'MEDICAL', required_units ['ambulance'].
- Bencana alam (banjir, longsor, pohon tumbang): category 'DISASTER', required_units ['rescue_team', 'ambulance'].
- Kejahatan / kriminal / tawuran: category 'SECURITY', required_units ['police_patrol'].
- Tidak ada kejadian darurat atau tidak dapat ditentukan: category 'UNKNOWN', required_units [].

SKALA SEVERITY
1 = tidak ada kejadian / sangat ringan, 2 = ringan, 3 = sedang, 4 = berat atau ada korban yang terancam nyawanya, 5 = bencana besar / korban massal.

Kembalikan HANYA satu objek JSON sesuai skema, tanpa markdown dan tanpa teks lain.
Gunakan string kosong untuk 'detected_location' jika lokasi tidak diketahui, dan 'none' untuk 'first_aid_key' jika tidak relevan.
PROMPT;
    }

    protected function buildPrompt(Incident $incident, int $imageCount, array $nonImage, int $skipped): string
    {
        $description = trim((string) $incident->description);
        $address = trim((string) $incident->address_text);

        $lines = [
            $this->systemPrompt(),
            '',
            'LAPORAN DARURAT WARGA:',
            'Kategori pilihan pelapor (belum tentu benar): ' . ($incident->category ?: 'tidak dipilih'),
            'Deskripsi kejadian: ' . ($description !== '' ? $description : '(tidak diisi)'),
            'Alamat/patokan lokasi: ' . ($address !== '' ? $address : '(tidak diisi)'),
            'Estimasi korban awal dari pelapor: ' . ((int) $incident->victim_estimate),
            '',
            'LAMPIRAN:',
            $imageCount > 0 ? "{$imageCount} foto terlampir untuk dianalisis." : 'Tidak ada foto yang dapat dianalisis.',
        ];

        if (! empty($nonImage)) {
            $lines[] = 'Ada lampiran non-gambar (' . implode(', ', array_unique($nonImage)) . ') yang TIDAK dapat Anda proses. Jangan menebak isinya.';
        }
        if ($skipped > 0) {
            $lines[] = "{$skipped} foto tidak dapat dilampirkan (terlalu besar atau gagal dibaca).";
        }

        return implode("\n", $lines);
    }

    protected function responseSchema(): array
    {
        $text = ['type' => 'string'];

        return [
            'type'       => 'object',
            'properties' => [
                'category'          => ['type' => 'string', 'enum' => self::CATEGORIES],
                'incident_type'     => $text,
                'severity'          => ['type' => 'integer'],
                'victim_estimate'   => ['type' => 'integer'],
                'critical_victim'   => ['type' => 'boolean'],
                'detected_location' => $text,
                'required_units'    => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => self::UNITS]],
                'summary'           => $text,
                'media_analysis'    => $text,
                'incident_detail'   => $text,
                'risk_analysis'     => $text,
                'first_aid_key'     => ['type' => 'string', 'enum' => self::FIRST_AID],
                'confidence'        => ['type' => 'number'],
            ],
            'required'   => [
                'category', 'incident_type', 'severity', 'victim_estimate', 'critical_victim',
                'detected_location', 'required_units', 'summary', 'media_analysis',
                'incident_detail', 'risk_analysis', 'first_aid_key', 'confidence',
            ],
        ];
    }

    /**
     * Ambil foto yang bisa dikirim. Audio/video dicatat tetapi tidak dikirim.
     *
     * @return array{0: array, 1: array, 2: int} [images, nonImageLabels, skippedCount]
     */
    protected function collectMedia(Incident $incident): array
    {
        $images = [];
        $nonImage = [];
        $skipped = 0;

        foreach ($incident->media ?? [] as $item) {
            $declared = (string) ($item->mime ?? '');

            if ($declared !== '' && ! str_starts_with($declared, 'image/')) {
                $nonImage[] = (string) ($item->type ?: $declared);
                continue;
            }

            if (count($images) >= self::MAX_IMAGES) {
                $skipped++;
                continue;
            }

            $prepared = $this->prepareImage((string) $item->disk_path, $declared);
            if ($prepared === null) {
                $skipped++;
                continue;
            }

            $images[] = ['type' => 'image', 'data' => $prepared['data'], 'mime_type' => $prepared['mime']];
        }

        return [$images, $nonImage, $skipped];
    }

    protected function prepareImage(string $diskPath, string $declaredMime): ?array
    {
        $disk = Storage::disk('public');

        if ($diskPath === '' || ! $disk->exists($diskPath)) {
            return null;
        }

        $data = $disk->get($diskPath);
        if (! is_string($data) || $data === '') {
            return null;
        }

        $mime = $this->detectMime($data) ?: $declaredMime;
        if ($mime === '' || ! str_starts_with($mime, 'image/')) {
            return null;
        }

        $resized = $this->resizeImage($data, $mime);
        if ($resized !== null) {
            return ['data' => base64_encode($resized), 'mime' => 'image/jpeg'];
        }

        if (strlen($data) <= self::RAW_FALLBACK_BYTES) {
            return ['data' => base64_encode($data), 'mime' => $mime];
        }

        Log::warning('Gemini: foto dilewati (terlalu besar dan resize tidak tersedia)', [
            'path' => $diskPath,
            'size' => strlen($data),
        ]);

        return null;
    }

    protected function detectMime(string $data): ?string
    {
        if (! function_exists('finfo_buffer')) {
            return null;
        }

        $mime = finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $data);

        return is_string($mime) ? $mime : null;
    }

    /**
     * Perkecil ke sisi terpanjang MAX_IMAGE_SIDE dan simpan sebagai JPEG. Butuh ekstensi GD.
     */
    protected function resizeImage(string $data, string $mime): ?string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagejpeg')) {
            return null;
        }

        $src = @imagecreatefromstring($data);
        if (! $src) {
            return null;
        }

        // Koreksi orientasi EXIF (foto HP sering tersimpan miring)
        if ($mime === 'image/jpeg' && function_exists('exif_read_data') && function_exists('imagerotate')) {
            $exif = @exif_read_data('data://image/jpeg;base64,' . base64_encode($data));
            $angle = [3 => 180, 6 => -90, 8 => 90][$exif['Orientation'] ?? 1] ?? 0;
            if ($angle !== 0) {
                $rotated = @imagerotate($src, $angle, 0);
                if ($rotated) {
                    $src = $rotated;
                }
            }
        }

        $w = imagesx($src);
        $h = imagesy($src);
        $scale = min(1, self::MAX_IMAGE_SIDE / max($w, $h));
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $dst = imagecreatetruecolor($nw, $nh);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // latar putih untuk PNG transparan
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        ob_start();
        imagejpeg($dst, null, 82);
        $out = ob_get_clean();

        return $out !== false && $out !== '' ? $out : null;
    }

    /**
     * Skema steps: jawaban ada di step bertipe 'model_output'. Step 'thought' diabaikan.
     */
    protected function extractText(array $body): string
    {
        $text = '';

        foreach ($body['steps'] ?? [] as $step) {
            if (($step['type'] ?? '') !== 'model_output') {
                continue;
            }
            foreach ($step['content'] ?? [] as $part) {
                if (($part['type'] ?? '') === 'text') {
                    $text .= (string) ($part['text'] ?? '');
                }
            }
        }

        return trim($text);
    }

    protected function decodeJson(string $text): array
    {
        if ($text === '') {
            return [];
        }

        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{[\s\S]*\}/', $text, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    /**
     * Buang step 'thought' (signature-nya panjang) agar raw_response tidak membengkak.
     */
    protected function sanitizeRaw(array $body): array
    {
        if (isset($body['steps']) && is_array($body['steps'])) {
            $body['steps'] = array_values(array_filter(
                $body['steps'],
                fn ($step) => ($step['type'] ?? '') !== 'thought'
            ));
        }

        return $body;
    }

    protected function normalizeCategory(string $raw): string
    {
        $c = strtoupper(trim($raw));

        return match (true) {
            str_contains($c, 'KEBAKARAN'), str_contains($c, 'FIRE')                                          => 'FIRE',
            str_contains($c, 'MEDIS'), str_contains($c, 'MEDICAL')                                           => 'MEDICAL',
            str_contains($c, 'LALU LINTAS'), str_contains($c, 'TRAFFIC'), str_contains($c, 'KECELAKAAN')     => 'TRAFFIC',
            str_contains($c, 'BENCANA'), str_contains($c, 'DISASTER')                                        => 'DISASTER',
            str_contains($c, 'KEAMANAN'), str_contains($c, 'KRIMINAL'), str_contains($c, 'SECURITY')         => 'SECURITY',
            default                                                                                          => 'UNKNOWN',
        };
    }

    protected function normalizeUnits(array $rawUnits, string $category): array
    {
        $units = [];

        foreach ($rawUnits as $raw) {
            $u = strtolower(trim((string) $raw));

            if (in_array($u, self::UNITS, true)) {
                $units[] = $u;
            } elseif (preg_match('/damkar|pemadam|fire/', $u)) {
                $units[] = 'fire_truck';
            } elseif (preg_match('/ambulan|medis/', $u)) {
                $units[] = 'ambulance';
            } elseif (preg_match('/polisi|patroli|police/', $u)) {
                $units[] = 'police_patrol';
            } elseif (preg_match('/rescue|\bsar\b|basarnas/', $u)) {
                $units[] = 'rescue_team';
            } elseif (preg_match('/lantas|traffic/', $u)) {
                $units[] = 'traffic_unit';
            }
            // nama unit yang tidak dikenal dibuang agar tidak merusak proses dispatch
        }

        $units = array_values(array_unique($units));

        if (empty($units)) {
            $units = self::DEFAULT_UNITS[$category] ?? [];
        }

        return $units;
    }

    /**
     * Fallback deterministik jika AI tidak tersedia. TIDAK pernah menebak FIRE secara default:
     * jika tidak ada petunjuk, hasilnya UNKNOWN dan wajib dicek operator.
     */
    protected function heuristicFallback(Incident $incident, float $startTime, ?string $errorMessage = null): array
    {
        $text = mb_strtolower(trim((string) ($incident->description ?? '')));
        $has = static fn (string $pattern): bool => (bool) preg_match($pattern, $text);

        $category = 'UNKNOWN';
        $severity = 3;
        $firstAid = null;
        $critical = false;
        $confidence = 0.2;

        if ($has('/\b(kebakaran|terbakar|api|asap)\b/u')) {
            $category = 'FIRE';
            $severity = $has('/\b(terjebak|besar)\b/u') ? 4 : 3;
            $firstAid = 'burn_wound';
            $confidence = 0.55;
        } elseif ($has('/\b(tabrakan|kecelakaan|laka|menabrak|ditabrak)\b/u')) {
            $category = 'TRAFFIC';
            $severity = $has('/\b(pingsan|darah)\b/u') ? 4 : 3;
            $firstAid = 'bleeding';
            $confidence = 0.55;
        } elseif ($has('/\b(jantung|stroke|pingsan|melahirkan|tidak sadar|henti napas)\b/u')) {
            $category = 'MEDICAL';
            $severity = $has('/\b(henti napas|tidak sadar)\b/u') ? 4 : 3;
            $firstAid = $has('/\bjantung\b/u') ? 'cpr' : 'recovery_position';
            $critical = $severity === 4;
            $confidence = 0.55;
        } elseif ($has('/\b(banjir|longsor|pohon tumbang|gempa)\b/u')) {
            $category = 'DISASTER';
            $severity = 2;
            $confidence = 0.55;
        } elseif ($has('/\b(maling|begal|perampokan|pencurian|tawuran)\b/u')) {
            $category = 'SECURITY';
            $confidence = 0.55;
        } else {
            $selected = strtoupper((string) $incident->category);
            if (in_array($selected, ['FIRE', 'MEDICAL', 'TRAFFIC', 'DISASTER', 'SECURITY'], true)) {
                $category = $selected;
                $confidence = 0.4;
            }
        }

        $description = trim((string) ($incident->description ?? ''));

        return [
            'category'          => $category,
            'incident_type'     => strtolower($category) . '_case',
            'severity'          => $severity,
            'victim_estimate'   => $critical ? 1 : 0,
            'critical_victim'   => $critical,
            'detected_location' => null,
            'required_units'    => self::DEFAULT_UNITS[$category] ?? [],
            'summary'           => 'Klasifikasi darurat TANPA AI (kata kunci), wajib diverifikasi operator. '
                . ($description !== '' ? mb_substr($description, 0, 150) : 'Laporan tanpa deskripsi.'),
            'media_analysis'    => null,
            'incident_detail'   => null,
            'risk_analysis'     => null,
            'first_aid_key'     => $firstAid,
            'confidence'        => $confidence,
            'needs_review'      => true,
            'is_fallback'       => true,
            'model_used'        => null,
            'raw_response'      => ['fallback' => true],
            'latency_ms'        => (int) round((microtime(true) - $startTime) * 1000),
            'token_input'       => 0,
            'token_output'      => 0,
            'error_message'     => $errorMessage ? mb_substr($errorMessage, 0, 240) : null,
        ];
    }
}