<?php
namespace App\Console\Commands;

use App\Models\HikingRoute;
use App\Services\GpxVersionService;
use Illuminate\Console\Command;

class BackfillGpxVersions extends Command
{
    protected $signature = 'provenance:backfill-gpx-versions';

    protected $description = 'Create initial GPX provenance versions for existing routes.';

    public function handle(GpxVersionService $gpxVersionService): int
    {
        $created = 0;
        $skipped = 0;

        HikingRoute::whereDoesntHave('gpxVersions')->chunkById(50, function ($routes) use ($gpxVersionService, &$created, &$skipped) {
            foreach ($routes as $route) {
                if (! $route->gpx_file_path || ! is_file(storage_path('app/public/' . $route->gpx_file_path))) {
                    $skipped++;
                    $this->warn("Skipped route {$route->id}: GPX file missing.");
                    continue;
                }

                $gpxVersionService->createInitialVersion(
                    $route,
                    $route->gpx_file_path,
                    null,
                    'Initial GPX version backfilled from existing route data.'
                );

                $created++;
            }
        });

        $this->info("Backfill completed. Created: {$created}, skipped: {$skipped}.");

        return self::SUCCESS;
    }
}
