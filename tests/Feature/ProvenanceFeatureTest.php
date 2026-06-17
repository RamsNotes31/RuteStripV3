<?php

namespace Tests\Feature;

use App\Jobs\RunBulkProvenanceAction;
use App\Models\BlockchainRegistryLog;
use App\Models\GpxVersion;
use App\Models\HikingRoute;
use App\Models\IpfsUploadLog;
use App\Models\RecommendationEvaluationQuery;
use App\Models\User;
use App\Services\BlockchainRegistryService;
use App\Services\GpxVersionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProvenanceFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_verifies_a_valid_gpx_hash(): void
    {
        [, $version] = $this->makeRouteWithVersion();

        app(GpxVersionService::class)->verify($version, $this->makeAdminUser()->id);

        $this->assertSame('verified', $version->refresh()->verification_status);
        $this->assertTrue($version->verificationLogs()->where('status', 'verified')->exists());
    }

    public function test_marks_a_tampered_gpx_hash_as_invalid(): void
    {
        [, $version, $absolutePath] = $this->makeRouteWithVersion();

        file_put_contents($absolutePath, '<gpx><trk><name>Tampered Track</name></trk></gpx>');

        app(GpxVersionService::class)->verify($version, $this->makeAdminUser()->id);

        $this->assertSame('invalid', $version->refresh()->verification_status);
        $this->assertTrue($version->verificationLogs()->where('status', 'invalid')->exists());
    }

    public function test_filters_routes_by_missing_blockchain_provenance(): void
    {
        [$route] = $this->makeRouteWithVersion(['ipfs_cid' => 'bafktestcid']);

        $this->get(route('routes.index', ['provenance_filter' => 'missing_blockchain']))
            ->assertOk()
            ->assertSee('Filter Provenance')
            ->assertSee($route->name);
    }

    public function test_renders_explicit_pending_provenance_statuses(): void
    {
        [$route] = $this->makeRouteWithVersion();

        $this->get(route('routes.index'))
            ->assertOk()
            ->assertSee($route->name)
            ->assertSee('PENDING IPFS')
            ->assertSee('PENDING BLOCKCHAIN')
            ->assertSee('File GPX cocok dengan hash tersimpan')
            ->assertSee('GPX masih menunggu upload ke IPFS')
            ->assertSee('Metadata GPX masih menunggu registrasi blockchain');

        $this->actingAs($this->makeAdminUser())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Pending IPFS')
            ->assertSee('Pending blockchain')
            ->assertSee('Penjelasan Status Provenance');
    }

    public function test_downloads_specific_gpx_version_file(): void
    {
        [$route, $version] = $this->makeRouteWithVersion();

        $this->get(route('routes.provenance.download-version', [$route, $version]))
            ->assertOk()
            ->assertDownload('test-route-v1.gpx');
    }

    public function test_renders_the_evidence_pack_page_for_admins(): void
    {
        $this->makeRouteWithVersion([
            'ipfs_cid' => 'bafktestcid',
            'ipfs_url' => 'https://gateway.pinata.cloud/ipfs/bafktestcid',
            'blockchain_tx_hash' => '0x' . str_repeat('a', 64),
            'blockchain_contract_address' => '0x' . str_repeat('b', 40),
            'blockchain_confirmation_status' => 'confirmed',
        ]);

        $this->actingAs($this->makeAdminUser())
            ->get(route('admin.paper-evidence-pack'))
            ->assertOk()
            ->assertSee('Evidence Pack Provenance')
            ->assertSee('Ready for Paper Checklist')
            ->assertSee('Blockchain Registry On-chain vs DB')
            ->assertSee('Print Evidence Pack');
    }

    public function test_renders_and_exports_paper_readiness_checker(): void
    {
        $this->makeRouteWithVersion([
            'ipfs_cid' => 'bafktestcid',
            'ipfs_url' => 'https://gateway.pinata.cloud/ipfs/bafktestcid',
            'blockchain_tx_hash' => '0x' . str_repeat('a', 64),
        ]);

        $this->actingAs($this->makeAdminUser())
            ->get(route('admin.paper-readiness'))
            ->assertOk()
            ->assertSee('Dataset Readiness Checker')
            ->assertSee('Route dataset tersedia')
            ->assertSee('Recommendation Precision@K dataset')
            ->assertSee('Download Readiness CSV');

        $this->actingAs($this->makeAdminUser())
            ->get(route('admin.paper-readiness.export'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_renders_before_after_and_captures_evaluation_snapshot(): void
    {
        $this->makeRouteWithVersion();

        $this->actingAs($this->makeAdminUser())
            ->get(route('admin.paper-outputs.provenance'))
            ->assertOk()
            ->assertSee('Tabel Before vs After Provenance')
            ->assertSee('Data integrity')
            ->assertSee('Evaluation Results Snapshots');

        $this->actingAs($this->makeAdminUser())
            ->post(route('admin.paper-outputs.provenance.capture-evaluation-snapshot'))
            ->assertRedirect();

        $this->assertDatabaseHas('evaluation_results', [
            'metric_name' => 'Hash Verification Accuracy (%)',
        ]);

        $this->actingAs($this->makeAdminUser())
            ->get(route('admin.paper-outputs.provenance.export-before-after'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->actingAs($this->makeAdminUser())
            ->get(route('admin.paper-outputs.provenance.export-evaluation-results'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_on_chain_readback_error_is_human_readable(): void
    {
        [, $version] = $this->makeRouteWithVersion([
            'ipfs_cid' => 'bafktestcid',
            'blockchain_tx_hash' => '0x' . str_repeat('a', 64),
        ]);

        $this->app->instance(BlockchainRegistryService::class, new class extends BlockchainRegistryService {
            public function readOnChain(GpxVersion $version): array
            {
                throw new \RuntimeException('The command "node script.js" failed. Working directory: C:\\app Output: JsonRpcProvider failed to detect network Error Output: getaddrinfo EAI_FAIL 1rpc.io');
            }
        });

        $this->actingAs($this->makeAdminUser())
            ->post(route('admin.paper-outputs.provenance.verify-on-chain', $version))
            ->assertRedirect()
            ->assertSessionHas('warning', function (string $message) {
                return str_contains($message, 'RPC blockchain tidak dapat diakses')
                    && ! str_contains($message, 'Working directory')
                    && ! str_contains($message, 'node script.js');
            });
    }

    public function test_exports_provenance_csv_responses(): void
    {
        $this->makeRouteWithVersion();

        $this->actingAs($this->makeAdminUser())
            ->get(route('admin.evaluation.provenance.export-versions'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_renders_and_exports_black_box_testing_table(): void
    {
        $this->actingAs($this->makeAdminUser())
            ->get(route('admin.paper-outputs.provenance'))
            ->assertOk()
            ->assertSee('Tabel Black Box Testing')
            ->assertSee('BB-001');

        $this->actingAs($this->makeAdminUser())
            ->get(route('admin.paper-outputs.provenance.export-black-box-testing'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_stores_and_exports_sus_responses(): void
    {
        $payload = [
            'respondent_name' => 'Reviewer 1',
            'respondent_role' => 'Reviewer',
            'q1' => 5,
            'q2' => 1,
            'q3' => 5,
            'q4' => 1,
            'q5' => 5,
            'q6' => 1,
            'q7' => 5,
            'q8' => 1,
            'q9' => 5,
            'q10' => 1,
            'notes' => 'Easy to use.',
        ];

        $this->post(route('evaluation.sus.store'), $payload)
            ->assertRedirect(route('evaluation.sus.create'));

        $this->assertDatabaseHas('sus_responses', [
            'respondent_name' => 'Reviewer 1',
            'score' => 100,
        ]);

        $this->actingAs($this->makeAdminUser())
            ->get(route('admin.paper-outputs.provenance'))
            ->assertOk()
            ->assertSee('Tabel SUS Usability Score')
            ->assertSee('Reviewer 1');

        $this->actingAs($this->makeAdminUser())
            ->get(route('admin.paper-outputs.provenance.export-sus-responses'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_renders_and_exports_recommendation_precision_table(): void
    {
        $routes = collect(range(1, 10))->map(function (int $index) {
            return HikingRoute::create([
                'name' => 'Evaluation Route ' . $index,
                'gpx_file_path' => 'gpx_files/evaluation-' . $index . '.gpx',
                'distance_km' => 10 + $index,
                'elevation_gain_m' => 500 + $index,
                'naismith_duration_hour' => 3,
                'average_grade_pct' => 8,
                'sbert_embedding' => $index === 1 ? [1, 0, 0] : [0, 1, 0],
            ]);
        });

        RecommendationEvaluationQuery::create([
            'query' => 'jalur evaluasi utama',
            'query_embedding' => [1, 0, 0],
            'relevant_route_ids' => [$routes->first()->id],
            'notes' => 'Feature test ground truth.',
        ]);

        $this->actingAs($this->makeAdminUser())
            ->get(route('admin.paper-outputs.provenance'))
            ->assertOk()
            ->assertSee('Tabel Precision@5 dan Precision@10')
            ->assertSee('jalur evaluasi utama')
            ->assertSee('0.2000')
            ->assertSee('0.1000');

        $this->actingAs($this->makeAdminUser())
            ->get(route('admin.paper-outputs.provenance.export-recommendation-precision'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_renders_and_exports_ipfs_and_blockchain_audit_logs(): void
    {
        [, $version] = $this->makeRouteWithVersion([
            'ipfs_cid' => 'bafktestcid',
            'ipfs_url' => 'https://gateway.pinata.cloud/ipfs/bafktestcid',
            'blockchain_tx_hash' => '0x' . str_repeat('c', 64),
        ]);

        IpfsUploadLog::create([
            'gpx_version_id' => $version->id,
            'operation' => 'upload',
            'status' => 'success',
            'cid' => 'bafktestcid',
            'url' => 'https://gateway.pinata.cloud/ipfs/bafktestcid',
            'duration_ms' => 1234,
            'message' => 'Uploaded.',
            'logged_at' => now(),
        ]);

        BlockchainRegistryLog::create([
            'gpx_version_id' => $version->id,
            'operation' => 'receipt_check',
            'status' => 'confirmed',
            'network' => 'sepolia',
            'contract_address' => '0x' . str_repeat('b', 40),
            'tx_hash' => '0x' . str_repeat('c', 64),
            'registered_by' => '0x' . str_repeat('d', 40),
            'block_number' => 123,
            'gas_used' => 456,
            'message' => 'Confirmed.',
            'logged_at' => now(),
        ]);

        $this->actingAs($this->makeAdminUser())
            ->get(route('admin.paper-outputs.provenance'))
            ->assertOk()
            ->assertSee('Tabel IPFS Audit Log')
            ->assertSee('Tabel Blockchain Audit Log')
            ->assertSee('RECEIPT_CHECK')
            ->assertSee('CONFIRMED');

        $this->actingAs($this->makeAdminUser())
            ->get(route('admin.paper-outputs.provenance.export-ipfs-upload-logs'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->actingAs($this->makeAdminUser())
            ->get(route('admin.paper-outputs.provenance.export-blockchain-registry-logs'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_queues_bulk_provenance_actions_instead_of_running_them_in_the_web_request(): void
    {
        Queue::fake();

        $this->actingAs($this->makeAdminUser())
            ->post(route('admin.bulk-provenance-action'), ['action' => 'verify_active'])
            ->assertRedirect();

        $this->assertDatabaseHas('bulk_provenance_actions', [
            'action' => 'verify_active',
            'status' => 'queued',
        ]);

        Queue::assertPushed(RunBulkProvenanceAction::class);
    }

    private function makeAdminUser(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeRouteWithVersion(array $versionAttributes = []): array
    {
        $path = 'gpx_files/test-' . uniqid() . '.gpx';
        $absolutePath = storage_path('app/public/' . $path);

        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0777, true);
        }

        file_put_contents($absolutePath, '<gpx><trk><name>Test Track</name></trk></gpx>');

        $route = HikingRoute::create([
            'name' => 'Test Route',
            'gpx_file_path' => $path,
            'distance_km' => 10,
            'elevation_gain_m' => 900,
            'naismith_duration_hour' => 4,
            'average_grade_pct' => 9,
        ]);

        $version = GpxVersion::create(array_merge([
            'hiking_route_id' => $route->id,
            'version_number' => 1,
            'gpx_file_path' => $path,
            'file_hash' => hash_file('sha256', $absolutePath),
            'verification_status' => 'verified',
            'ipfs_status' => ($versionAttributes['ipfs_cid'] ?? null) ? 'uploaded_ipfs' : 'pending_ipfs',
            'blockchain_status' => ($versionAttributes['blockchain_tx_hash'] ?? null) ? 'registered_blockchain' : 'pending_blockchain',
            'is_active' => true,
            'verified_at' => now(),
        ], $versionAttributes));

        return [$route, $version, $absolutePath];
    }
}
