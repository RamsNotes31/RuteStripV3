<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class IpfsStorageService
{
    public function upload(string $absolutePath, string $name, array $metadata = []): array
    {
        if (! is_file($absolutePath)) {
            throw new \InvalidArgumentException('File GPX tidak ditemukan untuk upload IPFS.');
        }

        $jwt = config('services.pinata.jwt');

        if (! $jwt) {
            throw new \RuntimeException('PINATA_JWT belum dikonfigurasi.');
        }

        $start = microtime(true);

        $pinataMetadata = [
            'name' => $name,
        ];

        if ($metadata !== []) {
            $pinataMetadata['keyvalues'] = collect($metadata)
                ->filter(fn($value) => $value !== null && $value !== '')
                ->map(fn($value) => is_scalar($value) ? (string) $value : json_encode($value))
                ->all();
        }

        $response = Http::withToken($jwt)
            ->timeout(120)
            ->attach('file', fopen($absolutePath, 'r'), basename($absolutePath))
            ->post(rtrim(config('services.pinata.api_url'), '/') . '/pinning/pinFileToIPFS', [
                'pinataMetadata' => json_encode($pinataMetadata),
                'pinataOptions'  => json_encode(['cidVersion' => 1]),
            ]);

        $durationMs = (int) round((microtime(true) - $start) * 1000);

        if (! $response->successful()) {
            throw new \RuntimeException('Upload IPFS gagal: ' . $response->body());
        }

        $payload = $response->json();
        $cid = $payload['IpfsHash'] ?? null;

        if (! $cid) {
            throw new \RuntimeException('Response Pinata tidak berisi CID.');
        }

        return [
            'cid'         => $cid,
            'url'         => config('services.pinata.gateway_url') . $cid,
            'duration_ms' => $durationMs,
        ];
    }

    public function retrieve(string $cid): array
    {
        if (! $cid) {
            throw new \InvalidArgumentException('CID IPFS tidak tersedia.');
        }

        $start = microtime(true);
        $url = config('services.pinata.gateway_url') . $cid;

        $response = Http::timeout(120)->get($url);

        $durationMs = (int) round((microtime(true) - $start) * 1000);

        if (! $response->successful()) {
            throw new \RuntimeException('Retrieval IPFS gagal: HTTP ' . $response->status());
        }

        return [
            'body'        => $response->body(),
            'url'         => $url,
            'duration_ms' => $durationMs,
        ];
    }
}
