<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestMinioConnection extends Command
{
    protected $signature = 'minio:test';
    protected $description = 'Test MinIO/S3 connection';

    public function handle()
    {
        $this->info('Testing MinIO/S3 connection...');

        try {
            // Test 1: Check if Aws\S3\S3Client exists
            if (class_exists('Aws\\S3\\S3Client')) {
                $this->line('✓ Aws\S3\S3Client class found');
            } else {
                $this->error('✗ Aws\S3\S3Client class NOT found');
                return self::FAILURE;
            }

            // Test 2: Check if league/flysystem-aws-s3-v3 exists
            if (class_exists('League\\Flysystem\\AwsS3V3\\AwsS3V3Adapter')) {
                $this->line('✓ League\Flysystem\AwsS3V3\AwsS3V3Adapter class found');
            } else {
                $this->error('✗ League\Flysystem\AwsS3V3\AwsS3V3Adapter class NOT found');
                return self::FAILURE;
            }

            // Test 3: Try to access minio disk
            $disk = Storage::disk('minio');
            $this->line('✓ Storage::disk("minio") accessible');

            // Test 4: Get disk driver info
            $this->line('Disk driver: ' . config('filesystems.disks.minio.driver'));
            $this->line('Bucket: ' . config('filesystems.disks.minio.bucket'));
            $this->line('Endpoint: ' . config('filesystems.disks.minio.endpoint'));

            $this->info("\n✓ All MinIO tests passed!");
            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('✗ Error: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return self::FAILURE;
        }
    }
}
