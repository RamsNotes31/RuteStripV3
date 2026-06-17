<?php
namespace App\Console\Commands;

use App\Models\GpxVersion;
use Illuminate\Console\Command;

class TamperGpxVersion extends Command
{
    protected $signature = 'provenance:tamper-gpx-version {version_id : GPX version ID to tamper}';

    protected $description = 'Append a harmless marker to a GPX file to simulate integrity tampering.';

    public function handle(): int
    {
        $version = GpxVersion::find($this->argument('version_id'));

        if (! $version) {
            $this->error('GPX version not found.');
            return self::FAILURE;
        }

        $path = storage_path('app/public/' . $version->gpx_file_path);

        if (! is_file($path)) {
            $this->error('GPX file not found: ' . $version->gpx_file_path);
            return self::FAILURE;
        }

        file_put_contents($path, PHP_EOL . '<!-- tampered for integrity evaluation at ' . now()->toIso8601String() . ' -->' . PHP_EOL, FILE_APPEND);

        $this->info("Tampered GPX version {$version->id} for route {$version->hiking_route_id}.");
        $this->warn('Run Verify Ulang from the provenance page to produce an invalid verification log.');

        return self::SUCCESS;
    }
}
