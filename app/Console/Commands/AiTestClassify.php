<?php

namespace App\Console\Commands;

use App\Models\Incident\Incident;
use App\Services\Ai\GeminiAiProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Tes klasifikasi AI memakai foto lokal TANPA membuat insiden di database.
 *
 * Contoh:
 *   php artisan ai:test-classify /Users/macanas/Downloads/cth.webp
 *   php artisan ai:test-classify /Users/macanas/Downloads/foto-uji --desc="ada asap tebal"
 *   php artisan ai:test-classify foto.jpg --json
 */
class AiTestClassify extends Command
{
    protected $signature = 'ai:test-classify
        {path : Path file foto, atau folder berisi foto}
        {--desc= : Deskripsi laporan (kosongkan untuk tes foto saja)}
        {--category= : Kategori pilihan pelapor (opsional)}
        {--json : Tampilkan hasil lengkap sebagai JSON}';

    protected $description = 'Uji klasifikasi AI dengan foto lokal tanpa menyimpan insiden';

    public function handle(GeminiAiProvider $provider): int
    {
        $path = (string) $this->argument('path');

        $files = is_dir($path)
            ? glob(rtrim($path, '/') . '/*.{jpg,jpeg,png,webp,gif,JPG,JPEG,PNG,WEBP}', GLOB_BRACE)
            : (is_file($path) ? [$path] : []);

        if (empty($files)) {
            $this->error("Tidak ada foto ditemukan di: {$path}");

            return self::FAILURE;
        }

        $rows = [];

        foreach ($files as $file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $diskPath = 'ai-test/' . Str::random(10) . '.' . $ext;

            // Provider membaca foto lewat disk 'public', jadi salin sementara ke sana
            Storage::disk('public')->put($diskPath, file_get_contents($file));

            try {
                // Insiden SEMENTARA (tidak disimpan ke database)
                $incident = new Incident();
                $incident->description = (string) $this->option('desc');
                $incident->category = $this->option('category') ?: null;
                $incident->setRelation('media', collect([
                    (object) [
                        'mime'      => File::mimeType($file),
                        'type'      => 'PHOTO',
                        'disk_path' => $diskPath,
                    ],
                ]));

                $result = $provider->classifyIncident($incident);
            } finally {
                Storage::disk('public')->delete($diskPath);
            }

            if ($this->option('json')) {
                $this->line(basename($file));
                $this->line(json_encode(
                    collect($result)->except('raw_response')->toArray(),
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
                ));
                continue;
            }

            $rows[] = [
                Str::limit(basename($file), 22),
                $result['category'],
                $result['severity'],
                number_format($result['confidence'], 2),
                $result['needs_review'] ? 'YA' : '-',
                $result['is_fallback'] ? 'FALLBACK' : ($result['model_used'] ?? '-'),
                $result['latency_ms'] . ' ms',
                Str::limit((string) ($result['error_message'] ?: $result['summary']), 60),
            ];
        }

        if (! $this->option('json')) {
            $this->table(
                ['Foto', 'Kategori', 'Sev', 'Conf', 'Review', 'Model', 'Waktu', 'Ringkasan / error'],
                $rows
            );
        }

        return self::SUCCESS;
    }
}