$s3 = \Illuminate\Support\Facades\Storage::disk('minio')->getClient();
$s3->putBucketPolicy([
    'Bucket' => 'dev',
    'Policy' => json_encode([
        'Version' => '2012-10-17',
        'Statement' => [[
            'Effect'    => 'Allow',
            'Principal' => ['AWS' => ['*']],
            'Action'    => ['s3:GetObject'],
            'Resource'  => ['arn:aws:s3:::dev/*']
        ]]
    ])
]);