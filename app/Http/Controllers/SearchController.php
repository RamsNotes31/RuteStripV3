<?php
namespace App\Http\Controllers;

use App\Models\HikingRoute;
use App\Models\SearchHistory;
use App\Services\PythonProcessorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SearchController extends Controller
{
    private PythonProcessorService $pythonService;

    public function __construct(PythonProcessorService $pythonService)
    {
        $this->pythonService = $pythonService;
    }

    /**
     * Show search form
     */
    public function index()
    {
        return view('search.index');
    }

    /**
     * Process search and show results - OPTIMIZED with PHP-based cosine similarity
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:3|max:500',
        ]);

        $query     = $request->input('query');
        $startTime = microtime(true);

        // Get all routes with embeddings (cached for 5 minutes)
        $routes = Cache::remember('routes_with_embeddings', 300, function () {
            return HikingRoute::whereNotNull('sbert_embedding')->with('activeGpxVersion')->get();
        });

        if ($routes->isEmpty()) {
            return view('search.results', [
                'query'   => $query,
                'results' => collect([]),
                'message' => 'Belum ada data jalur pendakian. Silakan upload file GPX terlebih dahulu.'
            ]);
        }

        try {
            // Get query embedding from Python (one-time call)
            $queryEmbedding = $this->pythonService->getQueryEmbedding($query);

            if (! is_array($queryEmbedding) || empty($queryEmbedding)) {
                throw new \RuntimeException('Embedding query kosong atau tidak valid.');
            }

            // Calculate cosine similarity in PHP (FAST!)
            $results = $routes->filter(fn($route) => is_array($route->sbert_embedding) && ! empty($route->sbert_embedding))
                ->map(function ($route) use ($queryEmbedding) {
                $similarity              = $this->cosineSimilarity($queryEmbedding, $route->sbert_embedding);
                $route->similarity_score = round($similarity * 100, 1);
                $route->cosine_value     = round($similarity, 4);
                return $route;
            })
                ->filter(fn($r) => $r->similarity_score > 0) // Filter positif saja
                ->sortByDesc('similarity_score')
                ->take(10);

            $searchTime = round((microtime(true) - $startTime) * 1000, 2);

            // Save search history for logged-in users
            if (Auth::check()) {
                SearchHistory::create([
                    'user_id'       => Auth::id(),
                    'query'         => $query,
                    'results_count' => $results->count(),
                ]);
            }

            return view('search.results', [
                'query'      => $query,
                'results'    => $results,
                'searchTime' => $searchTime,
            ]);

        } catch (\Throwable $e) {
            return back()->withErrors(['query' => 'Gagal melakukan pencarian: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Calculate cosine similarity between two vectors (PHP implementation - FAST)
     */
    private function cosineSimilarity(array $a, array $b): float
    {
        $dotProduct = 0.0;
        $normA      = 0.0;
        $normB      = 0.0;

        $length = min(count($a), count($b));

        for ($i = 0; $i < $length; $i++) {
            $dotProduct += $a[$i] * $b[$i];
            $normA += $a[$i] * $a[$i];
            $normB += $b[$i] * $b[$i];
        }

        $normA = sqrt($normA);
        $normB = sqrt($normB);

        if ($normA * $normB == 0) {
            return 0.0;
        }

        return $dotProduct / ($normA * $normB);
    }
}
