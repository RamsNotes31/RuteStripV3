<?php
namespace App\Http\Controllers;

use App\Models\GpxVersion;
use App\Models\HikingRoute;
use App\Models\VerificationLog;
use App\Services\BlockchainRegistryService;
use App\Services\GpxVersionService;
use App\Services\PythonProcessorService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProvenanceController extends Controller
{
    public function __construct(
        private GpxVersionService $gpxVersionService,
        private PythonProcessorService $pythonService,
        private BlockchainRegistryService $blockchainRegistryService
    )
    {
    }

    public function show(HikingRoute $route): View
    {
        $route->load([
            'activeGpxVersion',
            'gpxVersions' => fn($query) => $query->with(['uploader', 'verificationLogs' => fn($logs) => $logs->latest()])
                ->orderByDesc('version_number'),
        ]);

        return view('routes.provenance', compact('route'));
    }

    public function createVersion(HikingRoute $route): View
    {
        $this->ensureAdmin();

        $route->load('activeGpxVersion');

        return view('routes.version-create', compact('route'));
    }

    public function storeVersion(Request $request, HikingRoute $route): RedirectResponse
    {
        $this->ensureAdmin();

        $request->validate([
            'gpx_file'   => 'required|file|max:10240',
            'change_log' => 'required|string|max:1000',
        ]);

        $gpxFile   = $request->file('gpx_file');
        $extension = strtolower($gpxFile->getClientOriginalExtension());

        if (! in_array($extension, ['gpx', 'xml'])) {
            return back()->withErrors(['gpx_file' => 'File harus berformat GPX atau XML.'])->withInput();
        }

        $fileName = time() . '_' . uniqid() . '_' . $gpxFile->getClientOriginalName();
        $filePath = $gpxFile->storeAs('gpx_files', $fileName, 'public');
        $fullPath = storage_path('app/public/' . $filePath);

        try {
            $result = $this->pythonService->ingest($fullPath);

            $route->update([
                'gpx_file_path'          => $filePath,
                'route_coordinates'      => $result['route_coordinates'] ?? null,
                'distance_km'            => $result['distance_km'] ?? null,
                'elevation_gain_m'       => $result['elevation_gain_m'] ?? null,
                'naismith_duration_hour' => $result['naismith_duration_hour'] ?? null,
                'average_grade_pct'      => $result['average_grade_pct'] ?? null,
                'narrative_text'         => $result['narrative_text'] ?? $route->narrative_text,
                'sbert_embedding'        => $result['embedding'] ?? $route->sbert_embedding,
            ]);

            $version = $this->gpxVersionService->createVersion(
                $route,
                $filePath,
                Auth::id(),
                $request->input('change_log')
            );

            return redirect()
                ->route('routes.provenance', $route)
                ->with('success', "Versi GPX v{$version->version_number} berhasil dibuat dan dijadikan versi aktif.");
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($filePath);

            return back()->withErrors(['gpx_file' => 'Gagal memproses versi GPX baru: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function verify(HikingRoute $route, GpxVersion $version): RedirectResponse
    {
        $this->authorizeRouteVersion($route, $version);
        $this->ensureAdmin();

        $verified = $this->gpxVersionService->verify($version, Auth::id());

        return back()->with(
            $verified->verification_status === 'verified' ? 'success' : 'warning',
            $verified->verification_status === 'verified'
                ? 'Verifikasi berhasil: hash GPX cocok.'
                : 'Verifikasi selesai, tetapi hash GPX tidak valid atau gagal diverifikasi.'
        );
    }

    public function verifyIpfs(HikingRoute $route, GpxVersion $version): RedirectResponse
    {
        $this->authorizeRouteVersion($route, $version);
        $this->ensureAdmin();

        $verified = $this->gpxVersionService->verifyViaIpfs($version, Auth::id());

        return back()->with(
            $verified->verification_status === 'verified' ? 'success' : 'warning',
            $verified->verification_status === 'verified'
                ? 'Verifikasi IPFS berhasil: hash GPX dari gateway cocok.'
                : 'Verifikasi IPFS selesai, tetapi hash tidak valid atau retrieval gagal.'
        );
    }

    public function restore(HikingRoute $route, GpxVersion $version): RedirectResponse
    {
        $this->authorizeRouteVersion($route, $version);
        $this->ensureAdmin();

        $this->gpxVersionService->restore($version, Auth::id());

        return back()->with('success', "Versi GPX v{$version->version_number} dipulihkan sebagai versi aktif.");
    }

    public function registerBlockchain(HikingRoute $route, GpxVersion $version): RedirectResponse
    {
        $this->authorizeRouteVersion($route, $version);
        $this->ensureAdmin();

        try {
            $payload = $this->blockchainRegistryService->register($version);

            return back()->with('success', 'Metadata GPX berhasil didaftarkan ke blockchain. Tx: ' . $payload['transactionHash']);
        } catch (\Throwable $e) {
            return back()->with('warning', 'Registrasi blockchain gagal: ' . $e->getMessage());
        }
    }

    public function exportVerificationLogs(HikingRoute $route): StreamedResponse
    {
        $this->ensureAdmin();

        $fileName = 'verification_logs_route_' . $route->id . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($route) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'route_id',
                'route_name',
                'gpx_version_id',
                'version_number',
                'source',
                'expected_hash',
                'actual_hash',
                'status',
                'message',
                'verified_by',
                'verified_at',
            ]);

            VerificationLog::query()
                ->whereHas('gpxVersion', fn($query) => $query->where('hiking_route_id', $route->id))
                ->with(['gpxVersion', 'verifier'])
                ->oldest('verified_at')
                ->chunk(100, function ($logs) use ($handle, $route) {
                    foreach ($logs as $log) {
                        fputcsv($handle, [
                            $route->id,
                            $route->name,
                            $log->gpx_version_id,
                            $log->gpxVersion?->version_number,
                            $log->source,
                            $log->expected_hash,
                            $log->actual_hash,
                            $log->status,
                            $log->message,
                            $log->verifier?->email,
                            $log->verified_at?->toDateTimeString(),
                        ]);
                    }
                });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function downloadVersion(HikingRoute $route, GpxVersion $version): BinaryFileResponse
    {
        $this->authorizeRouteVersion($route, $version);

        abort_unless(Storage::disk('public')->exists($version->gpx_file_path), 404, 'File GPX tidak ditemukan.');

        $extension = pathinfo($version->gpx_file_path, PATHINFO_EXTENSION) ?: 'gpx';
        $fileName = Str::slug($route->name) . '-v' . $version->version_number . '.' . $extension;

        return response()->download(
            Storage::disk('public')->path($version->gpx_file_path),
            $fileName,
            ['Content-Type' => 'application/gpx+xml']
        );
    }

    private function authorizeRouteVersion(HikingRoute $route, GpxVersion $version): void
    {
        abort_unless($version->hiking_route_id === $route->id, 404);
    }

    private function ensureAdmin(): void
    {
        abort_unless(Auth::check() && Auth::user()->isAdmin(), 403);
    }
}
