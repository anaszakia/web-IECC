<?php
namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class MinioService
{
    protected string $disk;

    public function __construct(?string $disk = null)
    {
        $this->disk = $disk ?? (config('filesystems.minio_active', true) ? 'minio' : 'public');
    }

    /**
     * Get active disk name
     */
    public function getDisk(): string
    {
        return $this->disk;
    }

    /**
     * Check whether MinIO is actively used
     */
    public function isMinio(): bool
    {
        return $this->disk === 'minio';
    }

    /**
     * Get base folder dari APP_NAME
     * Contoh: my_app
     */
    public function baseFolder(): string
    {
        return Str::slug(config('app.name', 'app'), '_');
    }

    /**
     * Build full path dengan base folder
     * Contoh: my_app/avatars
     */
    public function buildPath(string $folder): string
    {
        return $this->baseFolder() . '/' . ltrim($folder, '/');
    }

    /**
     * Upload file
     */
    public function upload(UploadedFile $file, string $folder = 'uploads'): string
    {
        $filename = time() . '_' . uniqid() . '.' . $file->extension();

        Storage::disk($this->disk)->putFileAs(
            $folder,
            $file,
            $filename,
            'public'
        );

        return $folder . '/' . $filename;
    }

    /**
     * Upload dengan nama custom
     */
    public function uploadAs(UploadedFile $file, string $folder, string $filename): string
    {
        $fullname = $filename . '.' . $file->extension();

        Storage::disk($this->disk)->putFileAs(
            $folder,
            $file,
            $fullname,
            'public'
        );

        return $folder . '/' . $fullname;
    }

    /**
     * Upload base64
     */
    public function uploadBase64(string $base64, string $folder = 'uploads', string $ext = 'jpg'): string
    {
        $filename = time() . '_' . uniqid() . '.' . $ext;
        $path     = $folder . '/' . $filename;
        $data     = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64));

        Storage::disk($this->disk)->put($path, $data, 'public');

        return $path;
    }

    /**
     * Get URL untuk browser (public accessible)
     * Support MinIO dan Local storage secara dinamis
     */
    public function url(string $path): string
    {
        if ($this->disk === 'public') {
            return asset('storage/' . ltrim($path, '/'));
        }

        $url = Storage::disk($this->disk)->url($path);
        
        // Fix localhost untuk browser access jika running di development
        if (str_contains($url, '127.0.0.1')) {
            $url = str_replace('127.0.0.1', 'localhost', $url);
        }
        
        return $url;
    }

    /**
     * Temporary URL (fallback ke url biasa jika disk tidak mendukung temporary URL)
     */
    public function temporaryUrl(string $path, int $minutes = 30): string
    {
        try {
            return Storage::disk($this->disk)->temporaryUrl($path, now()->addMinutes($minutes));
        } catch (\Throwable $e) {
            return $this->url($path);
        }
    }

    /**
     * Hapus file
     */
    public function delete(string|array $path): bool
    {
        return Storage::disk($this->disk)->delete($path);
    }

    /**
     * Hapus folder
     */
    public function deleteFolder(string $folder): bool
    {
        return Storage::disk($this->disk)->deleteDirectory($folder);
    }

    /**
     * Cek exists
     */
    public function exists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path);
    }

    /**
     * Replace file lama dengan baru
     */
    public function replace(?string $oldPath, UploadedFile $newFile, string $folder): string
    {
        if ($oldPath && $this->exists($oldPath)) {
            $this->delete($oldPath);
        }
        return $this->upload($newFile, $folder);
    }

    /**
     * Get info file
     */
    public function info(string $path): array
    {
        $disk = Storage::disk($this->disk);
        $size = $disk->exists($path) ? $disk->size($path) : 0;

        return [
            'path'          => $path,
            'url'           => $disk->url($path),
            'size'          => $size,
            'size_human'    => $this->humanFileSize($size),
            'last_modified' => $disk->exists($path) ? $disk->lastModified($path) : null,
            'exists'        => $disk->exists($path),
        ];
    }

    /**
     * List files
     */
    public function files(string $folder = '/'): array
    {
        return Storage::disk($this->disk)->files($folder);
    }

    /**
     * Move file
     */
    public function move(string $from, string $to): bool
    {
        return Storage::disk($this->disk)->move($from, $to);
    }

    /**
     * Human readable file size
     */
    private function humanFileSize(int $bytes): string
    {
        if ($bytes === 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i     = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
}