<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    */

    'minio_active' => (bool) env('MINIO_ACTIVE', true),

    'default' => env('MINIO_ACTIVE', true) ? 'minio' : env('FILESYSTEM_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app/private'),
            'serve'  => true,
            'throw'  => false,
        ],

        'public' => [
            'driver'     => 'local',
            'root'       => storage_path('app/public'),
            'url'        => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw'      => false,
        ],

        'minio' => [
            'driver'                  => 's3',
            'key'                     => env('MINIO_KEY'),
            'secret'                  => env('MINIO_SECRET'),
            'region'                  => env('MINIO_REGION', 'us-east-1'),
            'bucket'                  => env('MINIO_BUCKET'),
            'url'                     => env('MINIO_URL'),
            'endpoint'                => env('MINIO_ENDPOINT'),
            'use_path_style_endpoint' => true,
            'visibility'              => 'public',
            // Add SSL verification disable jika development
            'ssl_verify'              => env('MINIO_SSL_VERIFY', true),
            // Fix URL untuk browser access
            'use_accelerate_endpoint' => false,
            'throw'                   => true,
        ],

    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
