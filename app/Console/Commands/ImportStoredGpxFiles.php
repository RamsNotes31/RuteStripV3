<?php
namespace App\Console\Commands;

use App\Models\HikingRoute;
use App\Services\GpxVersionService;
use App\Services\PythonProcessorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportStoredGpxFiles extends Command
{
    protected $signature = 'routes:import-stored-gpx {--limit= : Maximum files to import}';

    protected $description = 'Import GPX files already stored in storage/app/public/gpx_files into hiking_routes.';

    public function handle(PythonProcessorService $pythonService, GpxVersionService $gpxVersionService): int
    {
        $files = collect(Storage::disk('public')->files('gpx_files'))
            ->filter(fn(string $path) => in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['gpx', 'xml'], true))
            ->reject(fn(string $path) => HikingRoute::where('gpx_file_path', $path)->exists())
            ->values();

        if ($limit = (int) $this->option('limit')) {
            $files = $files->take($limit);
        }

        if ($files->isEmpty()) {
            $this->info('No new GPX files to import.');
            return self::SUCCESS;
        }

        $created = 0;
        $failed = 0;

        foreach ($files as $filePath) {
            $this->line("Importing {$filePath}...");

            try {
                $result = $pythonService->ingest(storage_path('app/public/' . $filePath));

                $route = HikingRoute::create([
                    'name'                   => $this->routeName($filePath),
                    'gpx_file_path'          => $filePath,
                    'route_coordinates'      => $result['route_coordinates'] ?? null,
                    'distance_km'            => $result['distance_km'] ?? null,
                    'elevation_gain_m'       => $result['elevation_gain_m'] ?? null,
                    'naismith_duration_hour' => $result['naismith_duration_hour'] ?? null,
                    'average_grade_pct'      => $result['average_grade_pct'] ?? null,
                    'narrative_text'         => $result['narrative_text'] ?? null,
                    'sbert_embedding'        => $result['embedding'] ?? null,
                ]);

                $gpxVersionService->createInitialVersion(
                    $route,
                    $filePath,
                    null,
                    'Imported from stored GPX file.'
                );

                $created++;
            } catch (\Throwable $e) {
                $failed++;
                $this->error("Failed {$filePath}: {$e->getMessage()}");
            }
        }

        $this->info("Import completed. Created: {$created}, failed: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function routeName(string $filePath): string
    {
        $name = pathinfo($filePath, PATHINFO_FILENAME);
        $name = preg_replace('/^\d+_[a-f0-9]+_/i', '', $name) ?: $name;

        return Str::of($name)->replace(['_', '-'], ' ')->squish()->title()->toString();
    }
}
