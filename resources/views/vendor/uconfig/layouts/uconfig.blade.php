<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') . ' - UltraConfig' }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                        },
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                },
            },
        }
    </script>
    
    <style type="text/tailwindcss">
        @layer utilities {
            .transition-colors { transition-property: color, background-color, border-color; }
            .transition-all { transition-property: all; }
        }
    </style>
    
    @stack('styles')
</head>
<body class="bg-gray-50 font-sans antialiased text-gray-800">
    <div class="min-h-screen flex flex-col">
        <!-- Top Navbar -->
        <header class="bg-gradient-to-r from-primary-700 to-primary-600 text-white shadow-md">
            <div class="container mx-auto px-4 py-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold">{{ __('uconfig::uconfig.title') }}</h1>
                        <p class="text-sm text-primary-100">{{ __('uconfig::uconfig.subtitle') }}</p>
                    </div>
                    
                    <!-- Mobile Menu Button -->
                    <div class="sm:hidden">
                        <button type="button" id="mobile-menu-button" class="text-white hover:text-primary-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Desktop Navigation -->
                    <nav class="hidden sm:flex space-x-4">
                        <a href="{{ route('uconfig.index') }}" class="py-2 px-3 rounded-md transition-colors hover:bg-primary-500 {{ request()->routeIs('uconfig.index') ? 'bg-primary-500' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            {{ __('uconfig::uconfig.nav.dashboard') }}
                        </a>
                        <a href="{{ route('uconfig.create') }}" class="py-2 px-3 rounded-md transition-colors hover:bg-primary-500 {{ request()->routeIs('uconfig.create') ? 'bg-primary-500' : '' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            {{ __('uconfig::uconfig.nav.add_config') }}
                        </a>
                    </nav>
                </div>
                
                <!-- Mobile Navigation Menu -->
                <div id="mobile-menu" class="sm:hidden hidden mt-4 border-t border-primary-500 pt-3">
                    <nav class="flex flex-col space-y-2">
                        <a href="{{ route('uconfig.index') }}" class="py-2 px-3 rounded-md transition-colors hover:bg-primary-500 {{ request()->routeIs('uconfig.index') ? 'bg-primary-500' : '' }}">
                            {{ __('uconfig::uconfig.nav.dashboard') }}
                        </a>
                        <a href="{{ route('uconfig.create') }}" class="py-2 px-3 rounded-md transition-colors hover:bg-primary-500 {{ request()->routeIs('uconfig.create') ? 'bg-primary-500' : '' }}">
                            {{ __('uconfig::uconfig.nav.add_config') }}
                        </a>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-grow container mx-auto px-4 py-6 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow" role="alert">
                    <p>{{ session('success') }}</p>
                </div>
            @endif
            
            @if (session('error'))
                <div class="mb-6 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow" role="alert">
                    <p>{{ session('error') }}</p>
                </div>
            @endif
            
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-gray-800 text-white py-6 mt-auto">
            <div class="container mx-auto px-4 text-center">
                <p class="text-sm">{!! __('uconfig::uconfig.footer.copyright', ['year' => date('Y')]) !!}</p>
                <p class="text-sm mt-2">
                    {{ __('uconfig::uconfig.footer.developed_by') }} 
                    <a href="https://github.com/AutobookNft/UltraConfigManager" 
                       class="text-blue-400 hover:text-blue-300 transition-colors">GitHub</a> | 
                    <a href="https://www.linkedin.com/in/fabiocherici" 
                       class="text-blue-400 hover:text-blue-300 transition-colors">LinkedIn</a>
                </p>
            </div>
        </footer>
    </div>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        });
    </script>
    
    @stack('scripts')
</body>
</html>
