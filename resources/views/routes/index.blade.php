@extends('layouts.app')

@section('title', 'Semua Rute Pendakian')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-800 mb-2">Semua Rute Pendakian</h1>
            <p class="text-slate-600">Database jalur pendakian yang telah diupload</p>
        </div>
        <a href="{{ route('routes.create') }}"
           class="mt-4 md:mt-0 inline-flex items-center px-6 py-3 bg-mountain-gradient text-white font-medium rounded-xl shadow-lg hover:shadow-xl transition-all hover:scale-105">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Upload GPX Baru
        </a>
    </div>

    @php
        $filterOptions = [
            '' => 'Semua Rute',
            'verified' => 'Verified',
            'missing_ipfs' => 'Belum IPFS',
            'missing_blockchain' => 'Belum Blockchain',
            'invalid' => 'Invalid',
        ];
    @endphp
    <div class="bg-white rounded-2xl shadow border border-slate-100 p-4 mb-8">
        <p class="text-sm font-bold text-slate-700 mb-3">Filter Provenance</p>
        <div class="flex flex-wrap gap-2">
            @foreach($filterOptions as $value => $label)
            <a href="{{ route('routes.index', $value ? ['provenance_filter' => $value] : []) }}"
               class="px-4 py-2 rounded-xl text-sm font-bold transition-colors {{ ($filter ?? '') === $value ? 'bg-emerald-700 text-white' : 'bg-slate-50 text-slate-700 hover:bg-emerald-50 hover:text-emerald-700' }}">
                {{ $label }}
            </a>
            @endforeach
        </div>
    </div>

    @if($routes->isEmpty())
    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-12 text-center">
        <svg class="w-20 h-20 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
        </svg>
        <h3 class="text-xl font-semibold text-slate-600 mb-2">Belum Ada Rute</h3>
        <p class="text-slate-500 mb-6">Mulai dengan mengupload file GPX pertama Anda.</p>
        <a href="{{ route('routes.create') }}"
           class="inline-flex items-center px-6 py-3 bg-mountain-gradient text-white font-medium rounded-xl shadow-lg hover:shadow-xl transition-all">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Upload GPX Sekarang
        </a>
    </div>
    @else
    <!-- Routes Grid -->
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        @foreach($routes as $route)
        <div class="mountain-card bg-white rounded-2xl overflow-hidden shadow-lg border border-slate-100 group">
            <!-- Card Map/Image -->
            <div class="relative h-40 overflow-hidden">
                @if($route->route_coordinates && count($route->route_coordinates) > 0)
                    <!-- Leaflet Map -->
                    <div id="map-{{ $route->id }}" class="w-full h-full z-0"></div>
                @else
                    <!-- Fallback Image -->
                    @php
                        $imageSrc = asset('images/mountains/default.png');
                        if ($route->image_path) {
                            $imageSrc = str_starts_with($route->image_path, 'http')
                                ? $route->image_path
                                : asset('storage/' . $route->image_path);
                        }
                    @endphp
                    <img src="{{ $imageSrc }}"
                         alt="{{ $route->name }}"
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                         loading="lazy">
                @endif
                <!-- Gradient Overlay -->
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent pointer-events-none"></div>
                <!-- Title on Image/Map -->
                <div class="absolute bottom-0 left-0 right-0 p-4 z-10">
                    <h3 class="text-xl font-bold text-white mb-2 line-clamp-2 drop-shadow-lg">{{ $route->name }}</h3>
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                            @if($route->difficulty_level === 'Mudah') bg-green-100 text-green-800
                            @elseif($route->difficulty_level === 'Sedang') bg-yellow-100 text-yellow-800
                            @elseif($route->difficulty_level === 'Sulit') bg-orange-100 text-orange-800
                            @else bg-red-100 text-red-800
                            @endif">
                            {{ $route->difficulty_level }}
                        </span>
                        @if($route->activeGpxVersion)
                        @php
                            $ipfsStatus = $route->activeGpxVersion->ipfs_status ?? 'pending_ipfs';
                            $blockchainStatus = $route->activeGpxVersion->blockchain_status ?? 'pending_blockchain';
                        @endphp
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold
                            @if($route->activeGpxVersion->verification_status === 'verified') bg-emerald-100 text-emerald-800
                            @elseif($route->activeGpxVersion->verification_status === 'invalid') bg-red-100 text-red-800
                            @else bg-amber-100 text-amber-800
                            @endif">
                            GPX {{ strtoupper($route->activeGpxVersion->verification_status) }}
                        </span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold
                            @if($ipfsStatus === 'uploaded_ipfs') bg-purple-100 text-purple-800
                            @elseif($ipfsStatus === 'failed_ipfs') bg-red-100 text-red-800
                            @else bg-amber-100 text-amber-800
                            @endif">
                            {{ strtoupper(str_replace('_', ' ', $ipfsStatus)) }}
                        </span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold
                            @if($blockchainStatus === 'registered_blockchain') bg-slate-800 text-white
                            @elseif($blockchainStatus === 'failed_blockchain') bg-red-100 text-red-800
                            @else bg-amber-100 text-amber-800
                            @endif">
                            {{ strtoupper(str_replace('_', ' ', $blockchainStatus)) }}
                        </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Card Body -->
            <div class="p-6">
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <!-- Distance -->
                    <div class="text-center">
                        <p class="text-2xl font-bold text-slate-800">{{ $route->formatted_distance }}</p>
                        <p class="text-xs text-slate-500">Jarak</p>
                    </div>

                    <!-- Duration -->
                    <div class="text-center">
                        <p class="text-2xl font-bold text-slate-800">{{ number_format($route->naismith_duration_hour, 1) }}h</p>
                        <p class="text-xs text-slate-500">Waktu</p>
                    </div>

                    <!-- Elevation -->
                    <div class="text-center">
                        <p class="text-2xl font-bold text-slate-800">{{ number_format($route->elevation_gain_m) }}m</p>
                        <p class="text-xs text-slate-500">Elevasi</p>
                    </div>

                    <!-- Grade -->
                    <div class="text-center">
                        <p class="text-2xl font-bold text-slate-800">{{ $route->formatted_grade }}</p>
                        <p class="text-xs text-slate-500">Grade</p>
                    </div>
                </div>

                @if($route->activeGpxVersion)
                <div class="mb-4 rounded-2xl bg-slate-50 border border-slate-100 p-3 text-xs text-slate-600 space-y-1">
                    <p><span class="font-bold text-slate-700">GPX:</span> {{ $route->activeGpxVersion->verification_status_explanation }}</p>
                    <p><span class="font-bold text-slate-700">IPFS:</span> {{ $route->activeGpxVersion->ipfs_status_explanation }}</p>
                    <p><span class="font-bold text-slate-700">Blockchain:</span> {{ $route->activeGpxVersion->blockchain_status_explanation }}</p>
                </div>
                @endif

                <!-- Actions -->
                <div class="flex gap-2">
                    <a href="{{ route('routes.show', $route) }}"
                       class="flex-1 py-2 text-center bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-medium rounded-xl transition-colors">
                        Detail
                    </a>
                    @auth
                    @if(Auth::user()->isAdmin())
                    <form action="{{ route('routes.destroy', $route) }}" method="POST"
                          onsubmit="return confirm('Yakin ingin menghapus rute ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="px-4 py-2 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </form>
                    @endif
                    @endauth
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Pagination -->
    <div class="flex justify-center">
        {{ $routes->links() }}
    </div>
    @endif
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize maps for routes with coordinates
    @foreach($routes as $route)
        @if($route->route_coordinates && count($route->route_coordinates) > 0)
        (function() {
            var coords = @json($route->route_coordinates);
            if (coords && coords.length > 0) {
                var map = L.map('map-{{ $route->id }}', {
                    zoomControl: false,
                    attributionControl: false,
                    dragging: false,
                    scrollWheelZoom: false
                });

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 18
                }).addTo(map);

                var polyline = L.polyline(coords, {
                    color: '#10b981',
                    weight: 3,
                    opacity: 0.9
                }).addTo(map);

                map.fitBounds(polyline.getBounds(), { padding: [10, 10] });
            }
        })();
        @endif
    @endforeach
});
</script>
@endpush

@endsection
