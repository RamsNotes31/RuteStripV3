@extends('layouts.app')

@section('title', $route->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <!-- Back Link -->
    <div class="flex items-center justify-between mb-6">
        <a href="{{ route('routes.index') }}" class="inline-flex items-center text-emerald-600 hover:text-emerald-700 group font-medium">
            <svg class="w-5 h-5 mr-2 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Daftar Rute
        </a>
    </div>

    <!-- MAIN GRID 2 COLUMNS FOR DESKTOP -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- ============================== -->
        <!-- LEFT COLUMN: MAIN CONTENT      -->
        <!-- ============================== -->
        <div class="lg:col-span-8 space-y-6">
            
            <!-- 1. HEADER & MAIN STATS -->
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-slate-100">
                <!-- Cover / Header -->
                <div class="bg-mountain-gradient p-8 relative">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <div>
                            <span class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-bold mb-4
                                @if($route->difficulty_level === 'Mudah') bg-green-100 text-green-800
                                @elseif($route->difficulty_level === 'Sedang') bg-yellow-100 text-yellow-800
                                @elseif($route->difficulty_level === 'Sulit') bg-orange-100 text-orange-800
                                @else bg-red-100 text-red-800
                                @endif shadow-sm">
                                {{ $route->difficulty_level }}
                            </span>
                            @if($route->activeGpxVersion)
                            <span class="inline-flex items-center px-4 py-1.5 rounded-full text-sm font-bold mb-4 ml-2 shadow-sm
                                @if($route->activeGpxVersion->verification_status === 'verified') bg-emerald-100 text-emerald-800
                                @elseif($route->activeGpxVersion->verification_status === 'invalid') bg-red-100 text-red-800
                                @else bg-amber-100 text-amber-800
                                @endif">
                                GPX v{{ $route->activeGpxVersion->version_number }}: {{ str_replace('_', ' ', ucfirst($route->activeGpxVersion->verification_status)) }}
                            </span>
                            @endif
                            <h1 class="text-3xl md:text-4xl font-extrabold text-white mb-2 tracking-tight">{{ $route->name }}</h1>
                            <p class="text-emerald-100 font-medium">Diupload pada {{ $route->created_at->format('d M Y, H:i') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-px bg-slate-100">
                    <div class="bg-white p-6 text-center">
                        <div class="text-emerald-600 mb-2 flex justify-center">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                        <p class="text-2xl font-bold text-slate-800">{{ $route->formatted_distance }}</p>
                        <p class="text-sm text-slate-500 font-medium">Total Jarak</p>
                    </div>
                    <div class="bg-white p-6 text-center">
                        <div class="text-teal-600 mb-2 flex justify-center">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <p class="text-2xl font-bold text-slate-800">{{ $route->formatted_duration }}</p>
                        <p class="text-sm text-slate-500 font-medium">Estimasi Waktu</p>
                    </div>
                    <div class="bg-white p-6 text-center">
                        <div class="text-cyan-600 mb-2 flex justify-center">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/></svg>
                        </div>
                        <p class="text-2xl font-bold text-slate-800">{{ number_format($route->elevation_gain_m) }}m</p>
                        <p class="text-sm text-slate-500 font-medium">Elevasi Gain</p>
                    </div>
                    <div class="bg-white p-6 text-center">
                        <div class="text-amber-600 mb-2 flex justify-center">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </div>
                        <p class="text-2xl font-bold text-slate-800">{{ $route->formatted_grade }}</p>
                        <p class="text-sm text-slate-500 font-medium">Grade Rata-rata</p>
                    </div>
                </div>
            </div>

            <!-- 2. ROUTE MAP -->
            @if($route->route_coordinates && count($route->route_coordinates) > 0)
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-slate-100">
                <div class="p-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h3 class="font-bold text-slate-800 flex items-center">
                        <svg class="w-5 h-5 text-emerald-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                        </svg>
                        Peta Rute Pendakian
                    </h3>
                    <span class="text-xs font-medium text-slate-500 bg-white px-2 py-1 rounded-lg border border-slate-200">
                        {{ count($route->route_coordinates) }} titik
                    </span>
                </div>
                <!-- Fixed size, smaller map frame for compact layout -->
                <div id="route-map" class="w-full h-[400px]"></div>
            </div>
            @endif

            <!-- 3. DESKRIPSI & NARRATIVE (2 Cols on desktop) -->
            <div class="grid md:grid-cols-2 gap-6">
                <!-- Info Jalur -->
                @if($route->description)
                <div class="bg-white rounded-3xl shadow-xl border border-slate-100 p-6 flex flex-col h-full">
                    <h3 class="font-bold text-slate-800 mb-3 flex items-center">
                        <svg class="w-5 h-5 text-emerald-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Info Jalur Manual
                    </h3>
                    <p class="text-slate-600 leading-relaxed text-sm flex-1">
                        {{ $route->description }}
                    </p>
                </div>
                @endif

                <!-- AI Description -->
                <div class="bg-slate-50 rounded-3xl shadow-inner border border-slate-200 p-6 flex flex-col h-full">
                    <h3 class="font-bold text-slate-800 mb-3 flex items-center">
                        <svg class="w-5 h-5 text-emerald-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Deskripsi AI (SBERT)
                    </h3>
                    <p class="text-slate-600 leading-relaxed text-sm flex-1">
                        {{ $route->narrative_text ?? 'Belum ada deskripsi komprehensif.' }}
                    </p>
                </div>
            </div>

            <!-- 4. REVIEWS SECTION -->
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-slate-100">
                <div class="p-6 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                    <h3 class="font-bold text-slate-800 flex items-center">
                        <span class="text-2xl mr-2">🌟</span> Ulasan Pengguna
                    </h3>
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-700 text-lg">{{ $route->average_rating }}/5</span>
                        <span class="text-sm text-slate-500">({{ $route->ratings->count() }} ulasan)</span>
                    </div>
                </div>

                <div class="p-6">
                    <!-- User Reviews -->
                    @if($route->comments->count() > 0)
                        <div class="space-y-4 mb-6">
                            @foreach($route->comments->take(3) as $comment)
                            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-semibold text-slate-700 text-sm">{{ $comment->user->name ?? 'Anonim' }}</span>
                                    <span class="text-xs text-slate-500">{{ $comment->created_at->diffForHumans() }}</span>
                                </div>
                                <p class="text-slate-600 text-sm">{{ $comment->content }}</p>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-slate-500 text-center py-4 text-sm">Belum ada ulasan pengguna. Jadilah yang pertama!</p>
                    @endif

                    <!-- Review Form -->
                    @auth
                    <div class="pt-4 border-t border-slate-100">
                        <form action="{{ route('routes.review.store', $route) }}" method="POST" class="space-y-4">
                            @csrf
                            <div class="flex flex-col sm:flex-row gap-4">
                                <div class="flex-shrink-0">
                                    <div class="flex gap-1" x-data="{ rating: 0 }">
                                        @for($i = 1; $i <= 5; $i++)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="rating" value="{{ $i }}" class="sr-only" required>
                                            <svg class="w-6 h-6 text-slate-300 hover:text-yellow-400 transition-colors star-rating" data-value="{{ $i }}" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                            </svg>
                                        </label>
                                        @endfor
                                    </div>
                                </div>
                                <div class="flex-1 flex gap-2">
                                    <input type="text" name="comment" class="w-full px-4 py-2 text-sm border border-slate-200 rounded-xl focus:ring-emerald-500 focus:border-emerald-500" placeholder="Tulis pengalaman singkat...">
                                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-xl whitespace-nowrap">Kirim</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    @endauth
                </div>
            </div>

        </div>


        <!-- ============================== -->
        <!-- RIGHT COLUMN: SIDEBAR          -->
        <!-- ============================== -->
        <div class="lg:col-span-4 space-y-6">
            
            <!-- A. LIKE & ACTION WIDGET -->
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-slate-100 p-6 text-center">
                <div class="flex justify-center items-center gap-2 mb-4">
                    <span class="text-3xl">❤️</span>
                    <span class="font-bold text-3xl text-slate-800">{{ $route->likes_count }}</span>
                    <span class="text-sm text-slate-500 mt-2 font-medium">likes</span>
                </div>
                
                <div class="space-y-3">
                    @auth
                    <form action="{{ route('routes.like.toggle', $route) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full py-3 rounded-2xl font-bold transition-all border-2 {{ $route->isLikedBy(Auth::id()) ? 'bg-rose-50 text-rose-600 border-rose-200 hover:bg-rose-100' : 'bg-white text-slate-700 border-slate-200 hover:border-emerald-200 hover:text-emerald-700 hover:bg-emerald-50' }}">
                            {{ $route->isLikedBy(Auth::id()) ? 'Batal Suka' : 'Berikan Like 👍' }}
                        </button>
                    </form>
                    @if(Auth::user()->isAdmin())
                    <form action="{{ route('routes.destroy', $route) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus rute ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full py-2 bg-red-50 text-red-600 text-sm font-bold rounded-xl mt-2 hover:bg-red-100">
                            🗑️ Hapus Rute
                        </button>
                    </form>
                    @endif
                    @else
                    <a href="{{ route('login') }}" class="block w-full py-3 bg-slate-50 text-slate-700 rounded-2xl font-bold border border-slate-200 hover:bg-slate-100">
                        Login untuk Suka
                    </a>
                    @endauth
                </div>
            </div>

            <!-- B. GPX PROVENANCE -->
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden border border-slate-100 p-6">
                <h3 class="font-bold text-slate-800 mb-4 flex items-center justify-between">
                    <span>GPX Provenance</span>
                    <a href="{{ route('routes.provenance', $route) }}" class="text-xs text-emerald-600 hover:text-emerald-700 font-semibold">Lihat riwayat</a>
                </h3>

                @if($route->activeGpxVersion)
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Versi aktif</span>
                        <span class="font-bold text-slate-800">v{{ $route->activeGpxVersion->version_number }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Status</span>
                        <span class="px-2 py-1 rounded-lg text-xs font-bold
                            @if($route->activeGpxVersion->verification_status === 'verified') bg-emerald-100 text-emerald-700
                            @elseif($route->activeGpxVersion->verification_status === 'invalid') bg-red-100 text-red-700
                            @else bg-amber-100 text-amber-700
                            @endif">
                            {{ str_replace('_', ' ', strtoupper($route->activeGpxVersion->verification_status)) }}
                        </span>
                    </div>
                    <div class="rounded-2xl bg-slate-50 border border-slate-100 p-3 text-xs text-slate-600 space-y-1">
                        <p><span class="font-bold text-slate-700">GPX:</span> {{ $route->activeGpxVersion->verification_status_explanation }}</p>
                        <p><span class="font-bold text-slate-700">IPFS:</span> {{ $route->activeGpxVersion->ipfs_status_explanation }}</p>
                        <p><span class="font-bold text-slate-700">Blockchain:</span> {{ $route->activeGpxVersion->blockchain_status_explanation }}</p>
                    </div>
                    <div>
                        <span class="text-slate-500 block mb-1">SHA-256</span>
                        <code class="block text-xs bg-slate-50 border border-slate-200 rounded-xl p-3 break-all text-slate-700">{{ $route->activeGpxVersion->file_hash }}</code>
                    </div>
                    <a href="{{ route('routes.provenance.download-version', [$route, $route->activeGpxVersion]) }}" class="block w-full text-center py-3 rounded-2xl bg-blue-50 text-blue-700 font-bold hover:bg-blue-100">
                        Download GPX Aktif
                    </a>
                </div>
                @else
                <p class="text-sm text-slate-500">Belum ada metadata provenance untuk GPX ini.</p>
                @endif
            </div>

            <!-- C. SIMILAR ROUTES (SIDEBAR MOVED UP) -->
            @if($similarRoutes->count() > 0)
            <div class="bg-emerald-600 rounded-3xl shadow-xl overflow-hidden text-white relative">
                <div class="absolute inset-x-0 top-0 h-32 bg-white/10 skew-y-6 origin-bottom-left blur-2xl"></div>
                
                <div class="p-5 relative z-10 border-b border-emerald-500/50">
                    <h3 class="font-extrabold text-lg flex items-center">
                        <svg class="w-5 h-5 mr-2 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        Rekomendasi Serupa
                    </h3>
                    <p class="text-[11px] text-emerald-100 mt-1 opacity-90 hidden sm:block">Model Kosinus SBERT</p>
                </div>

                <div class="p-3 space-y-3 relative z-10 bg-slate-50">
                    @foreach($similarRoutes as $similar)
                    <a href="{{ route('routes.show', $similar) }}" class="flex bg-white rounded-2xl p-2.5 shadow-sm hover:shadow-md border border-slate-100 transition-all gap-3 group">
                        <!-- Map Box Thumbnail -->
                        <div class="w-16 h-16 rounded-xl bg-slate-100 overflow-hidden relative flex-shrink-0 shadow-inner">
                            @if($similar->route_coordinates && count($similar->route_coordinates) > 0)
                            <div id="similar-map-{{ $similar->id }}" class="w-full h-full opacity-80 group-hover:opacity-100"></div>
                            @else
                            <div class="w-full h-full flex items-center justify-center text-xl bg-gradient-to-br from-emerald-100 to-teal-100">🏔️</div>
                            @endif
                        </div>
                        
                        <!-- Content -->
                        <div class="flex-1 min-w-0 flex flex-col justify-between py-1">
                            <h4 class="font-bold text-slate-800 text-sm truncate pr-2 group-hover:text-emerald-600 transition-colors">{{ $similar->name }}</h4>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-lg text-xs font-black">{{ $similar->similarity_score }}%</span>
                                <span class="text-[10px] text-slate-400 font-mono hidden sm:inline-block">cos={{ number_format($similar->similarity_score / 100, 3) }}</span>
                            </div>
                        </div>
                    </a>
                    @endforeach
                    
                    <a href="{{ route('routes.similar', $route) }}" class="block w-full text-center py-2.5 mt-2 bg-emerald-100 hover:bg-emerald-200 text-emerald-800 text-xs font-bold rounded-xl transition-colors">
                        Lihat Semua Rekomendasi →
                    </a>
                </div>
            </div>
            @endif

            <!-- C. BASECAMP & PRACTICAL INFO -->
            @if($route->basecamp_name || $route->entry_fee || $route->facilities || $route->tips)
            <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-slate-100 bg-slate-50">
                    <h3 class="font-bold text-slate-800 flex items-center">
                        <svg class="w-5 h-5 text-amber-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        Info Praktis & Tips
                    </h3>
                </div>
                <div class="p-6 space-y-5">
                    @if($route->basecamp_name)
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm">⛺</div>
                        <div>
                            <p class="text-xs text-slate-500 font-medium">Basecamp</p>
                            <p class="font-bold text-slate-800 text-sm">{{ $route->basecamp_name }}</p>
                            @if($route->basecamp_address)
                                <p class="text-xs text-slate-600 mt-0.5">{{ $route->basecamp_address }}</p>
                            @endif
                            @if($route->basecamp_lat && $route->basecamp_lng)
                                <a href="https://www.google.com/maps?q={{ $route->basecamp_lat }},{{ $route->basecamp_lng }}" target="_blank" class="text-[10px] text-emerald-600 hover:underline mt-1 inline-block">🗺️ Buka di Google Maps →</a>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if($route->contact_phone)
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm">📞</div>
                        <div>
                            <p class="text-xs text-slate-500 font-medium">Kontak</p>
                            <p class="font-bold text-slate-800 text-sm">{{ $route->contact_phone }}</p>
                        </div>
                    </div>
                    @endif

                    @if($route->entry_fee)
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-yellow-100 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm">💰</div>
                        <div>
                            <p class="text-xs text-slate-500 font-medium">Tiket Masuk</p>
                            <p class="font-black text-emerald-600 text-lg leading-tight">Rp {{ number_format($route->entry_fee, 0, ',', '.') }}</p>
                        </div>
                    </div>
                    @endif

                    @if($route->facilities)
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm">🚿</div>
                        <div>
                            <p class="text-xs text-slate-500 font-medium">Fasilitas</p>
                            <p class="text-sm text-slate-700">{{ $route->facilities }}</p>
                        </div>
                    </div>
                    @endif

                    @if($route->best_season)
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center flex-shrink-0 mt-0.5 text-sm">🗓️</div>
                        <div>
                            <p class="text-xs text-slate-500 font-medium">Musim Terbaik</p>
                            <p class="font-bold text-slate-800 text-sm">{{ $route->best_season }}</p>
                        </div>
                    </div>
                    @endif

                    @if($route->tips)
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <div class="bg-amber-50 rounded-xl p-4 border border-amber-100">
                            <p class="text-xs font-bold text-amber-800 mb-1 flex items-center"><span class="mr-1">💡</span> Tips Penting</p>
                            <p class="text-xs text-amber-900 leading-relaxed">{{ $route->tips }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif

        </div>
    </div>
</div>

@push('scripts')
@if($similarRoutes->count() > 0)
<script>
document.addEventListener('DOMContentLoaded', function() {
    @foreach($similarRoutes as $similar)
    @if($similar->route_coordinates && count($similar->route_coordinates) > 0)
    (function() {
        var coords = @json($similar->route_coordinates);
        if (coords && coords.length > 0 && document.getElementById('similar-map-{{ $similar->id }}')) {
            var map = L.map('similar-map-{{ $similar->id }}', {
                zoomControl: false, dragging: false, scrollWheelZoom: false, doubleClickZoom: false, touchZoom: false,
                attributionControl: false
            });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18 }).addTo(map);
            var poly = L.polyline(coords, { color: '#10b981', weight: 4, opacity: 0.9 }).addTo(map);
            map.fitBounds(poly.getBounds(), { padding: [2, 2] });
        }
    })();
    @endif
    @endforeach
});
</script>
@endif

@if($route->route_coordinates && count($route->route_coordinates) > 0)
<script>
document.addEventListener('DOMContentLoaded', function() {
    var coords = @json($route->route_coordinates);

    if (coords && coords.length > 0 && document.getElementById('route-map')) {
        var map = L.map('route-map', {
            zoomControl: true,
            scrollWheelZoom: true
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        // Draw route polyline
        var polyline = L.polyline(coords, {
            color: '#10b981',
            weight: 5,
            opacity: 0.9
        }).addTo(map);

        // Add start marker (green)
        L.circleMarker(coords[0], {
            radius: 8,
            fillColor: '#22c55e',
            color: '#fff',
            weight: 2,
            fillOpacity: 1
        }).addTo(map).bindPopup('🏁 Start');

        // Add end marker (red)
        L.circleMarker(coords[coords.length - 1], {
            radius: 8,
            fillColor: '#ef4444',
            color: '#fff',
            weight: 2,
            fillOpacity: 1
        }).addTo(map).bindPopup('🏔️ Finish');

        map.fitBounds(polyline.getBounds(), { padding: [30, 30] });
    }
});
</script>
@endif
@endpush
@endsection
