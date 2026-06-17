@extends('layouts.app')

@section('title', 'Provenance GPX - ' . $route->name)

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <a href="{{ route('routes.show', $route) }}" class="inline-flex items-center text-emerald-600 hover:text-emerald-700 mb-4 group font-medium">
            <svg class="w-5 h-5 mr-2 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Detail Rute
        </a>

        <div class="bg-mountain-gradient rounded-3xl p-8 text-white shadow-xl">
            <p class="text-emerald-100 text-sm font-semibold mb-2">GPX Dataset Provenance</p>
            <h1 class="text-3xl md:text-4xl font-extrabold mb-3">{{ $route->name }}</h1>
            <p class="text-emerald-50 max-w-3xl">
                Riwayat versi, hash SHA-256, uploader, dan status verifikasi file GPX. Data ini menjadi dasar audit trail lokal sebelum integrasi IPFS dan blockchain.
            </p>
            @auth
            @if(Auth::user()->isAdmin())
            <div class="flex flex-wrap gap-3 mt-5">
                <a href="{{ route('routes.versions.create', $route) }}" class="inline-flex items-center px-5 py-3 bg-white text-emerald-700 rounded-2xl font-bold shadow-lg hover:bg-emerald-50 transition-colors">
                    Upload Versi GPX Baru
                </a>
                <a href="{{ route('routes.provenance.export-verification-logs', $route) }}" class="inline-flex items-center px-5 py-3 bg-emerald-900/40 text-white rounded-2xl font-bold border border-white/30 hover:bg-emerald-900/60 transition-colors">
                    Export Verification CSV
                </a>
            </div>
            @endif
            @endauth
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-lg p-6">
            <p class="text-sm text-slate-500 mb-1">Total versi</p>
            <p class="text-3xl font-extrabold text-slate-800">{{ $route->gpxVersions->count() }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-lg p-6">
            <p class="text-sm text-slate-500 mb-1">Versi aktif</p>
            <p class="text-3xl font-extrabold text-slate-800">
                {{ $route->activeGpxVersion ? 'v' . $route->activeGpxVersion->version_number : '-' }}
            </p>
            @if($route->activeGpxVersion)
            <a href="{{ route('routes.provenance.download-version', [$route, $route->activeGpxVersion]) }}" class="inline-flex mt-3 px-3 py-2 rounded-xl bg-blue-50 text-blue-700 text-sm font-bold hover:bg-blue-100">
                Download GPX Aktif
            </a>
            @endif
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-lg p-6">
            <p class="text-sm text-slate-500 mb-1">Status aktif</p>
            @if($route->activeGpxVersion)
            <span class="inline-flex px-3 py-1 rounded-full text-sm font-bold
                @if($route->activeGpxVersion->verification_status === 'verified') bg-emerald-100 text-emerald-800
                @elseif($route->activeGpxVersion->verification_status === 'invalid') bg-red-100 text-red-800
                @else bg-amber-100 text-amber-800
                @endif">
                {{ str_replace('_', ' ', strtoupper($route->activeGpxVersion->verification_status)) }}
            </span>
            <p class="text-xs text-slate-500 mt-3">{{ $route->activeGpxVersion->verification_status_explanation }}</p>
            @else
            <span class="inline-flex px-3 py-1 rounded-full text-sm font-bold bg-slate-100 text-slate-600">BELUM ADA</span>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-slate-50">
            <h2 class="text-xl font-bold text-slate-800">Riwayat Versi GPX</h2>
            <p class="text-sm text-slate-500 mt-1">Versi terbaru ditampilkan paling atas.</p>
        </div>

        @if($route->gpxVersions->isEmpty())
        <div class="p-10 text-center text-slate-500">Belum ada metadata provenance.</div>
        @else
        <div class="divide-y divide-slate-100">
            @foreach($route->gpxVersions as $version)
            <div class="p-6">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-2">
                            <h3 class="text-lg font-extrabold text-slate-800">Versi {{ $version->version_number }}</h3>
                            @if($version->is_active)
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">AKTIF</span>
                            @endif
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold
                                @if($version->verification_status === 'verified') bg-emerald-100 text-emerald-700
                                @elseif($version->verification_status === 'invalid') bg-red-100 text-red-700
                                @else bg-amber-100 text-amber-700
                                @endif">
                                {{ str_replace('_', ' ', strtoupper($version->verification_status)) }}
                            </span>
                        </div>
                        <p class="text-sm text-slate-500">
                            Diupload {{ $version->created_at->format('d M Y, H:i') }}
                            @if($version->uploader)
                            oleh {{ $version->uploader->name }}
                            @endif
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('routes.provenance.download-version', [$route, $version]) }}" class="px-4 py-2 rounded-xl bg-blue-50 text-blue-700 text-sm font-bold hover:bg-blue-100">
                            Download GPX
                        </a>
                        @auth
                        @if(Auth::user()->isAdmin())
                        <form action="{{ route('routes.provenance.verify', [$route, $version]) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-50 text-emerald-700 text-sm font-bold hover:bg-emerald-100">
                                Verify Ulang
                            </button>
                        </form>
                        @if($version->ipfs_cid)
                        <form action="{{ route('routes.provenance.verify-ipfs', [$route, $version]) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-4 py-2 rounded-xl bg-purple-50 text-purple-700 text-sm font-bold hover:bg-purple-100">
                                Verify via IPFS
                            </button>
                        </form>
                        @if(!$version->blockchain_tx_hash)
                        <form action="{{ route('routes.provenance.register-blockchain', [$route, $version]) }}" method="POST">
                            @csrf
                            <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 text-white text-sm font-bold hover:bg-slate-900">
                                Register Blockchain
                            </button>
                        </form>
                        @endif
                        @endif
                        @unless($version->is_active)
                        <form action="{{ route('routes.provenance.restore', [$route, $version]) }}" method="POST" onsubmit="return confirm('Pulihkan versi GPX ini sebagai versi aktif?')">
                            @csrf
                            <button type="submit" class="px-4 py-2 rounded-xl bg-blue-50 text-blue-700 text-sm font-bold hover:bg-blue-100">
                                Restore
                            </button>
                        </form>
                        @endunless
                        @endif
                        @endauth
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">SHA-256 Hash</p>
                        <code class="block text-xs text-slate-700 break-all">{{ $version->file_hash }}</code>
                    </div>
                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">File GPX</p>
                        <p class="text-sm text-slate-700 break-all">{{ $version->gpx_file_path }}</p>
                    </div>
                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Change Log</p>
                        <p class="text-sm text-slate-700">{{ $version->change_log ?: 'Tidak ada catatan perubahan.' }}</p>
                    </div>
                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Verifikasi Terakhir</p>
                        <p class="text-sm text-slate-700">{{ $version->verified_at ? $version->verified_at->format('d M Y, H:i') : '-' }}</p>
                        <p class="text-xs text-slate-500 mt-2">{{ $version->verification_status_explanation }}</p>
                    </div>
                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">IPFS CID</p>
                        @if($version->ipfs_cid)
                        <code class="block text-xs text-slate-700 break-all">{{ $version->ipfs_cid }}</code>
                        @else
                        <p class="text-sm text-amber-700">Belum tersimpan di IPFS.</p>
                        @endif
                        <p class="text-xs text-slate-500 mt-2">{{ $version->ipfs_status_explanation }}</p>
                    </div>
                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">IPFS Gateway</p>
                        @if($version->ipfs_url)
                        <a href="{{ $version->ipfs_url }}" target="_blank" rel="noopener" class="text-sm text-emerald-700 hover:text-emerald-800 font-semibold break-all">Buka file IPFS</a>
                        <p class="text-xs text-slate-500 mt-2">Upload time: {{ $version->ipfs_upload_time_ms ?? '-' }} ms</p>
                        <p class="text-xs text-slate-500 mt-1">Retrieval time: {{ $version->ipfs_retrieval_time_ms ?? '-' }} ms</p>
                        @else
                        <p class="text-sm text-slate-500">Menunggu upload IPFS atau upload gagal.</p>
                        @endif
                    </div>
                    <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100 md:col-span-2">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Blockchain Registry</p>
                        @if($version->blockchain_tx_hash)
                        <div class="grid md:grid-cols-2 gap-3 text-sm">
                            <div>
                                <span class="text-slate-500 block">Network</span>
                                <span class="font-semibold text-slate-700">{{ $version->blockchain_network ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Registered By</span>
                                <code class="text-xs break-all text-slate-700">{{ $version->blockchain_registered_by ?? '-' }}</code>
                            </div>
                            <div>
                                <span class="text-slate-500 block">Contract</span>
                                @if($version->blockchain_contract_address)
                                <a href="https://sepolia.etherscan.io/address/{{ $version->blockchain_contract_address }}" target="_blank" rel="noopener" class="text-xs break-all text-emerald-700 font-semibold hover:text-emerald-800">{{ $version->blockchain_contract_address }}</a>
                                @else
                                <code class="text-xs break-all text-slate-700">-</code>
                                @endif
                            </div>
                            <div>
                                <span class="text-slate-500 block">Tx Hash</span>
                                <a href="https://sepolia.etherscan.io/tx/{{ $version->blockchain_tx_hash }}" target="_blank" rel="noopener" class="text-xs break-all text-emerald-700 font-semibold hover:text-emerald-800">{{ $version->blockchain_tx_hash }}</a>
                            </div>
                        </div>
                        @else
                        <p class="text-sm text-slate-500">Belum terdaftar di blockchain metadata registry.</p>
                        @endif
                        <p class="text-xs text-slate-500 mt-3">{{ $version->blockchain_status_explanation }}</p>
                    </div>
                </div>

                @if($version->verificationLogs->isNotEmpty())
                <div class="mt-4 bg-white rounded-2xl border border-slate-100 overflow-hidden">
                    <div class="px-4 py-3 bg-slate-50 border-b border-slate-100 text-sm font-bold text-slate-700">Log Verifikasi Terbaru</div>
                    <div class="divide-y divide-slate-100">
                        @foreach($version->verificationLogs as $log)
                        <div class="px-4 py-3 text-sm flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                            <div>
                                <span class="font-bold text-slate-700">{{ str_replace('_', ' ', strtoupper($log->status)) }}</span>
                                <span class="text-slate-500">- {{ $log->message }}</span>
                            </div>
                            <span class="text-xs text-slate-400">{{ $log->verified_at->format('d M Y, H:i') }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection
