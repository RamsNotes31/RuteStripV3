@extends('layouts.app')

@section('title', 'Hasil Pencarian')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Search Header -->
    <div class="mb-8">
        <a href="{{ route('search.index') }}" class="inline-flex items-center text-emerald-600 hover:text-emerald-700 mb-4 group">
            <svg class="w-5 h-5 mr-2 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Pencarian
        </a>

        <h1 class="text-3xl font-bold text-slate-800 mb-2">Hasil Rekomendasi</h1>
        <p class="text-slate-600">
            Pencarian: <span class="font-semibold text-emerald-600">"{{ $query }}"</span>
            @if(isset($searchTime))
            <span class="ml-2 text-sm bg-slate-100 px-2 py-1 rounded text-slate-500">⚡ {{ $searchTime }} ms</span>
            @endif
        </p>
    </div>

    @if(isset($message))
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 flex items-center space-x-4">
        <div class="flex-shrink-0">
            <svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <div>
            <h3 class="font-semibold text-amber-800">Tidak Ada Data</h3>
            <p class="text-amber-700">{{ $message }}</p>
        </div>
    </div>
    @elseif($results->isEmpty())
    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-12 text-center">
        <svg class="w-16 h-16 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <h3 class="text-xl font-semibold text-slate-600 mb-2">Tidak Ditemukan</h3>
        <p class="text-slate-500">Tidak ada jalur yang cocok dengan pencarian Anda.</p>
    </div>
    @else
    <!-- Results Count -->
    <div class="mb-6">
        <p class="text-slate-500">Ditemukan <span class="font-bold text-slate-700">{{ $results->count() }}</span> jalur yang cocok</p>
    </div>

    <!-- Cosine Similarity Formula Explanation -->
    <div class="mb-8 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-2xl p-6 border border-indigo-100">
        <div class="flex items-start space-x-4">
            <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-indigo-800 mb-2">Metode: Cosine Similarity</h3>
                <p class="text-sm text-slate-600 mb-3">
                    Tingkat kemiripan dihitung menggunakan rumus Cosine Similarity antara vektor query
                    <span class="font-mono bg-white px-1 rounded">Q</span> dan vektor jalur
                    <span class="font-mono bg-white px-1 rounded">D</span>:
                </p>
                <div class="bg-white rounded-xl p-4 mb-3 text-center overflow-x-auto">
                    <code class="text-lg font-mono text-indigo-700">
                        Sim(Q, D) = (Q · D) / (||Q|| × ||D||)
                    </code>
                </div>
                <div class="grid sm:grid-cols-3 gap-3 text-xs">
                    <div class="bg-white/60 rounded-lg p-2">
                        <span class="font-semibold text-indigo-700">Q · D</span>
                        <span class="text-slate-500"> = Dot product vektor</span>
                    </div>
                    <div class="bg-white/60 rounded-lg p-2">
                        <span class="font-semibold text-indigo-700">||Q||</span>
                        <span class="text-slate-500"> = Magnitude vektor query</span>
                    </div>
                    <div class="bg-white/60 rounded-lg p-2">
                        <span class="font-semibold text-indigo-700">||D||</span>
                        <span class="text-slate-500"> = Magnitude vektor jalur</span>
                    </div>
                </div>
                <p class="text-xs text-slate-500 mt-3">
                    <strong>Model:</strong> SBERT (paraphrase-multilingual-MiniLM-L12-v2) menghasilkan vektor 384 dimensi
                </p>
            </div>
        </div>
    </div>

    <!-- Results Grid -->
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($results as $index => $route)
        <div class="mountain-card glass-card rounded-2xl overflow-hidden shadow-lg group">
            <!-- Card Header with Score -->
            <div class="relative bg-mountain-gradient p-6">
                <!-- Match Score Badge -->
                <div class="absolute top-4 right-4">
                    <div class="score-badge px-3 py-1 rounded-full text-white text-sm font-bold shadow-lg pulse-glow">
                        {{ $route->similarity_score }}% cocok
                    </div>
                </div>

                <!-- Rank Badge -->
                <div class="absolute top-4 left-4">
                    <div class="w-8 h-8 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center text-white font-bold text-sm">
                        #{{ $index + 1 }}
                    </div>
                </div>

                <div class="pt-8">
                    <h3 class="text-xl font-bold text-white mb-2 line-clamp-2">{{ $route->name }}</h3>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                        @if($route->difficulty_level === 'Mudah') bg-green-100 text-green-800
                        @elseif($route->difficulty_level === 'Sedang') bg-yellow-100 text-yellow-800
                        @elseif($route->difficulty_level === 'Sulit') bg-orange-100 text-orange-800
                        @else bg-red-100 text-red-800
                        @endif">
                        {{ $route->difficulty_level }}
                    </span>
                    @if($route->activeGpxVersion)
                    <span class="inline-flex items-center mt-2 px-3 py-1 rounded-full text-xs font-bold
                        @if($route->activeGpxVersion->verification_status === 'verified') bg-emerald-100 text-emerald-800
                        @elseif($route->activeGpxVersion->verification_status === 'invalid') bg-red-100 text-red-800
                        @else bg-amber-100 text-amber-800
                        @endif">
                        GPX {{ strtoupper($route->activeGpxVersion->verification_status) }}
                    </span>
                    @endif
                </div>
            </div>

            <!-- Card Body with Stats -->
            <div class="p-6">
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <!-- Distance -->
                    <div class="bg-slate-50 rounded-xl p-3 text-center">
                        <div class="flex items-center justify-center mb-1">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                        <p class="text-lg font-bold text-slate-800">{{ $route->formatted_distance }}</p>
                        <p class="text-xs text-slate-500">Jarak</p>
                    </div>

                    <!-- Duration -->
                    <div class="bg-slate-50 rounded-xl p-3 text-center">
                        <div class="flex items-center justify-center mb-1">
                            <svg class="w-4 h-4 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-lg font-bold text-slate-800">{{ $route->formatted_duration }}</p>
                        <p class="text-xs text-slate-500">Waktu</p>
                    </div>

                    <!-- Elevation -->
                    <div class="bg-slate-50 rounded-xl p-3 text-center">
                        <div class="flex items-center justify-center mb-1">
                            <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"/>
                            </svg>
                        </div>
                        <p class="text-lg font-bold text-slate-800">{{ number_format($route->elevation_gain_m) }}m</p>
                        <p class="text-xs text-slate-500">Elevasi</p>
                    </div>

                    <!-- Grade -->
                    <div class="bg-slate-50 rounded-xl p-3 text-center">
                        <div class="flex items-center justify-center mb-1">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                        <p class="text-lg font-bold text-slate-800">{{ $route->formatted_grade }}</p>
                        <p class="text-xs text-slate-500">Grade</p>
                    </div>
                </div>

                <!-- SBERT Narrative Description -->
                @if($route->narrative_text)
                <div class="mb-4 p-4 bg-gradient-to-r from-purple-50 to-indigo-50 rounded-xl border border-purple-100">
                    <div class="flex items-start space-x-2">
                        <svg class="w-5 h-5 text-purple-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <div>
                            <p class="text-xs font-semibold text-purple-700 mb-1">Deskripsi AI (SBERT)</p>
                            <p class="text-sm text-slate-600 leading-relaxed">{{ Str::limit($route->narrative_text, 150) }}</p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Cosine Similarity Score -->
                <div class="mb-4 p-3 bg-gradient-to-r from-emerald-50 to-teal-50 rounded-xl border border-emerald-100">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            <span class="text-sm font-medium text-emerald-700">Cosine Similarity</span>
                        </div>
                        <div class="text-right">
                            <span class="text-lg font-bold text-emerald-600">{{ number_format($route->similarity_score / 100, 4) }}</span>
                            <span class="text-xs text-emerald-500 ml-1">({{ $route->similarity_score }}%)</span>
                        </div>
                    </div>
                    <!-- Similarity Bar -->
                    <div class="mt-2 h-2 bg-emerald-100 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-emerald-400 to-teal-500 rounded-full transition-all duration-500"
                             style="width: {{ min($route->similarity_score, 100) }}%"></div>
                    </div>
                </div>

                <!-- View Details Button -->
                <a href="{{ route('routes.show', $route) }}"
                   class="block w-full py-3 text-center bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-medium rounded-xl transition-colors">
                    Lihat Detail
                </a>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- New Search -->
    <div class="mt-12 bg-white rounded-2xl p-8 shadow-lg border border-slate-100">
        <h3 class="text-lg font-semibold text-slate-800 mb-4">Cari Lagi</h3>
        <form action="{{ route('search.submit') }}" method="POST" class="flex flex-col sm:flex-row gap-4">
            @csrf
            <input type="text"
                   name="query"
                   placeholder="Masukkan deskripsi jalur yang diinginkan..."
                   class="flex-1 px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none"
                   required
                   minlength="3">
            <button type="submit"
                    class="px-6 py-3 bg-mountain-gradient text-white font-medium rounded-xl shadow-lg hover:shadow-xl transition-all">
                Cari
            </button>
        </form>
    </div>
</div>
@endsection
