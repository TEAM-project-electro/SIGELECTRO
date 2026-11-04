<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'SIG Electrocentro - Gobierno Digital' }}</title>

    <!-- Activos compilados localmente -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-slate-800 flex flex-col">
    <!-- Navbar Institucional -->
    <header class="bg-blue-900 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Marca / Logo -->
                <div class="flex items-center space-x-3">
                    <a href="{{ route('tablero') }}" class="flex items-center space-x-2">
                        <div class="w-8 h-8 rounded-lg bg-blue-800 flex items-center justify-center text-amber-400 font-bold">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <span class="font-bold text-lg tracking-tight">ELECTROCENTRO</span>
                        <span class="text-xs px-2 py-0.5 rounded bg-blue-800 text-blue-200 uppercase font-semibold">SIG PGD</span>
                    </a>
                </div>

                <!-- Enlaces de navegación -->
                <nav class="hidden md:flex space-x-4 text-sm font-medium">
                    <a href="{{ route('tablero') }}" class="px-3 py-2 rounded-md hover:bg-blue-800 text-white transition">Tablero Gerencial</a>
                    <a href="{{ route('proyectos.index') }}" class="px-3 py-2 rounded-md hover:bg-blue-800 text-blue-100 transition">Cartera de Proyectos</a>
                    <a href="{{ route('salud') }}" class="px-3 py-2 rounded-md hover:bg-blue-800 text-blue-100 transition">Diagnóstico Motor</a>
                </nav>

                <!-- Menú de Usuario -->
                <div class="flex items-center space-x-4">
                    @auth
                        <div class="text-right hidden sm:block">
                            <div class="text-sm font-semibold text-white leading-none">
                                {{ Auth::user()->nombre }}
                            </div>
                            <div class="text-xs text-blue-200 mt-1">
                                <span class="inline-block px-1.5 py-0.2 rounded text-[10px] uppercase font-bold
                                    @if(Auth::user()->esAdmin()) bg-purple-900 text-purple-200
                                    @elseif(Auth::user()->esResponsable()) bg-emerald-900 text-emerald-200
                                    @else bg-amber-900 text-amber-200 @endif">
                                    {{ Auth::user()->perfil }}
                                </span>
                                &bull; {{ Auth::user()->area }}
                            </div>
                        </div>

                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" title="Cerrar sesión"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded bg-blue-800 hover:bg-red-700 text-white transition">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Salir
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-sm text-white hover:underline">Iniciar Sesión</a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Contenido Principal -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @if (session('success'))
            <div class="mb-4 p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 rounded-r shadow-sm">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-800 rounded-r shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-4 text-center text-xs text-slate-500">
        Electrocentro S. A. &bull; Sistema de Información Gerencial para Seguimiento de Proyectos de Gobierno Digital (2023-2026)
    </footer>
</body>
</html>
