<?php
namespace App\Jobs;

use App\Models\BulkProvenanceAction;
use App\Models\GpxVersion;
use App\Services\BlockchainRegistryService;
use App\Services\GpxVersionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunBulkProvenanceAction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;

    public function __construct(public int $bulkActionId)
    {
    }

    public function handle(GpxVersionService $gpxVersionService, BlockchainRegistryService $blockchainRegistryService): void
    {
        $bulkAction = BulkProvenanceAction::findOrFail($this->bulkActionId);

        $bulkAction->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            match ($bulkAction->action) {
                'verify_active' => $this->verifyActive($bulkAction, $gpxVersionService),
                'upload_missing_ipfs' => $this->uploadMissingIpfs($bulkAction, $gpxVersionService),
                'register_missing_blockchain' => $this->registerMissingBlockchain($bulkAction, $blockchainRegistryService),
                'refresh_etherscan_receipts' => $this->refreshEtherscanReceipts($bulkAction, $blockchainRegistryService),
                default => throw new \InvalidArgumentException('Bulk action tidak dikenal.'),
            };

            $bulkAction->update([
                'status' => $bulkAction->failed_count > 0 ? 'completed_with_errors' : 'completed',
                'finished_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $bulkAction->update([
                'status' => 'failed',
                'last_error' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            throw $e;
        }
    }

    private function verifyActive(BulkProvenanceAction $bulkAction, GpxVersionService $gpxVersionService): void
    {
        GpxVersion::where('is_active', true)->chunkById(25, function ($versions) use ($bulkAction, $gpxVersionService) {
            foreach ($versions as $version) {
                $this->runItem($bulkAction, fn() => $gpxVersionService->verify($version, $bulkAction->started_by));
            }
        });
    }

    private function uploadMissingIpfs(BulkProvenanceAction $bulkAction, GpxVersionService $gpxVersionService): void
    {
        GpxVersion::with('route')->whereNull('ipfs_cid')->chunkById(10, function ($versions) use ($bulkAction, $gpxVersionService) {
            foreach ($versions as $version) {
                $this->runItem($bulkAction, function () use ($version, $gpxVersionService) {
                    $gpxVersionService->ensureIpfsUpload($version);

                    if (! $version->refresh()->ipfs_cid) {
                        throw new \RuntimeException('Upload IPFS tidak menghasilkan CID.');
                    }
                });
            }
        });
    }

    private function registerMissingBlockchain(BulkProvenanceAction $bulkAction, BlockchainRegistryService $blockchainRegistryService): void
    {
        GpxVersion::whereNotNull('ipfs_cid')->whereNull('blockchain_tx_hash')->chunkById(5, function ($versions) use ($bulkAction, $blockchainRegistryService) {
            foreach ($versions as $version) {
                $this->runItem($bulkAction, fn() => $blockchainRegistryService->register($version));
            }
        });
    }

    private function refreshEtherscanReceipts(BulkProvenanceAction $bulkAction, BlockchainRegistryService $blockchainRegistryService): void
    {
        GpxVersion::whereNotNull('blockchain_tx_hash')->chunkById(5, function ($versions) use ($bulkAction, $blockchainRegistryService) {
            foreach ($versions as $version) {
                $this->runItem($bulkAction, fn() => $blockchainRegistryService->refreshReceipt($version));
            }
        });
    }

    private function runItem(BulkProvenanceAction $bulkAction, callable $callback): void
    {
        try {
            $callback();
            $bulkAction->increment('processed_count');
        } catch (\Throwable $e) {
            $bulkAction->increment('failed_count');
            $bulkAction->update(['last_error' => $e->getMessage()]);
        }
    }
}
