<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>RuteStrip AI - Asisten Pendakian</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                }
            }
        }
    </script>

    <style>
        * { font-family: 'Inter', system-ui, sans-serif; }
        body { margin: 0; overflow: hidden; }

        .chat-scroll::-webkit-scrollbar { width: 6px; }
        .chat-scroll::-webkit-scrollbar-track { background: transparent; }
        .chat-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
        .chat-scroll::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .msg-appear { animation: fadeInUp 0.3s ease-out; }
        
        /* Caching & Print Optimizations */
        @media print {
            body { height: auto !important; overflow: visible !important; background: white !important; }
            .print-hide, header, footer, .pipeline-info, [x-show="showPipeline"] { display: none !important; }
            main { height: auto !important; overflow: visible !important; border:none !important; padding-bottom: 2rem !important; }
            .chat-scroll { overflow: visible !important; }
            .msg-appear { animation: none !important; opacity: 1 !important; transform: none !important; page-break-inside: avoid; }
        }
    </style>
</head>
<body class="h-screen flex flex-col bg-slate-50" x-data="chatApp()" x-init="init()">

    {{-- Top Bar --}}
    <header class="flex-shrink-0 bg-white border-b border-slate-200 px-4 sm:px-6 h-14 flex items-center justify-between z-10">
        <div class="flex items-center space-x-3">
            <a href="{{ route('search.index') }}" class="flex items-center space-x-2 text-slate-500 hover:text-emerald-600 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span class="text-sm hidden sm:inline">Kembali</span>
            </a>
            <div class="w-px h-6 bg-slate-200"></div>
            <div class="flex items-center space-x-2.5">
                <div class="relative">
                    <img src="/images/logo.svg" alt="RuteStrip" class="w-9 h-9 rounded-xl shadow-sm">
                    <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-400 border-2 border-white rounded-full"></div>
                </div>
                <div>
                    <h1 class="text-sm font-bold text-slate-800 leading-tight">RuteStrip AI</h1>
                    <p class="text-[11px] text-slate-400 leading-tight">Asisten Pendakian</p>
                </div>
            </div>
        </div>
        <div class="flex items-center space-x-2">
            <button @click="showPipeline = !showPipeline"
                    class="hidden sm:flex items-center space-x-1.5 px-2.5 py-1.5 text-slate-500 hover:bg-slate-100 rounded-lg text-xs transition-colors"
                    :class="showPipeline ? 'bg-slate-100' : ''">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <span>Pipeline</span>
            </button>
            <button onclick="window.print()" class="hidden sm:flex items-center space-x-1.5 px-2.5 py-1.5 text-slate-500 hover:bg-slate-100 rounded-lg text-xs transition-colors print-hide">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span>Export PDF</span>
            </button>
            <a href="{{ route('chat.new') }}"
               class="flex items-center space-x-1.5 px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-xs font-medium hover:bg-emerald-700 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Chat Baru</span>
            </a>
        </div>
    </header>

    {{-- Pipeline Info (collapsible) --}}
    <div x-show="showPipeline" x-transition x-cloak
         class="flex-shrink-0 bg-gradient-to-r from-slate-50 via-emerald-50/30 to-slate-50 border-b border-slate-200 px-6 py-3">
        <div class="flex flex-wrap items-center justify-center gap-1.5 text-[11px]">
            <span class="px-2 py-1 bg-white rounded border border-slate-200 text-slate-600 font-medium">Query → Preprocessing</span>
            <span class="text-slate-300">→</span>
            <span class="px-2 py-1 bg-white rounded border border-emerald-200 text-emerald-700 font-medium">SBERT 384d</span>
            <span class="text-slate-300">→</span>
            <span class="px-2 py-1 bg-white rounded border border-amber-200 text-amber-700 font-medium">Cosine Similarity</span>
            <span class="text-slate-300">→</span>
            <span class="px-2 py-1 bg-white rounded border border-purple-200 text-purple-700 font-medium">Context Augmentation</span>
            <span class="text-slate-300">→</span>
            <span class="px-2 py-1 bg-white rounded border border-pink-200 text-pink-700 font-medium">Gemini Generation</span>
        </div>
    </div>

    {{-- Chat Messages Area (takes all remaining space) --}}
    <main class="flex-1 overflow-y-auto chat-scroll" x-ref="chatMessages">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 py-6 space-y-5">

            {{-- Welcome (empty state) --}}
            <template x-if="messages.length === 0">
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    {{-- Mountain icon --}}
                    <img src="/images/logo.svg" alt="RuteStrip" class="w-20 h-20 rounded-2xl shadow-md mb-5">
                    <h2 class="text-lg font-bold text-slate-800 mb-1">Tanya seputar pendakian 🏔️</h2>
                    <p class="text-sm text-slate-500 mb-8 max-w-sm">Tanyakan jalur, estimasi waktu, tingkat kesulitan, atau rekomendasi pendakian</p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 w-full max-w-md">
                        <button @click="sendSuggestion('Rekomendasikan jalur pendakian untuk pemula')"
                                class="group text-left p-3.5 bg-white rounded-xl border border-slate-200 hover:border-emerald-300 hover:shadow-md transition-all duration-200">
                            <div class="flex items-center space-x-3">
                                <span class="text-lg">🥾</span>
                                <div>
                                    <p class="text-sm font-medium text-slate-700 group-hover:text-emerald-700">Jalur untuk pemula</p>
                                    <p class="text-[11px] text-slate-400">Temukan jalur yang mudah</p>
                                </div>
                            </div>
                        </button>
                        <button @click="sendSuggestion('Jalur pendakian mana yang paling menantang?')"
                                class="group text-left p-3.5 bg-white rounded-xl border border-slate-200 hover:border-amber-300 hover:shadow-md transition-all duration-200">
                            <div class="flex items-center space-x-3">
                                <span class="text-lg">⛰️</span>
                                <div>
                                    <p class="text-sm font-medium text-slate-700 group-hover:text-amber-700">Jalur menantang</p>
                                    <p class="text-[11px] text-slate-400">Tantangan untuk expert</p>
                                </div>
                            </div>
                        </button>
                        <button @click="sendSuggestion('Berapa estimasi waktu pendakian Gunung Merbabu via Selo?')"
                                class="group text-left p-3.5 bg-white rounded-xl border border-slate-200 hover:border-blue-300 hover:shadow-md transition-all duration-200">
                            <div class="flex items-center space-x-3">
                                <span class="text-lg">⏱️</span>
                                <div>
                                    <p class="text-sm font-medium text-slate-700 group-hover:text-blue-700">Estimasi waktu</p>
                                    <p class="text-[11px] text-slate-400">Info durasi pendakian</p>
                                </div>
                            </div>
                        </button>
                        <button @click="sendSuggestion('Jalur pendakian dengan pemandangan sabana dan sunrise')"
                                class="group text-left p-3.5 bg-white rounded-xl border border-slate-200 hover:border-purple-300 hover:shadow-md transition-all duration-200">
                            <div class="flex items-center space-x-3">
                                <span class="text-lg">🌅</span>
                                <div>
                                    <p class="text-sm font-medium text-slate-700 group-hover:text-purple-700">Sabana & Sunrise</p>
                                    <p class="text-[11px] text-slate-400">Pemandangan terbaik</p>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            </template>

            {{-- Messages --}}
            <template x-for="(msg, index) in messages" :key="index">
                <div class="msg-appear" :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div :class="msg.role === 'user' ? 'max-w-[80%]' : 'max-w-[85%]'" class="flex items-start space-x-2.5">

                        {{-- Assistant avatar: mountain/compass icon --}}
                        <template x-if="msg.role === 'assistant'">
                            <img src="/images/logo.svg" alt="AI" class="flex-shrink-0 w-8 h-8 rounded-lg shadow-sm mt-0.5">
                        </template>

                        <div class="flex flex-col space-y-1 min-w-0">
                            <div :class="msg.role === 'user'
                                ? 'bg-emerald-600 text-white rounded-2xl rounded-tr-sm'
                                : 'bg-white text-slate-700 rounded-2xl rounded-tl-sm border border-slate-200'"
                                 class="px-4 py-3 text-sm leading-relaxed shadow-sm">
                                <div x-html="formatMessage(msg.content)" class="prose prose-sm max-w-none break-words"
                                     :class="msg.role === 'user' ? 'prose-invert' : 'prose-slate'"></div>
                            </div>

                            {{-- Retrieved routes --}}
                            <template x-if="msg.role === 'assistant' && msg.retrieved_routes && msg.retrieved_routes.length > 0">
                                <div class="flex flex-wrap items-center gap-1.5 ml-0.5">
                                    <template x-for="route in msg.retrieved_routes" :key="route.id">
                                        <a :href="'/routes/' + route.id"
                                           class="inline-flex items-center space-x-1 px-2 py-0.5 bg-emerald-50 text-emerald-700 rounded text-[11px] hover:bg-emerald-100 transition-colors border border-emerald-100">
                                            <span>📍</span>
                                            <span x-text="route.name" class="truncate max-w-[100px]"></span>
                                            <span class="text-emerald-400" x-text="(route.similarity_score * 100).toFixed(0) + '%'"></span>
                                        </a>
                                    </template>
                                </div>
                            </template>

                            {{-- Timing --}}
                            <template x-if="msg.role === 'assistant' && msg.timing">
                                <div class="flex items-center space-x-2 text-[10px] text-slate-400 ml-0.5">
                                    <span>⏱ <span x-text="msg.timing.retrieval_ms + 'ms'"></span> retrieval</span>
                                    <span>•</span>
                                    <span><span x-text="msg.timing.generation_ms + 'ms'"></span> generation</span>
                                </div>
                            </template>

                            <div class="text-[10px] text-slate-400 ml-0.5" :class="msg.role === 'user' ? 'text-right' : ''">
                                <span x-text="msg.time || ''"></span>
                            </div>
                        </div>

                        {{-- User avatar: hiker icon --}}
                        <template x-if="msg.role === 'user'">
                            <div class="flex-shrink-0 w-8 h-8 bg-gradient-to-br from-slate-600 to-slate-700 rounded-lg flex items-center justify-center mt-0.5">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <circle cx="12" cy="4" r="2"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v4m-3 2l3-2 3 2m-6 0l-2 8h2l1-4m4 4h2l-2-8m-1 0l1 4"/>
                                </svg>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Typing indicator --}}
            <template x-if="isLoading">
                <div class="flex justify-start msg-appear">
                    <div class="flex items-start space-x-2.5">
                        <img src="/images/logo.svg" alt="AI" class="flex-shrink-0 w-8 h-8 rounded-lg shadow-sm">
                        <div class="bg-white rounded-2xl rounded-tl-sm border border-slate-200 px-4 py-3 shadow-sm">
                            <div class="flex items-center space-x-2.5">
                                <div class="flex space-x-1">
                                    <div class="w-2 h-2 bg-emerald-400 rounded-full animate-bounce" style="animation-delay: 0ms;"></div>
                                    <div class="w-2 h-2 bg-emerald-400 rounded-full animate-bounce" style="animation-delay: 150ms;"></div>
                                    <div class="w-2 h-2 bg-emerald-400 rounded-full animate-bounce" style="animation-delay: 300ms;"></div>
                                </div>
                                <span class="text-xs text-slate-400" x-text="loadingText"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

        </div>
    </main>

    {{-- Input Bar (fixed at bottom) --}}
    <footer class="flex-shrink-0 bg-white border-t border-slate-200 px-4 sm:px-6 py-3 print-hide">
        <form @submit.prevent="sendMessage()" class="max-w-3xl mx-auto flex items-end space-x-2.5">
            <div class="flex-1 relative flex items-end bg-slate-50 border border-slate-200 rounded-xl focus-within:ring-2 focus-within:ring-emerald-500/20 focus-within:border-emerald-400 transition-all">
                
                {{-- Voice Input Button --}}
                <button type="button" @click="toggleSpeech()" 
                        :class="isListening ? 'text-red-500 animate-pulse bg-red-50' : 'text-slate-400 hover:text-emerald-500'" 
                        class="flex-shrink-0 w-11 h-11 rounded-bl-xl flex items-center justify-center transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                    </svg>
                </button>

                <textarea x-model="inputMessage"
                          @keydown.enter.prevent="if(!$event.shiftKey) sendMessage()"
                          :disabled="isLoading"
                          rows="1"
                          x-ref="chatInput"
                          @input="autoResize($event)"
                          placeholder="Ketik atau gunakan suara..."
                          class="w-full px-2 py-3 bg-transparent text-sm text-slate-700 placeholder-slate-400 focus:outline-none resize-none transition-all disabled:opacity-50 max-h-28"
                          style="min-height: 44px;"></textarea>
            </div>
            <button type="submit"
                    :disabled="isLoading || !inputMessage.trim()"
                    class="flex-shrink-0 w-11 h-11 bg-emerald-600 text-white rounded-xl flex items-center justify-center hover:bg-emerald-700 transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                <template x-if="!isLoading">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                </template>
                <template x-if="isLoading">
                    <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </template>
            </button>
        </form>
        <div class="max-w-3xl mx-auto flex items-center justify-between mt-1.5 px-1">
            <p class="text-[10px] text-slate-400">
                Powered by <span class="font-medium text-emerald-600">SBERT</span> + <span class="font-medium text-blue-600">Gemini</span>
            </p>
            <p class="text-[10px] text-slate-400">Shift+Enter baris baru</p>
        </div>
    </footer>

    <script>
    function chatApp() {
        return {
            messages: @json($messagesJson),
            inputMessage: '',
            isLoading: false,
            loadingText: 'Menganalisis pertanyaan...',
            sessionId: '{{ $sessionId }}',
            showPipeline: false,
            isListening: false,
            recognition: null,

            init() {
                this.$nextTick(() => this.scrollToBottom());
                
                // Initialize Web Speech API for voice input
                if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
                    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                    this.recognition = new SpeechRecognition();
                    this.recognition.lang = 'id-ID';
                    this.recognition.interimResults = true;
                    
                    this.recognition.onresult = (event) => {
                        let finalTranscript = '';
                        for (let i = event.resultIndex; i < event.results.length; ++i) {
                            if (event.results[i].isFinal) {
                                finalTranscript += event.results[i][0].transcript;
                            }
                        }
                        if (finalTranscript) {
                            // Append transcript
                            this.inputMessage = (this.inputMessage + ' ' + finalTranscript).trim();
                            this.autoResize({target: this.$refs.chatInput});
                        }
                    };
                    
                    this.recognition.onstart = () => { this.isListening = true; };
                    this.recognition.onend = () => { this.isListening = false; };
                    this.recognition.onerror = () => { this.isListening = false; };
                }
            },

            toggleSpeech() {
                if (!this.recognition) {
                    alert('Maaf, browser Anda (atau versi gawai ini) tidak mendukung fitur Voice Input. Coba gunakan Chrome/Edge terbaru.');
                    return;
                }
                
                if (this.isListening) {
                    this.recognition.stop();
                } else {
                    this.recognition.start();
                }
            },

            async sendMessage() {
                const message = this.inputMessage.trim();
                if (!message || this.isLoading) return;

                const now = new Date();
                const timeStr = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
                this.messages.push({ role: 'user', content: message, time: timeStr });
                this.inputMessage = '';
                this.isLoading = true;
                this.resetTextarea();
                this.scrollToBottom();

                const stages = [
                    'Preprocessing query...',
                    'Generating SBERT embedding...',
                    'Mencari jalur relevan...',
                    'Menyusun konteks...',
                    'Generating response...',
                ];
                let stageIdx = 0;
                const stageInterval = setInterval(() => {
                    stageIdx = (stageIdx + 1) % stages.length;
                    this.loadingText = stages[stageIdx];
                }, 2000);

                try {
                    const response = await fetch('{{ route("chat.send") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ message: message, session_id: this.sessionId }),
                    });
                    const data = await response.json();
                    this.messages.push({
                        role: 'assistant',
                        content: data.response || 'Maaf, terjadi kesalahan.',
                        retrieved_routes: data.retrieved_routes || [],
                        timing: data.timing || null,
                        time: new Date().getHours().toString().padStart(2, '0') + ':' + new Date().getMinutes().toString().padStart(2, '0'),
                    });
                } catch (error) {
                    this.messages.push({
                        role: 'assistant',
                        content: 'Maaf, terjadi kesalahan koneksi. Silakan coba lagi.',
                        time: new Date().getHours().toString().padStart(2, '0') + ':' + new Date().getMinutes().toString().padStart(2, '0'),
                    });
                } finally {
                    clearInterval(stageInterval);
                    this.isLoading = false;
                    this.loadingText = 'Menganalisis pertanyaan...';
                    this.scrollToBottom();
                }
            },

            sendSuggestion(text) { this.inputMessage = text; this.sendMessage(); },

            scrollToBottom() {
                this.$nextTick(() => {
                    const el = this.$refs.chatMessages;
                    if (el) el.scrollTo({ top: el.scrollHeight, behavior: 'smooth' });
                });
            },

            autoResize(event) {
                const el = event.target;
                el.style.height = 'auto';
                el.style.height = Math.min(el.scrollHeight, 112) + 'px';
            },

            resetTextarea() {
                this.$nextTick(() => {
                    if (this.$refs.chatInput) this.$refs.chatInput.style.height = '44px';
                });
            },

            formatMessage(content) {
                if (!content) return '';
                let html = content.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');
                html = html.replace(/^(\d+)\.\s+(.+)$/gm, '<li class="ml-4 list-decimal">$2</li>');
                html = html.replace(/^[-•]\s+(.+)$/gm, '<li class="ml-4 list-disc">$1</li>');
                html = html.replace(/((?:<li[^>]*>.*?<\/li>\s*)+)/g, '<ul class="my-1.5 space-y-0.5">$1</ul>');
                html = html.replace(/^### (.+)$/gm, '<h4 class="font-semibold mt-2 mb-0.5">$1</h4>');
                html = html.replace(/^## (.+)$/gm, '<h3 class="font-bold mt-2 mb-0.5">$1</h3>');
                html = html.replace(/\n\n/g, '</p><p class="mt-1.5">');
                html = html.replace(/\n/g, '<br>');
                return '<p>' + html + '</p>';
            }
        }
    }
    </script>

</body>
</html>
