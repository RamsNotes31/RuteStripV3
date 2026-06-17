@extends('layouts.app')

@section('title', 'Upload Versi GPX Baru - ' . $route->name)

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <a href="{{ route('routes.provenance', $route) }}" class="inline-flex items-center text-emerald-600 hover:text-emerald-700 mb-4 group font-medium">
            <svg class="w-5 h-5 mr-2 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Provenance
        </a>

        <h1 class="text-3xl font-bold text-slate-800 mb-2">Upload Versi GPX Baru</h1>
        <p class="text-slate-600">Rute: <span class="font-semibold text-emerald-700">{{ $route->name }}</span></p>
    </div>

    <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
        <div class="bg-gradient-to-r from-emerald-600 to-teal-600 p-6">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M5 19A9 9 0 0119 5"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-white">Dataset Version Control</h2>
                    <p class="text-emerald-100 text-sm">
                        Versi aktif saat ini: {{ $route->activeGpxVersion ? 'v' . $route->activeGpxVersion->version_number : 'belum ada' }}
                    </p>
                </div>
            </div>
        </div>

        <form action="{{ route('routes.versions.store', $route) }}" method="POST" enctype="multipart/form-data" class="p-8">
            @csrf

            <div class="mb-6">
                <label for="change_log" class="block text-sm font-semibold text-slate-700 mb-2">
                    Change Log <span class="text-red-500">*</span>
                </label>
                <textarea name="change_log"
                          id="change_log"
                          rows="4"
                          class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all resize-none @error('change_log') border-red-500 @enderror"
                          placeholder="Contoh: Perbaikan track GPS pada segmen pos 2 sampai puncak, menghapus titik koordinat noise."
                          required>{{ old('change_log') }}</textarea>
                <p class="mt-2 text-xs text-slate-500">Wajib diisi agar alasan perubahan dataset terdokumentasi untuk provenance history.</p>
                @error('change_log')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-8">
                <label for="gpx_file" class="block text-sm font-semibold text-slate-700 mb-2">
                    File GPX Versi Baru <span class="text-red-500">*</span>
                </label>

                <input type="file"
                       name="gpx_file"
                       id="gpx_file"
                       accept=".gpx,.xml"
                       class="block w-full text-sm text-slate-700 border border-slate-200 rounded-xl cursor-pointer bg-slate-50 focus:outline-none file:mr-4 file:py-3 file:px-4 file:rounded-l-xl file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 @error('gpx_file') border-red-500 @enderror"
                       required>
                <p class="mt-2 text-xs text-slate-500">Format yang didukung: GPX atau XML, maksimal 10MB.</p>
                @error('gpx_file')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-8">
                <div class="flex items-start space-x-3">
                    <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div class="text-sm text-blue-700">
                        <p class="font-semibold mb-1">Dampak Upload Versi Baru</p>
                        <p>File akan diproses ulang, hash SHA-256 dibuat, versi baru menjadi aktif, dan versi lama tetap tersimpan di riwayat provenance.</p>
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full py-4 bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all flex items-center justify-center space-x-2 hover:scale-[1.02]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v6h6M20 20v-6h-6M5 19A9 9 0 0119 5"/>
                </svg>
                <span>Upload Sebagai Versi Baru</span>
            </button>
        </form>
    </div>
</div>
@endsection
