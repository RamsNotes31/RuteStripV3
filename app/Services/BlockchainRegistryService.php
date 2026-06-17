<?php
namespace App\Services;

use App\Models\BlockchainRegistryLog;
use App\Models\GpxVersion;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;

class BlockchainRegistryService
{
    public function register(GpxVersion $version): array
    {
        if (! $version->ipfs_cid) {
            $version->update(['blockchain_status' => 'failed_blockchain']);
            throw new \InvalidArgumentException('CID IPFS wajib tersedia sebelum registrasi blockchain.');
        }

        $version->update(['blockchain_status' => 'pending_blockchain']);

        $nodePath = config('services.blockchain.node_path', 'node');
        $scriptPath = base_path('blockchain/scripts/register-gpx-version.js');

        $process = new Process([
            $nodePath,
            $scriptPath,
            (string) $version->hiking_route_id,
            (string) $version->version_number,
            $version->file_hash,
            $version->ipfs_cid,
        ]);

        $process->setWorkingDirectory(base_path());
        $process->setTimeout(180);
        $process->setEnv(array_merge($_SERVER, $_ENV, [
            'BLOCKCHAIN_RPC_URL'          => config('services.blockchain.rpc_url'),
            'BLOCKCHAIN_PRIVATE_KEY'      => config('services.blockchain.private_key'),
            'BLOCKCHAIN_CONTRACT_ADDRESS' => config('services.blockchain.contract_address'),
            'BLOCKCHAIN_NETWORK'          => config('services.blockchain.network'),
        ]));

        try {
            $process->mustRun();
        } catch (\Throwable $e) {
            $version->update(['blockchain_status' => 'failed_blockchain']);
            $this->logBlockchain($version, 'register', 'failed', null, null, null, null, null, null, $e->getMessage());
            throw $e;
        }

        $payload = json_decode(trim($process->getOutput()), true);

        if (! is_array($payload) || empty($payload['transactionHash'])) {
            $version->update(['blockchain_status' => 'failed_blockchain']);
            $this->logBlockchain($version, 'register', 'failed', null, null, null, null, null, null, 'Output registrasi blockchain tidak valid: ' . $process->getOutput());
            throw new \RuntimeException('Output registrasi blockchain tidak valid: ' . $process->getOutput());
        }

        $version->update([
            'blockchain_tx_hash'          => $payload['transactionHash'],
            'blockchain_status'           => 'registered_blockchain',
            'blockchain_network'          => $payload['network'] ?? config('services.blockchain.network'),
            'blockchain_contract_address' => $payload['contractAddress'] ?? config('services.blockchain.contract_address'),
            'blockchain_registered_by'    => $payload['registeredBy'] ?? null,
            'blockchain_registered_at'    => now(),
        ]);

        $this->logBlockchain(
            $version,
            'register',
            'success',
            $payload['network'] ?? config('services.blockchain.network'),
            $payload['contractAddress'] ?? config('services.blockchain.contract_address'),
            $payload['transactionHash'],
            $payload['registeredBy'] ?? null,
            null,
            null,
            'GPX metadata registered on blockchain.'
        );

        return $payload;
    }

    public function refreshReceipt(GpxVersion $version): array
    {
        if (! $version->blockchain_tx_hash) {
            throw new \InvalidArgumentException('Tx hash belum tersedia untuk verifikasi Etherscan.');
        }

        $receipt = $this->rpc('eth_getTransactionReceipt', [$version->blockchain_tx_hash]);

        if (! is_array($receipt)) {
            throw new \RuntimeException('Receipt transaksi belum tersedia di Etherscan.');
        }

        $blockNumber = $this->hexToInt($receipt['blockNumber'] ?? null);
        $gasUsed = $this->hexToInt($receipt['gasUsed'] ?? null);
        $status = ($receipt['status'] ?? null) === '0x1' ? 'confirmed' : 'failed';
        $blockTimestamp = null;

        if ($blockNumber !== null) {
            $block = $this->rpc('eth_getBlockByNumber', [$receipt['blockNumber'], false]);

            $timestamp = is_array($block) ? $this->hexToInt($block['timestamp'] ?? null) : null;
            $blockTimestamp = $timestamp ? now()->setTimestamp($timestamp) : null;
        }

        $version->update([
            'blockchain_block_number' => $blockNumber,
            'blockchain_gas_used' => $gasUsed,
            'blockchain_confirmation_status' => $status,
            'blockchain_block_timestamp' => $blockTimestamp,
            'blockchain_receipt_checked_at' => now(),
        ]);

        $this->logBlockchain(
            $version,
            'receipt_check',
            $status,
            $version->blockchain_network,
            $version->blockchain_contract_address,
            $version->blockchain_tx_hash,
            $version->blockchain_registered_by,
            $blockNumber,
            $gasUsed,
            'Blockchain transaction receipt refreshed.'
        );

        return [
            'block_number' => $blockNumber,
            'gas_used' => $gasUsed,
            'status' => $status,
            'block_timestamp' => $blockTimestamp?->toDateTimeString(),
        ];
    }

    public function readOnChain(GpxVersion $version): array
    {
        $nodePath = config('services.blockchain.node_path', 'node');
        $scriptPath = base_path('blockchain/scripts/get-gpx-version.js');

        $process = new Process([
            $nodePath,
            $scriptPath,
            (string) $version->hiking_route_id,
            (string) $version->version_number,
        ]);

        $process->setWorkingDirectory(base_path());
        $process->setTimeout(60);
        $process->setEnv(array_merge($_SERVER, $_ENV, [
            'BLOCKCHAIN_RPC_URL' => config('services.blockchain.rpc_url'),
            'BLOCKCHAIN_CONTRACT_ADDRESS' => config('services.blockchain.contract_address'),
            'BLOCKCHAIN_NETWORK' => config('services.blockchain.network'),
        ]));

        $process->mustRun();
        $payload = json_decode(trim($process->getOutput()), true);

        if (! is_array($payload) || empty($payload['fileHash'])) {
            throw new \RuntimeException('Output readback blockchain tidak valid: ' . $process->getOutput());
        }

        return array_merge($payload, [
            'file_hash_matches_db' => ($payload['fileHash'] ?? null) === $version->file_hash,
            'ipfs_cid_matches_db' => ($payload['ipfsCid'] ?? null) === $version->ipfs_cid,
        ]);
    }

    private function hexToInt(?string $hex): ?int
    {
        if (! $hex) {
            return null;
        }

        return hexdec(str_replace('0x', '', $hex));
    }

    private function rpc(string $method, array $params): mixed
    {
        $apiKey = config('services.blockchain.etherscan_api_key');

        if ($apiKey) {
            $action = match ($method) {
                'eth_getTransactionReceipt' => 'eth_getTransactionReceipt',
                'eth_getBlockByNumber' => 'eth_getBlockByNumber',
                default => throw new \InvalidArgumentException('Unsupported Etherscan proxy method.'),
            };

            $query = [
                'module' => 'proxy',
                'action' => $action,
                'apikey' => $apiKey,
            ];

            if ($method === 'eth_getTransactionReceipt') {
                $query['txhash'] = $params[0];
            }

            if ($method === 'eth_getBlockByNumber') {
                $query['tag'] = $params[0];
                $query['boolean'] = $params[1] ? 'true' : 'false';
            }

            return Http::timeout(20)->get(config('services.blockchain.etherscan_api_url'), $query)->throw()->json('result');
        }

        $response = Http::timeout(20)->post(config('services.blockchain.rpc_url'), [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => $method,
            'params' => $params,
        ])->throw()->json();

        if (! empty($response['error'])) {
            throw new \RuntimeException($response['error']['message'] ?? 'Blockchain RPC error.');
        }

        return $response['result'] ?? null;
    }

    private function logBlockchain(GpxVersion $version, string $operation, string $status, ?string $network, ?string $contractAddress, ?string $txHash, ?string $registeredBy, ?int $blockNumber, ?int $gasUsed, ?string $message): void
    {
        BlockchainRegistryLog::create([
            'gpx_version_id' => $version->id,
            'operation' => $operation,
            'status' => $status,
            'network' => $network,
            'contract_address' => $contractAddress,
            'tx_hash' => $txHash,
            'registered_by' => $registeredBy,
            'block_number' => $blockNumber,
            'gas_used' => $gasUsed,
            'message' => $message,
            'logged_at' => now(),
        ]);
    }
}
