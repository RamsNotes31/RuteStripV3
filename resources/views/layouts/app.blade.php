<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'RuteStrip') - Sistem Rekomendasi Rute Pendakian</title>
    <link rel="icon" type="image/svg+xml" href="/images/favicon.svg">

    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js for dropdown -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Leaflet.js for maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                        },
                        mountain: {
                            50: '#f0fdfa',
                            100: '#ccfbf1',
                            200: '#99f6e4',
                            300: '#5eead4',
                            400: '#2dd4bf',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                            800: '#115e59',
                            900: '#134e4a',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                    backgroundImage: {
                        'mountain-gradient': 'linear-gradient(135deg, #065f46 0%, #0f766e 50%, #115e59 100%)',
                        'hero-pattern': 'url("data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.05\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E")',
                    }
                }
            }
        }
    </script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            font-family: 'Inter', system-ui, sans-serif;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .gradient-text {
            background: linear-gradient(135deg, #059669 0%, #0d9488 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .mountain-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .mountain-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        .floating {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .pulse-glow {
            animation: pulse-glow 2s ease-in-out infinite;
        }

        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(16, 185, 129, 0.3); }
            50% { box-shadow: 0 0 40px rgba(16, 185, 129, 0.6); }
        }

        .score-badge {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
    </style>
    @stack('styles')
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-emerald-50 to-teal-50">
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-white/80 backdrop-blur-xl border-b border-emerald-100/50 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <a href="{{ route('search.index') }}" class="flex items-center space-x-3 group">
                    <img src="/images/logo.svg" alt="RuteStrip" class="w-10 h-10 rounded-xl shadow-lg group-hover:shadow-xl transition-shadow">
                    <span class="text-xl font-bold gradient-text">RuteStrip</span>
                </a>

                <!-- Navigation Links -->
                <div class="flex items-center space-x-1">
                    <a href="{{ route('search.index') }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-all
                              {{ request()->routeIs('search.*') ? 'bg-emerald-100 text-emerald-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        <span class="flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <span>Cari Rute</span>
                        </span>
                    </a>
                    <a href="{{ route('routes.index') }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-all
                              {{ request()->routeIs('routes.index') || request()->routeIs('routes.show') ? 'bg-emerald-100 text-emerald-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        <span class="flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                            </svg>
                            <span>Semua Rute</span>
                        </span>
                    </a>
                    <a href="{{ route('chat.index') }}"
                       class="px-4 py-2 rounded-lg text-sm font-medium transition-all
                              {{ request()->routeIs('chat.*') ? 'bg-indigo-100 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        <span class="flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                            </svg>
                            <span>AI Chat</span>
                        </span>
                    </a>
                    @auth
                    <!-- User Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" @click.away="open = false"
                                class="flex items-center space-x-2 px-4 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100 transition-all">
                            <div class="w-8 h-8 bg-emerald-100 rounded-full flex items-center justify-center">
                                <span class="text-emerald-700 font-bold text-sm">{{ substr(Auth::user()->name, 0, 1) }}</span>
                            </div>
                            <span class="hidden sm:inline">{{ Auth::user()->name }}</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-transition class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-slate-100 py-2 z-50">
                            <div class="px-4 py-2 border-b border-slate-100">
                                <p class="text-sm font-medium text-slate-800">{{ Auth::user()->name }}</p>
                                <p class="text-xs text-slate-500">{{ Auth::user()->email }}</p>
                            </div>
                            <a href="{{ route('user.dashboard') }}" class="flex items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Dashboard</a>
                            <a href="{{ route('user.favorites') }}" class="flex items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Rute Favorit</a>
                            <a href="{{ route('user.profile') }}" class="flex items-center px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">Edit Profil</a>
                            @if(Auth::user()->isAdmin())
                            <div class="border-t border-slate-100 my-1"></div>
                            <a href="{{ route('admin.dashboard') }}" class="flex items-center px-4 py-2 text-sm text-purple-700 hover:bg-purple-50">Admin Panel</a>
                            @endif
                            <div class="border-t border-slate-100 my-1"></div>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50">Logout</button>
                            </form>
                        </div>
                    </div>
                    @else
                    <a href="{{ route('login') }}" class="px-4 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100">Masuk</a>
                    <a href="{{ route('register') }}" class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm font-medium hover:bg-emerald-700">Daftar</a>
                    @endauth
                    <!-- Upload Dropdown -->
                    <div class="relative ml-2" x-data="{ open: false }">
                        <button @click="open = !open" @click.away="open = false"
                                class="px-4 py-2 bg-mountain-gradient text-white rounded-lg text-sm font-medium shadow-lg hover:shadow-xl transition-all hover:scale-105 flex items-center space-x-2
                                       {{ request()->routeIs('routes.create') || request()->routeIs('routes.batch') ? 'ring-2 ring-emerald-300 ring-offset-2' : '' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            <span>Upload GPX</span>
                            <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <div x-show="open" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-slate-100 overflow-hidden z-50">
                            <a href="{{ route('routes.create') }}" class="flex items-center space-x-3 px-4 py-3 hover:bg-emerald-50 transition-colors">
                                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-slate-700">Upload Single</p>
                                    <p class="text-xs text-slate-500">Satu file GPX</p>
                                </div>
                            </a>
                            <a href="{{ route('routes.batch') }}" class="flex items-center space-x-3 px-4 py-3 hover:bg-indigo-50 transition-colors border-t border-slate-100">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-slate-700">Batch Upload</p>
                                    <p class="text-xs text-slate-500">Banyak file sekaligus</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="pt-20 min-h-screen">
        <!-- Flash Messages -->
        @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <p class="text-emerald-700 font-medium">{{ session('success') }}</p>
            </div>
        </div>
        @endif

        @if(session('warning'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <svg class="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <p class="text-amber-700 font-medium">{{ session('warning') }}</p>
            </div>
        </div>
        @endif

        @if($errors->any())
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                <div class="flex items-center space-x-3">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <ul class="text-red-700 font-medium">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gradient-to-b from-slate-900 to-slate-950 text-white pt-16 pb-8 mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Main Footer Content -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
                <!-- Brand Section -->
                <div class="lg:col-span-1">
                    <div class="flex items-center space-x-3 mb-4">
                        <img src="/images/logo.svg" alt="RuteStrip" class="w-12 h-12 rounded-xl shadow-lg">
                        <span class="text-2xl font-bold">RuteStrip</span>
                    </div>
                    <p class="text-slate-400 text-sm leading-relaxed mb-6">
                        Sistem rekomendasi rute pendakian gunung berbasis AI menggunakan Content-Based Filtering dengan SBERT dan Cosine Similarity.
                    </p>
                    <!-- Social Links -->
                    <div class="flex space-x-3">
                        <a href="#" class="w-10 h-10 bg-slate-800 hover:bg-emerald-600 rounded-lg flex items-center justify-center transition-all duration-300 group">
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                            </svg>
                        </a>
                        <a href="#" class="w-10 h-10 bg-slate-800 hover:bg-blue-600 rounded-lg flex items-center justify-center transition-all duration-300 group">
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
                            </svg>
                        </a>
                        <a href="#" class="w-10 h-10 bg-slate-800 hover:bg-pink-600 rounded-lg flex items-center justify-center transition-all duration-300 group">
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-white" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                            </svg>
                        </a>
                    </div>
                </div>

                <!-- Navigation Links -->
                <div>
                    <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                        </svg>
                        Navigasi
                    </h3>
                    <ul class="space-y-3">
                        <li>
                            <a href="{{ route('search.index') }}" class="text-slate-400 hover:text-emerald-400 flex items-center transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                                Cari Rute
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('routes.index') }}" class="text-slate-400 hover:text-emerald-400 flex items-center transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                                Semua Rute
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('routes.create') }}" class="text-slate-400 hover:text-emerald-400 flex items-center transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Upload GPX
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('architecture') }}" class="text-slate-400 hover:text-emerald-400 flex items-center transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                                Arsitektur Sistem
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Features -->
                <div>
                    <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Fitur Unggulan
                    </h3>
                    <ul class="space-y-3">
                        <li class="flex items-start text-slate-400">
                            <span class="text-emerald-400 mr-2 mt-1">✓</span>
                            <span>Rekomendasi AI dengan SBERT</span>
                        </li>
                        <li class="flex items-start text-slate-400">
                            <span class="text-emerald-400 mr-2 mt-1">✓</span>
                            <span>Analisis File GPX Otomatis</span>
                        </li>
                        <li class="flex items-start text-slate-400">
                            <span class="text-emerald-400 mr-2 mt-1">✓</span>
                            <span>Visualisasi Peta Interaktif</span>
                        </li>
                        <li class="flex items-start text-slate-400">
                            <span class="text-emerald-400 mr-2 mt-1">✓</span>
                            <span>Kalkulasi Naismith Time</span>
                        </li>
                        <li class="flex items-start text-slate-400">
                            <span class="text-emerald-400 mr-2 mt-1">✓</span>
                            <span>Rating & Review Rute</span>
                        </li>
                    </ul>
                </div>

                <!-- Contact & Info -->
                <div>
                    <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Tentang Sistem
                    </h3>
                    <ul class="space-y-3">
                        <li class="flex items-start text-slate-400">
                            <svg class="w-4 h-4 mr-2 mt-1 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <span>Content-Based Filtering</span>
                        </li>
                        <li class="flex items-start text-slate-400">
                            <svg class="w-4 h-4 mr-2 mt-1 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            <span>Cosine Similarity</span>
                        </li>
                        <li class="flex items-start text-slate-400">
                            <svg class="w-4 h-4 mr-2 mt-1 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                            </svg>
                            <span>Laravel 11 + Python 3.11</span>
                        </li>
                        <li class="flex items-start text-slate-400">
                            <svg class="w-4 h-4 mr-2 mt-1 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            <span>SBERT Multilingual</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Bottom Bar -->
            <div class="border-t border-slate-800 pt-8">
                <div class="flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
                    <div class="flex flex-wrap justify-center md:justify-start gap-4 text-sm text-slate-400">
                        <a href="{{ route('architecture') }}" class="hover:text-emerald-400 transition-colors duration-200">Arsitektur</a>
                        <span class="text-slate-700">•</span>
                        <a href="{{ route('routes.index') }}" class="hover:text-emerald-400 transition-colors duration-200">Database Rute</a>
                        <span class="text-slate-700">•</span>
                        <a href="{{ route('search.index') }}" class="hover:text-emerald-400 transition-colors duration-200">Pencarian</a>
                    </div>
                    <div class="flex items-center space-x-2 text-sm text-slate-400">
                        <span>Dibuat dengan</span>
                        <svg class="w-4 h-4 text-red-500 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"/>
                        </svg>
                        <span>untuk pendaki Indonesia</span>
                    </div>
                </div>
                <p class="text-center text-slate-500 text-sm mt-6">
                    &copy; {{ date('Y') }} RuteStrip - Sistem Rekomendasi Rute Pendakian dengan AI. All rights reserved.
                </p>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
