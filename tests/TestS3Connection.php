<?php

namespace Tests;

use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\TestCase;

class TestS3Connection extends TestCase
{
    public function test_s3_client_available()
    {
        try {
            // Try to access minio disk
            $disk = Storage::disk('minio');
            echo "✓ Storage disk 'minio' successfully accessed\n";
            return true;
        } catch (\Exception $e) {
            echo "✗ Error accessing minio disk: " . $e->getMessage() . "\n";
            return false;
        }
    }

    public function test_aws_s3_client_class()
    {
        try {
            // Check if AWS S3Client class exists
            $class = 'Aws\\S3\\S3Client';
            if (class_exists($class)) {
                echo "✓ AWS S3Client class found\n";
                return true;
            } else {
                echo "✗ AWS S3Client class not found\n";
                return false;
            }
        } catch (\Exception $e) {
            echo "✗ Error checking AWS S3Client: " . $e->getMessage() . "\n";
            return false;
        }
    }
}
