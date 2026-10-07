<?php

// ==========================================
// MINIO HELPERS
// ==========================================

/**
 * Get app folder prefix dari .env
 * Contoh: dev/my_app
 */
function minio_base_folder(): string
{
    $bucket  = env('MINIO_BUCKET', 'dev');
    $appName = str(\Illuminate\Support\Str::slug(env('APP_NAME', 'app'), '_'));
    return $appName;
}

/**
 * Build full folder path
 * Contoh: minio_folder('avatars') → my_app/avatars
 */
function minio_folder(string $folder): string
{
    return minio_base_folder() . '/' . $folder;
}

/**
 * Upload file ke MinIO
 * Otomatis masuk ke folder: {app_name}/{folder}
 */
function minio_upload(\Illuminate\Http\UploadedFile $file, string $folder = 'uploads'): string
{
    return app(\App\Services\MinioService::class)->upload($file, minio_folder($folder));
}

/**
 * Upload file dengan nama custom
 */
function minio_upload_as(\Illuminate\Http\UploadedFile $file, string $folder, string $filename): string
{
    return app(\App\Services\MinioService::class)->uploadAs($file, minio_folder($folder), $filename);
}

/**
 * Upload base64 image ke MinIO
 */
function minio_upload_base64(string $base64, string $folder = 'uploads', string $ext = 'jpg'): string
{
    return app(\App\Services\MinioService::class)->uploadBase64($base64, minio_folder($folder), $ext);
}

/**
 * Get URL publik file dari MinIO
 */
function minio_url(?string $path): string
{
    if (!$path) return '';
    return app(\App\Services\MinioService::class)->url($path);
}

/**
 * Get temporary URL file dari MinIO (untuk file private)
 */
function minio_temp_url(?string $path, int $minutes = 30): string
{
    if (!$path) return '';
    return app(\App\Services\MinioService::class)->temporaryUrl($path, $minutes);
}

/**
 * Hapus file dari MinIO
 */
function minio_delete(?string $path): bool
{
    if (!$path) return false;
    return app(\App\Services\MinioService::class)->delete($path);
}

/**
 * Replace file lama dengan baru
 */
function minio_replace(?string $oldPath, \Illuminate\Http\UploadedFile $newFile, string $folder): string
{
    return app(\App\Services\MinioService::class)->replace($oldPath, $newFile, minio_folder($folder));
}

/**
 * Cek file ada di MinIO
 */
function minio_exists(?string $path): bool
{
    if (!$path) return false;
    return app(\App\Services\MinioService::class)->exists($path);
}

/**
 * Get info file dari MinIO
 */
function minio_info(string $path): array
{
    return app(\App\Services\MinioService::class)->info($path);
}

/**
 * Get URL avatar user, fallback ke inisial jika tidak ada
 */
function minio_avatar(?string $path, string $name = 'User'): string
{
    if ($path) {
        try {
            return minio_url($path);
        } catch (\Throwable $e) {
            // fallback below
        }
    }
    // Fallback ke UI Avatars
    return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=0d6efd&color=fff&size=128';
}

/**
 * Universal Storage Helper Aliases
 */
function storage_avatar(?string $path, string $name = 'User'): string
{
    return minio_avatar($path, $name);
}

function storage_url(?string $path): string
{
    return minio_url($path);
}

function storage_upload(\Illuminate\Http\UploadedFile $file, string $folder = 'uploads'): string
{
    return minio_upload($file, $folder);
}

function storage_delete(?string $path): bool
{
    return minio_delete($path);
}

// ==========================================
// STRING HELPERS
// ==========================================

/**
 * Truncate string
 */
function str_limit(string $value, int $limit = 100, string $end = '...'): string
{
    return \Illuminate\Support\Str::limit($value, $limit, $end);
}

/**
 * Format bytes ke human readable
 */
function human_filesize(int $bytes, int $decimals = 2): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i     = floor(log($bytes, 1024));
    return round($bytes / pow(1024, $i), $decimals) . ' ' . $units[$i];
}

// ==========================================
// DATE HELPERS
// ==========================================

/**
 * Format tanggal ke Indonesia
 * Contoh: 02 Mei 2026
 */
function tgl_indo(?string $date, string $format = 'd F Y'): string
{
    if (!$date) return '-';

    $bulan = [
        'January'   => 'Januari',
        'February'  => 'Februari',
        'March'     => 'Maret',
        'April'     => 'April',
        'May'       => 'Mei',
        'June'      => 'Juni',
        'July'      => 'Juli',
        'August'    => 'Agustus',
        'September' => 'September',
        'October'   => 'Oktober',
        'November'  => 'November',
        'December'  => 'Desember',
    ];

    $formatted = \Carbon\Carbon::parse($date)->format($format);
    return str_replace(array_keys($bulan), array_values($bulan), $formatted);
}

/**
 * Format tanggal + jam
 * Contoh: 02 Mei 2026, 14:30
 */
function tgl_jam(?string $date): string
{
    if (!$date) return '-';
    return tgl_indo($date, 'd F Y') . ', ' . \Carbon\Carbon::parse($date)->format('H:i');
}

/**
 * Berapa lama yang lalu
 * Contoh: 2 jam yang lalu
 */
function time_ago(?string $date): string
{
    if (!$date) return '-';
    return \Carbon\Carbon::parse($date)->locale('id')->diffForHumans();
}

// ==========================================
// NUMBER HELPERS
// ==========================================

/**
 * Format angka ke Rupiah
 * Contoh: 150000 → Rp 150.000
 */
function rupiah(int|float $amount): string
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

/**
 * Format angka dengan titik
 * Contoh: 1500000 → 1.500.000
 */
function number_dot(int|float $number): string
{
    return number_format($number, 0, ',', '.');
}

// ==========================================
// UI HELPERS
// ==========================================

/**
 * Get inisial dari nama
 * Contoh: "John Doe" → "JD"
 */
function get_initials(string $name, int $length = 2): string
{
    $words    = explode(' ', $name);
    $initials = '';
    foreach (array_slice($words, 0, $length) as $word) {
        $initials .= strtoupper(substr($word, 0, 1));
    }
    return $initials;
}

/**
 * Generate warna random dari nama (konsisten)
 */
function name_color(string $name): string
{
    $colors = [
        '#0d6efd', '#6610f2', '#6f42c1', '#d63384',
        '#dc3545', '#fd7e14', '#198754', '#0dcaf0',
    ];
    return $colors[ord($name[0]) % count($colors)];
}

/**
 * Active route helper untuk nav
 */
function is_active(string|array $routes, string $class = 'active'): string
{
    if (is_array($routes)) {
        foreach ($routes as $route) {
            if (request()->routeIs($route)) return $class;
        }
        return '';
    }
    return request()->routeIs($routes) ? $class : '';
}

/**
 * Active URL helper
 */
function is_active_url(string $url, string $class = 'active'): string
{
    return request()->is(ltrim($url, '/') . '*') ? $class : '';
}