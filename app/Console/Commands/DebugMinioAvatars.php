<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\MinioService;

class DebugMinioAvatars extends Command
{
    protected $signature = 'minio:debug-avatars';
    protected $description = 'Debug MinIO avatars for all users';

    public function handle()
    {
        $minio = app(MinioService::class);
        $users = User::all();

        $this->info("Total users: {$users->count()}\n");

        foreach ($users as $user) {
            $this->line("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━");
            $this->info("User: {$user->name} (ID: {$user->id})");
            $this->line("Avatar path in DB: " . ($user->avatar ?? 'NULL'));

            if (!$user->avatar) {
                $this->warn("  ✗ No avatar path stored");
                continue;
            }

            try {
                // Check if file exists
                $exists = $minio->exists($user->avatar);
                $this->line("File exists: " . ($exists ? '✓ YES' : '✗ NO'));

                // Get URL
                $url = $minio->url($user->avatar);
                $this->line("Generated URL: {$url}");

                // Get file info
                if ($exists) {
                    $info = $minio->info($user->avatar);
                    $this->line("File size: {$info['size_human']}");
                    $this->line("Last modified: {$info['last_modified']}");
                }

                // Test minio_avatar function
                $avatarUrl = minio_avatar($user->avatar, $user->name);
                $this->line("minio_avatar() result: {$avatarUrl}");

            } catch (\Exception $e) {
                $this->error("Error: " . $e->getMessage());
            }
        }

        $this->info("\n✓ Debug complete");
    }
}
