<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestMinioFiles extends Command
{
    protected $signature = 'minio:list-files';
    protected $description = 'List all files in MinIO bucket';

    public function handle()
    {
        try {
            $this->info('Connecting to MinIO...');
            $disk = Storage::disk('minio');
            
            // Get bucket info
            $bucket = config('filesystems.disks.minio.bucket');
            $endpoint = config('filesystems.disks.minio.endpoint');
            
            $this->line("Bucket: {$bucket}");
            $this->line("Endpoint: {$endpoint}");

            // List files
            $this->info("\nListing files in bucket...\n");
            
            $files = $disk->allFiles();
            
            if (empty($files)) {
                $this->warn("No files found in bucket");
                return self::FAILURE;
            }

            $this->line("Total files: " . count($files));
            
            foreach ($files as $file) {
                $size = $disk->size($file);
                $url = Storage::disk('minio')->url($file);
                // Fix localhost
                $url = str_replace('127.0.0.1', 'localhost', $url);
                
                $this->line("  📄 {$file}");
                $this->line("     Size: " . $this->humanFileSize($size));
                $this->line("     URL: {$url}");
                $this->line("");
            }

            $this->info("✓ Success!");
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return self::FAILURE;
        }
    }

    private function humanFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }
}
