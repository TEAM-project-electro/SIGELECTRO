<!DOCTYPE html>
<html lang="es" class="h-full bg-canvas">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#001836">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Tablero') | SIGELECTRO</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-slate-800 bg-canvas" x-data="{ menu: false }">

<a href="#contenido-principal"
   class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[60] focus:px-4 focus:py-2 focus:rounded-lg focus:bg-brand-800 focus:text-white focus:text-xs focus:font-semibold focus:shadow-lg">
    Saltar al contenido principal
</a>

<div class="min-h-full lg:flex">

    {{-- Fondo del menú móvil --}}
    <div x-show="menu" x-cloak @click="menu = false"
         class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-[1px] lg:hidden"
         aria-hidden="true"></div>

    {{-- Navegación lateral (escritorio y móvil) --}}
    <aside :class="menu ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-50 w-64 shrink-0 bg-brand-950 text-slate-200 flex flex-col transform transition-transform duration-200 ease-out lg:static lg:translate-x-0 border-r border-brand-900/60 overscroll-contain">

        <div class="h-1 w-full electro-rail shrink-0" aria-hidden="true"></div>

        {{-- Identidad institucional sin texto repetido --}}
        <div class="px-4 pt-4 pb-3.5 border-b border-white/10">
            <div class="flex items-start justify-between gap-2">
                <a href="{{ route('tablero') }}" class="block flex-1 min-w-0 group">
                    <div class="bg-white rounded-lg px-2.5 py-1.5 inline-flex items-center shadow-xs">
                        <img src="{{ asset('images/logo-electrocentro.jpg') }}"
                             alt="ELECTROCENTRO S. A."
                             width="136"
                             height="26"
                             fetchpriority="high"
                             class="h-6 w-auto object-contain">
                    </div>
                    <span translate="no" class="block text-[15px] font-bold tracking-tight text-white mt-2.5 group-hover:text-brand-200 transition-colors duration-150">SIGELECTRO</span>
                    <span class="block text-[11px] text-slate-400 mt-0.5 truncate">Plan de Gobierno Digital</span>
                </a>
                <button type="button" @click="menu = false"
                        class="lg:hidden p-1.5 -mr-1 rounded-lg text-slate-400 hover:text-white hover:bg-white/10 transition-colors duration-150"
                        aria-label="Cerrar menú de navegación">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        @include('layouts.partials.sidebar-nav')
        @include('layouts.partials.sidebar-user')
    </aside>

    {{-- Área principal --}}
    <div class="flex-1 min-w-0 flex flex-col">
        {{-- Cabecera móvil --}}
        <header class="lg:hidden h-14 bg-white border-b border-slate-200/90 px-4 flex items-center justify-between sticky top-0 z-30">
            <div class="flex items-center gap-2.5 min-w-0">
                <button type="button" @click="menu = true"
                        class="p-2 -ml-2 rounded-lg text-slate-600 hover:bg-slate-100 transition-colors duration-150"
                        aria-label="Abrir menú de navegación">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
                <img src="{{ asset('images/logo-electrocentro.jpg') }}" alt="ELECTROCENTRO S. A." width="104" height="20" class="h-5 w-auto object-contain">
                <span translate="no" class="text-sm font-bold text-brand-900 tracking-tight truncate">SIGELECTRO</span>
            </div>
            @auth
                <span class="text-[11px] font-medium text-slate-500 shrink-0">{{ Auth::user()->perfil }}</span>
            @endauth
        </header>

        <main id="contenido-principal" class="flex-1 w-full max-w-[1320px] mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-7 space-y-6">
            @if (session('success'))
                <div role="status" aria-live="polite">
                    <x-alert type="success">{{ session('success') }}</x-alert>
                </div>
            @endif
            @if (session('error'))
                <div role="alert" aria-live="assertive">
                    <x-alert type="error">{{ session('error') }}</x-alert>
                </div>
            @endif

            @yield('content')
        </main>

        <footer class="px-4 sm:px-6 lg:px-8 py-3.5 text-[11px] text-slate-500 flex flex-col sm:flex-row items-center justify-between gap-1 border-t border-slate-200/80 bg-white/60">
            <span>Prototipo académico local. Proyectos y montos del PGD 2023–2026; seguimiento y gastos demostrativos.</span>
            <span translate="no" class="font-medium text-slate-400">SIGELECTRO</span>
        </footer>
    </div>
</div>
</body>
</html>
