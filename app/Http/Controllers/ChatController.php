<?php
namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\HikingRoute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class ChatController extends Controller
{
    /**
     * Show the chatbot page
     */
    public function index(Request $request)
    {
        $sessionId = $request->session()->get('chat_session_id');

        if (!$sessionId) {
            $sessionId = Str::uuid()->toString();
            $request->session()->put('chat_session_id', $sessionId);
        }

        // Load existing chat history for this session
        $messages = ChatMessage::forSession($sessionId)->get();

        // Pre-format messages data for JSON (avoids Blade @json parse issues with arrow functions)
        $messagesJson = $messages->map(function ($m) {
            return [
                'role'             => $m->role,
                'content'          => $m->content,
                'retrieved_routes' => $m->retrieved_routes,
                'timing'           => $m->timing_data,
                'time'             => $m->created_at->format('H:i'),
            ];
        })->values();

        return view('chat.index', compact('messages', 'sessionId', 'messagesJson'));
    }

    /**
     * Handle chat message via AJAX — RAG Pipeline
     */
    public function send(Request $request)
    {
        $request->validate([
            'message'    => 'required|string|min:2|max:1000',
            'session_id' => 'required|string|max:64',
        ]);

        $userMessage = $request->input('message');
        $sessionId   = $request->input('session_id');
        $userId      = Auth::id();

        // Save user message
        ChatMessage::create([
            'session_id' => $sessionId,
            'user_id'    => $userId,
            'role'       => 'user',
            'content'    => $userMessage,
        ]);

        try {
            // Get all routes with embeddings
            $routes = Cache::remember('routes_with_embeddings_chat', 300, function () {
                return HikingRoute::whereNotNull('sbert_embedding')->get();
            });

            if ($routes->isEmpty()) {
                $assistantContent = 'Maaf, belum ada data jalur pendakian yang tersedia. Silakan upload file GPX terlebih dahulu untuk menambahkan data jalur.';

                ChatMessage::create([
                    'session_id' => $sessionId,
                    'user_id'    => $userId,
                    'role'       => 'assistant',
                    'content'    => $assistantContent,
                ]);

                return response()->json([
                    'success'  => true,
                    'response' => $assistantContent,
                ]);
            }

            // Prepare routes data for Python
            $routesData = $routes->map(function ($route) {
                return [
                    'id'                     => $route->id,
                    'name'                   => $route->name,
                    'description'            => $route->description,
                    'narrative_text'         => $route->narrative_text,
                    'distance_km'            => $route->distance_km,
                    'elevation_gain_m'       => $route->elevation_gain_m,
                    'naismith_duration_hour' => $route->naismith_duration_hour,
                    'average_grade_pct'      => $route->average_grade_pct,
                    'basecamp_name'          => $route->basecamp_name,
                    'basecamp_address'       => $route->basecamp_address,
                    'basecamp_lat'           => $route->basecamp_lat,
                    'basecamp_lng'           => $route->basecamp_lng,
                    'entry_fee'              => $route->entry_fee,
                    'facilities'             => $route->facilities,
                    'best_season'            => $route->best_season,
                    'tips'                   => $route->tips,
                    'embedding'              => $route->sbert_embedding,
                ];
            })->values()->toArray();

            // Get recent chat history for context
            $chatHistory = ChatMessage::forSession($sessionId)
                ->latest()
                ->take(6)
                ->get()
                ->reverse()
                ->map(fn($msg) => ['role' => $msg->role, 'content' => $msg->content])
                ->values()
                ->toArray();

            // Write temp files
            $dataFile    = tempnam(sys_get_temp_dir(), 'rutestrip_chat_data_');
            $historyFile = tempnam(sys_get_temp_dir(), 'rutestrip_chat_hist_');
            file_put_contents($dataFile, json_encode($routesData));
            file_put_contents($historyFile, json_encode($chatHistory));

            // Get API key, model, and Python path
            $apiKey           = env('GEMINI_API_KEY', '');
            $geminiModel      = env('GEMINI_MODEL', 'gemini-2.0-flash');
            $pythonExecutable = env('PYTHON_PATH', 'python');
            $scriptPath       = base_path('python/rag_chatbot.py');

            // Build command
            $command = [
                $pythonExecutable,
                $scriptPath,
                '--query', $userMessage,
                '--data-file', $dataFile,
                '--api-key', $apiKey,
                '--model', $geminiModel,
                '--top-k', '5',
                '--history-file', $historyFile,
            ];

            $process = new Process($command);
            $process->setEnv([
                'PATH'             => getenv('PATH'),
                'PYTHONIOENCODING' => 'utf-8',
                'HF_HOME'          => storage_path('app/huggingface'),
                'SENTENCE_TRANSFORMERS_HOME' => storage_path('app/huggingface/sentence-transformers'),
                'TRANSFORMERS_CACHE' => storage_path('app/huggingface/transformers'),
                'USERPROFILE'      => getenv('USERPROFILE'),
                'USERNAME'         => getenv('USERNAME') ?: 'default',
                'LOCALAPPDATA'     => getenv('LOCALAPPDATA'),
                'APPDATA'          => getenv('APPDATA'),
                'HOME'             => getenv('USERPROFILE'),
                'SYSTEMROOT'       => getenv('SYSTEMROOT') ?: 'C:\\Windows',
                'TEMP'             => getenv('TEMP') ?: sys_get_temp_dir(),
                'TMP'              => getenv('TMP') ?: sys_get_temp_dir(),
            ]);
            $process->setWorkingDirectory(base_path('python'));
            $process->setTimeout(120);

            $process->mustRun();
            $output = $process->getOutput();
            $result = json_decode($output, true);

            // Clean up temp files
            @unlink($dataFile);
            @unlink($historyFile);

            if (json_last_error() !== JSON_ERROR_NONE || !isset($result['success'])) {
                throw new \Exception('Invalid response from RAG pipeline');
            }

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Unknown RAG error');
            }

            // Save assistant's response
            $assistantMessage = ChatMessage::create([
                'session_id'      => $sessionId,
                'user_id'         => $userId,
                'role'            => 'assistant',
                'content'         => $result['response'],
                'retrieved_routes' => $result['retrieved_routes'] ?? null,
                'timing_data'     => $result['timing'] ?? null,
            ]);

            return response()->json([
                'success'          => true,
                'response'         => $result['response'],
                'retrieved_routes' => $result['retrieved_routes'] ?? [],
                'timing'           => $result['timing'] ?? null,
                'metadata'         => $result['metadata'] ?? null,
            ]);

        } catch (\Exception $e) {
            Log::error('Chat RAG error: ' . $e->getMessage());

            $errorMsg = 'Maaf, terjadi kesalahan saat memproses pertanyaan Anda. Silakan coba lagi.';

            ChatMessage::create([
                'session_id' => $sessionId,
                'user_id'    => $userId,
                'role'       => 'assistant',
                'content'    => $errorMsg,
            ]);

            return response()->json([
                'success'  => false,
                'response' => $errorMsg,
                'error'    => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Start a new chat session
     */
    public function newSession(Request $request)
    {
        $sessionId = Str::uuid()->toString();
        $request->session()->put('chat_session_id', $sessionId);

        return redirect()->route('chat.index');
    }

    /**
     * Get chat history for a session (API)
     */
    public function history(Request $request)
    {
        $sessionId = $request->input('session_id', $request->session()->get('chat_session_id'));

        $messages = ChatMessage::forSession($sessionId)
            ->get()
            ->map(fn($msg) => [
                'id'               => $msg->id,
                'role'             => $msg->role,
                'content'          => $msg->content,
                'retrieved_routes' => $msg->retrieved_routes,
                'timing_data'     => $msg->timing_data,
                'created_at'       => $msg->created_at->format('H:i'),
            ]);

        return response()->json(['messages' => $messages]);
    }
}
