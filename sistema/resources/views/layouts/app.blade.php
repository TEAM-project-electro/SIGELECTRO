<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Inicio') | SIGELECTRO · Electrocentro S. A.</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-slate-800" x-data="{ menu: false }">
<div class="min-h-full lg:flex">

    {{-- Fondo del menú móvil --}}
    <div x-show="menu" x-cloak @click="menu = false" class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden"></div>

    {{-- Navegación lateral (única para escritorio y móvil) --}}
    <aside :class="menu ? 'translate-x-0' : '-translate-x-full'"
           class="fixed inset-y-0 left-0 z-50 w-60 bg-brand-950 text-slate-200 flex flex-col transform transition-transform lg:static lg:translate-x-0">
        <div class="h-16 flex items-center justify-between px-3.5 border-b border-white/10">
            <a href="{{ route('tablero') }}" class="flex items-center gap-2.5 min-w-0">
                <div class="bg-white p-1 rounded-lg shrink-0">
                    <img src="{{ asset('images/logo-electrocentro.jpg') }}" alt="Logo Electrocentro" class="h-6 w-auto">
                </div>
                <div class="leading-tight truncate">
                    <span class="block text-sm font-black tracking-wide text-white">SIGELECTRO</span>
                    <span class="block text-[10px] text-slate-400 font-semibold truncate">ELECTROCENTRO S. A.</span>
                </div>
            </a>
            <button type="button" @click="menu = false" class="lg:hidden p-1 text-slate-400 hover:text-white" aria-label="Cerrar menú">✕</button>
        </div>

        @include('layouts.partials.sidebar-nav')
        @include('layouts.partials.sidebar-user')
    </aside>

    {{-- Contenido --}}
    <div class="flex-1 min-w-0 flex flex-col">
        <header class="lg:hidden h-14 bg-white border-b border-slate-200 px-4 flex items-center justify-between sticky top-0 z-30">
            <div class="flex items-center gap-2.5">
                <button type="button" @click="menu = true" class="p-2 -ml-2 rounded-lg text-slate-600 hover:bg-slate-100" aria-label="Abrir menú">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="flex items-center gap-2">
                    <img src="{{ asset('images/logo-electrocentro.jpg') }}" alt="Logo Electrocentro" class="h-6 w-auto">
                    <span class="text-sm font-black text-slate-900 tracking-tight">SIGELECTRO</span>
                </div>
            </div>
            <span class="text-[10px] font-bold text-brand-900 uppercase">ELECTROCENTRO S. A.</span>
        </header>

        <main class="flex-1 w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
            @if (session('success'))
                <x-alert type="success">{{ session('success') }}</x-alert>
            @endif
            @if (session('error'))
                <x-alert type="error">{{ session('error') }}</x-alert>
            @endif

            @yield('content')
        </main>

        <footer class="px-4 sm:px-6 lg:px-8 py-3 text-[11px] text-slate-400 text-center border-t border-slate-200">
            Prototipo académico · Proyectos y montos tomados del PGD 2023–2026; hitos, avances y gastos son datos demostrativos.
        </footer>
    </div>
</div>
</body>
</html>
