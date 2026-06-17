<?php
namespace App\Services;

use App\Models\GpxVersion;
use App\Models\HikingRoute;
use App\Models\IpfsUploadLog;
use App\Models\VerificationLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GpxVersionService
{
    public function __construct(
        private GpxHashService $hashService,
        private IpfsStorageService $ipfsStorageService
    )
    {
    }

    public function createInitialVersion(HikingRoute $route, string $filePath, ?int $uploadedBy = null, ?string $changeLog = null): GpxVersion
    {
        return $this->createVersion($route, $filePath, $uploadedBy, $changeLog ?: 'Initial GPX upload.');
    }

    public function createVersion(HikingRoute $route, string $filePath, ?int $uploadedBy = null, ?string $changeLog = null): GpxVersion
    {
        $versionNumber = ((int) $route->gpxVersions()->max('version_number')) + 1;
        $absolutePath  = storage_path('app/public/' . $filePath);
        $fileHash      = $this->hashService->hash($absolutePath);

        $route->gpxVersions()->update(['is_active' => false]);

        $version = $route->gpxVersions()->create([
            'version_number'      => $versionNumber,
            'gpx_file_path'       => $filePath,
            'file_hash'           => $fileHash,
            'verification_status' => 'verified',
            'ipfs_status'         => 'pending_ipfs',
            'blockchain_status'   => 'pending_blockchain',
            'change_log'          => $changeLog,
            'uploaded_by'         => $uploadedBy,
            'is_active'           => true,
            'verified_at'         => now(),
        ]);

        $this->uploadToIpfs($version, $absolutePath, $route);

        $this->logVerification($version, $fileHash, $fileHash, 'verified', 'Hash dibuat saat upload GPX.', $uploadedBy);

        return $version;
    }

    public function verify(GpxVersion $version, ?int $verifiedBy = null): GpxVersion
    {
        $absolutePath = storage_path('app/public/' . $version->gpx_file_path);

        try {
            $result = $this->hashService->verify($absolutePath, $version->file_hash);

            $version->update([
                'verification_status' => $result['status'],
                'verified_at'         => now(),
            ]);

            $this->logVerification(
                $version,
                $version->file_hash,
                $result['actual_hash'],
                $result['status'],
                $result['message'],
                $verifiedBy
            );
        } catch (\Throwable $e) {
            $version->update(['verification_status' => 'verification_failed']);

            $this->logVerification(
                $version,
                $version->file_hash,
                null,
                'verification_failed',
                $e->getMessage(),
                $verifiedBy
            );
        }

        return $version->refresh();
    }

    public function verifyViaIpfs(GpxVersion $version, ?int $verifiedBy = null): GpxVersion
    {
        if (! $version->ipfs_cid) {
            $version->update([
                'verification_status' => 'verification_failed',
                'ipfs_status' => 'failed_ipfs',
            ]);

            $this->logVerification(
                $version,
                $version->file_hash,
                null,
                'verification_failed',
                'CID IPFS belum tersedia untuk versi GPX ini.',
                $verifiedBy,
                'ipfs'
            );

            return $version->refresh();
        }

        try {
            $retrieved = $this->ipfsStorageService->retrieve($version->ipfs_cid);
            $result = $this->hashService->verifyContent($retrieved['body'], $version->file_hash);

            $version->update([
                'verification_status'       => $result['status'],
                'ipfs_retrieval_time_ms'    => $retrieved['duration_ms'],
                'verified_at'               => now(),
            ]);

            $this->logVerification(
                $version,
                $version->file_hash,
                $result['actual_hash'],
                $result['status'],
                $result['message'] . ' Retrieval time: ' . $retrieved['duration_ms'] . ' ms.',
                $verifiedBy,
                'ipfs'
            );

            $this->logIpfs($version, 'retrieve', 'success', $version->ipfs_cid, $retrieved['url'], $retrieved['duration_ms'], $result['message']);
        } catch (\Throwable $e) {
            $version->update([
                'verification_status' => 'verification_failed',
                'ipfs_status' => $version->ipfs_cid ? $version->ipfs_status : 'failed_ipfs',
            ]);

            $this->logVerification(
                $version,
                $version->file_hash,
                null,
                'verification_failed',
                $e->getMessage(),
                $verifiedBy,
                'ipfs'
            );

            $this->logIpfs($version, 'retrieve', 'failed', $version->ipfs_cid, $version->ipfs_url, null, $e->getMessage());
        }

        return $version->refresh();
    }

    public function restore(GpxVersion $version, ?int $uploadedBy = null): GpxVersion
    {
        $route = $version->route;

        $route->gpxVersions()->update(['is_active' => false]);
        $version->update(['is_active' => true]);

        $route->update(['gpx_file_path' => $version->gpx_file_path]);

        $this->logVerification(
            $version,
            $version->file_hash,
            $version->file_hash,
            $version->verification_status,
            'Versi GPX dipulihkan sebagai versi aktif.',
            $uploadedBy
        );

        return $version->refresh();
    }

    public function ensureIpfsUpload(GpxVersion $version): GpxVersion
    {
        if ($version->ipfs_cid) {
            return $version;
        }

        $this->uploadToIpfs(
            $version,
            storage_path('app/public/' . $version->gpx_file_path),
            $version->route
        );

        return $version->refresh();
    }

    public function deleteFilesForRoute(HikingRoute $route): void
    {
        $paths = $route->gpxVersions()->pluck('gpx_file_path');

        if ($route->gpx_file_path) {
            $paths->push($route->gpx_file_path);
        }

        $paths->unique()->each(function (string $path) {
            Storage::disk('public')->delete($path);
        });
    }

    private function logVerification(GpxVersion $version, string $expectedHash, ?string $actualHash, string $status, ?string $message, ?int $verifiedBy, string $source = 'local'): void
    {
        VerificationLog::create([
            'gpx_version_id' => $version->id,
            'source'         => $source,
            'expected_hash'  => $expectedHash,
            'actual_hash'    => $actualHash,
            'status'         => $status,
            'message'        => $message,
            'verified_by'    => $verifiedBy,
            'verified_at'    => now(),
        ]);
    }

    private function uploadToIpfs(GpxVersion $version, string $absolutePath, HikingRoute $route): void
    {
        try {
            $result = $this->ipfsStorageService->upload(
                $absolutePath,
                $route->name . ' v' . $version->version_number,
                [
                    'route_id'                => $route->id,
                    'route_name'              => $route->name,
                    'gpx_version_id'          => $version->id,
                    'version_number'          => $version->version_number,
                    'file_hash_sha256'        => $version->file_hash,
                    'verification_status'     => $version->verification_status,
                    'distance_km'             => $route->distance_km,
                    'elevation_gain_m'        => $route->elevation_gain_m,
                    'naismith_duration_hour'  => $route->naismith_duration_hour,
                    'average_grade_pct'       => $route->average_grade_pct,
                    'change_log'              => $version->change_log,
                    'provenance_created_at'   => $version->created_at?->toIso8601String(),
                ]
            );

            $version->update([
                'ipfs_cid'            => $result['cid'],
                'ipfs_url'            => $result['url'],
                'ipfs_status'         => 'uploaded_ipfs',
                'ipfs_upload_time_ms' => $result['duration_ms'],
            ]);

            $this->logIpfs($version, 'upload', 'success', $result['cid'], $result['url'], $result['duration_ms'], 'GPX uploaded to Pinata/IPFS.');
        } catch (\Throwable $e) {
            $version->update(['ipfs_status' => 'failed_ipfs']);
            $this->logIpfs($version, 'upload', 'failed', null, null, null, $e->getMessage());
            Log::warning('IPFS upload failed for GPX version ' . $version->id . ': ' . $e->getMessage());
        }
    }

    private function logIpfs(GpxVersion $version, string $operation, string $status, ?string $cid, ?string $url, ?int $durationMs, ?string $message): void
    {
        IpfsUploadLog::create([
            'gpx_version_id' => $version->id,
            'operation' => $operation,
            'status' => $status,
            'cid' => $cid,
            'url' => $url,
            'duration_ms' => $durationMs,
            'message' => $message,
            'logged_at' => now(),
        ]);
    }
}
