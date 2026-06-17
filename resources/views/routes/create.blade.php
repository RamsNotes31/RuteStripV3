@extends('layouts.app')

@section('title', 'Upload GPX')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Page Header -->
    <div class="mb-8">
        <a href="{{ route('routes.index') }}" class="inline-flex items-center text-emerald-600 hover:text-emerald-700 mb-4 group">
            <svg class="w-5 h-5 mr-2 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Daftar Rute
        </a>

        <h1 class="text-3xl font-bold text-slate-800 mb-2">Upload File GPX</h1>
        <p class="text-slate-600">Upload file GPX dari perangkat GPS atau aplikasi tracking Anda.</p>
    </div>

    <!-- Upload Form -->
    <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
        <div class="bg-mountain-gradient p-6">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-white">Upload Jalur Baru</h2>
                    <p class="text-emerald-100 text-sm">Format yang didukung: .gpx, .xml</p>
                </div>
            </div>
        </div>

        <form action="{{ route('routes.store') }}" method="POST" enctype="multipart/form-data" class="p-8">
            @csrf

            <!-- Route Name -->
            <div class="mb-6">
                <label for="name" class="block text-sm font-semibold text-slate-700 mb-2">
                    Nama Jalur/Gunung <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       name="name"
                       id="name"
                       value="{{ old('name') }}"
                       placeholder="Contoh: Gunung Semeru via Ranu Pani"
                       class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all @error('name') border-red-500 @enderror"
                       required>
                @error('name')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description (Optional) -->
            <div class="mb-6">
                <label for="description" class="block text-sm font-semibold text-slate-700 mb-2">
                    Deskripsi Jalur <span class="text-slate-400 font-normal">(Opsional)</span>
                </label>
                <textarea name="description"
                          id="description"
                          rows="4"
                          class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all resize-none"
                          placeholder="Contoh: Jalur dengan vegetasi hutan pinus, sumber air tersedia di pos 2, pemandangan sabana yang indah...">{{ old('description') }}</textarea>
                <p class="mt-2 text-xs text-slate-500">
                    Tambahkan informasi seperti: tipe vegetasi, ketersediaan sumber air, panorama, kondisi medan, dll.
                </p>
                @error('description')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Change Log -->
            <div class="mb-6">
                <label for="change_log" class="block text-sm font-semibold text-slate-700 mb-2">
                    Catatan Provenance <span class="text-slate-400 font-normal">(Opsional)</span>
                </label>
                <textarea name="change_log"
                          id="change_log"
                          rows="3"
                          class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none transition-all resize-none"
                          placeholder="Contoh: Upload awal dataset GPX dari tracking lapangan via aplikasi GPS.">{{ old('change_log') }}</textarea>
                <p class="mt-2 text-xs text-slate-500">
                    Catatan ini akan muncul di riwayat provenance GPX untuk kebutuhan audit dan paper.
                </p>
                @error('change_log')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- GPX File Upload -->
            <div class="mb-8">
                <label for="gpx_file" class="block text-sm font-semibold text-slate-700 mb-2">
                    File GPX <span class="text-red-500">*</span>
                </label>

                <div class="relative">
                    <input type="file"
                           name="gpx_file"
                           id="gpx_file"
                           accept=".gpx,.xml"
                           class="hidden"
                           required>

                    <label for="gpx_file"
                           class="flex flex-col items-center justify-center w-full h-48 border-2 border-dashed border-slate-300 rounded-2xl cursor-pointer hover:border-emerald-400 hover:bg-emerald-50/50 transition-all @error('gpx_file') border-red-500 @enderror"
                           id="dropzone">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6" id="dropzone-content">
                            <div class="w-16 h-16 bg-emerald-100 rounded-2xl flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                            </div>
                            <p class="mb-2 text-sm text-slate-600">
                                <span class="font-semibold text-emerald-600">Klik untuk upload</span> atau drag & drop
                            </p>
                            <p class="text-xs text-slate-500">File GPX atau XML (Maks. 10MB)</p>
                        </div>
                        <div class="hidden flex-col items-center justify-center pt-5 pb-6" id="file-selected">
                            <div class="w-16 h-16 bg-green-100 rounded-2xl flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            </div>
                            <p class="mb-2 text-sm font-semibold text-green-600" id="file-name">File terpilih</p>
                            <p class="text-xs text-slate-500">Klik untuk mengganti file</p>
                        </div>
                    </label>
                </div>

                @error('gpx_file')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-8">
                <div class="flex items-start space-x-3">
                    <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div class="text-sm text-blue-700">
                        <p class="font-semibold mb-1">Proses Analisis Otomatis</p>
                        <p>Sistem akan mengekstrak data berikut secara otomatis:</p>
                        <ul class="mt-2 list-disc list-inside space-y-1">
                            <li>Jarak total jalur (km)</li>
                            <li>Total elevasi gain (meter)</li>
                            <li>Estimasi waktu tempuh (Naismith)</li>
                            <li>Grade rata-rata (%)</li>
                            <li>Vector embedding untuk pencarian AI</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <button type="submit"
                    class="w-full py-4 bg-mountain-gradient text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all flex items-center justify-center space-x-2 hover:scale-[1.02]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <span>Upload & Proses GPX</span>
            </button>
        </form>
    </div>
</div>

<script>
    // File input handling
    const fileInput = document.getElementById('gpx_file');
    const dropzoneContent = document.getElementById('dropzone-content');
    const fileSelected = document.getElementById('file-selected');
    const fileName = document.getElementById('file-name');

    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            dropzoneContent.classList.add('hidden');
            fileSelected.classList.remove('hidden');
            fileSelected.classList.add('flex');
            fileName.textContent = this.files[0].name;
        }
    });

    // Drag and drop
    const dropzone = document.getElementById('dropzone');

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, unhighlight, false);
    });

    function highlight(e) {
        dropzone.classList.add('border-emerald-400', 'bg-emerald-50');
    }

    function unhighlight(e) {
        dropzone.classList.remove('border-emerald-400', 'bg-emerald-50');
    }

    dropzone.addEventListener('drop', handleDrop, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        fileInput.files = files;
        fileInput.dispatchEvent(new Event('change'));
    }
</script>
@endsection
