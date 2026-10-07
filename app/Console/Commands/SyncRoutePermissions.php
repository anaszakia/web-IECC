<?php

namespace App\Console\Commands;

use App\Services\PermissionSyncService;
use Illuminate\Console\Command;

class SyncRoutePermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permission:sync-routes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan routes for permission middleware and sync them to permissions table';

    /**
     * Execute the console command.
     */
    public function handle(PermissionSyncService $syncService): int
    {
        $this->info('Memindai routes untuk mendeteksi permission middleware...');

        $result = $syncService->syncFromRoutes();

        $this->info("Total permission terdeteksi di routes: {$result['total_detected']}");
        $this->info("Permission baru yang ditambahkan: {$result['created_count']}");

        if (!empty($result['created_slugs'])) {
            $this->table(['Slug Permission Baru'], array_map(fn($slug) => [$slug], $result['created_slugs']));
        }

        $this->info('Sinkronisasi selesai!');
        return Command::SUCCESS;
    }
}
