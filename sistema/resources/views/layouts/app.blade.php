<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'SIG Electrocentro - Sistema de Información Gerencial' }}</title>

    <!-- Activos compilados localmente (sin CDNs externas) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-slate-800 bg-slate-50"
      x-data="{ sidebarOpen: false, userMenuOpen: false }">

    <div class="min-h-full flex flex-col lg:flex-row">

        <!-- ============================================================ -->
        <!-- 1. SIDEBAR MÓVIL (Off-canvas Drawer)                         -->
        <!-- ============================================================ -->
        <div x-show="sidebarOpen" x-cloak class="relative z-50 lg:hidden" role="dialog" aria-modal="true">
            <!-- Backdrop -->
            <div x-show="sidebarOpen"
                 x-transition:enter="transition-opacity ease-linear duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-linear duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="sidebarOpen = false"
                 class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs"></div>

            <div class="fixed inset-0 flex">
                <div x-show="sidebarOpen"
                     x-transition:enter="transition ease-in-out duration-200 transform"
                     x-transition:enter-start="-translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transition ease-in-out duration-200 transform"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="-translate-x-full"
                     class="relative mr-16 flex w-full max-w-xs flex-1">
                    
                    <div class="flex flex-col flex-1 bg-brand-950 text-slate-200 border-r border-brand-900">
                        <!-- Botón cerrar móvil -->
                        <div class="flex items-center justify-between h-16 px-5 border-b border-white/10 bg-brand-900/60">
                            <div class="flex items-center space-x-2.5">
                                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-amber-400 to-amber-500 text-brand-950 flex items-center justify-center font-black shadow-xs">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                </div>
                                <div>
                                    <span class="font-extrabold text-sm tracking-tight text-white block">ELECTROCENTRO</span>
                                    <span class="text-[10px] text-amber-300 font-semibold uppercase tracking-wider block">SIG PGD 2023–2026</span>
                                </div>
                            </div>
                            <button type="button" @click="sidebarOpen = false" class="text-slate-400 hover:text-white p-1 rounded-lg">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Enlaces de navegación móvil -->
                        <div class="flex-1 overflow-y-auto px-4 py-4 space-y-6">
                            @include('layouts.partials.sidebar-nav')
                        </div>

                        <!-- Perfil móvil -->
                        @include('layouts.partials.sidebar-user')
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- 2. SIDEBAR ESCRITORIO (Fijo / Adaptable)                     -->
        <!-- ============================================================ -->
        <aside class="hidden lg:flex lg:w-64 lg:flex-col lg:shrink-0 bg-brand-950 text-slate-300 border-r border-brand-900/80 min-h-screen">
            <!-- Brand Header -->
            <div class="h-16 flex items-center px-5 border-b border-white/10 bg-brand-900/60">
                <a href="{{ route('tablero') }}" class="flex items-center space-x-3 group">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-400 to-amber-500 text-brand-950 flex items-center justify-center font-black shadow-md shadow-amber-500/10 group-hover:scale-105 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div class="leading-tight">
                        <span class="font-extrabold text-sm tracking-tight text-white block">ELECTROCENTRO</span>
                        <span class="text-[10px] text-amber-300 font-bold uppercase tracking-wider block">SIG PGD 2023–2026</span>
                    </div>
                </a>
            </div>

            <!-- Navegación Principal -->
            <div class="flex-1 flex flex-col justify-between overflow-y-auto px-3.5 py-4">
                @include('layouts.partials.sidebar-nav')
            </div>

            <!-- Usuario Activo y Logout -->
            @include('layouts.partials.sidebar-user')
        </aside>

        <!-- ============================================================ -->
        <!-- 3. CONTENEDOR PRINCIPAL: TOPBAR + CONTENIDO                  -->
        <!-- ============================================================ -->
        <div class="flex-1 flex flex-col min-w-0">

            <!-- Topbar Compacto y Funcional -->
            <header class="h-16 bg-white border-b border-slate-200/90 shadow-xs px-4 sm:px-6 lg:px-8 flex items-center justify-between sticky top-0 z-30">
                <div class="flex items-center space-x-3">
                    <!-- Botón Hamburguesa Móvil -->
                    <button type="button" @click="sidebarOpen = true"
                            class="lg:hidden p-2 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-brand-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <!-- Identificador de Sección Activa / Breadcrumb -->
                    <div class="flex items-center space-x-2 text-xs">
                        <span class="font-semibold text-slate-500 hidden sm:inline">Electrocentro S. A.</span>
                        <span class="text-slate-300 hidden sm:inline">/</span>
                        <span class="font-bold text-slate-800 text-sm">
                            @if(request()->routeIs('tablero*'))
                                Tablero Gerencial de Control
                            @elseif(request()->routeIs('proyectos.create'))
                                Cartera &bull; Nuevo Proyecto
                            @elseif(request()->routeIs('proyectos.edit'))
                                Cartera &bull; Editar Proyecto
                            @elseif(request()->routeIs('proyectos.show'))
                                Cartera &bull; Ficha de Proyecto
                            @elseif(request()->routeIs('proyectos*'))
                                Cartera de Proyectos PGD
                            @elseif(request()->routeIs('auditoria*'))
                                Bitácora de Auditoría R4
                            @elseif(request()->routeIs('usuarios*'))
                                Administración de Usuarios
                            @elseif(request()->routeIs('objetivos*'))
                                Catálogo de Objetivos PGD
                            @elseif(request()->routeIs('hitos*'))
                                Gestión de Hitos
                            @elseif(request()->routeIs('avances*'))
                                Seguimiento Presupuestal
                            @else
                                Sistema de Información Gerencial
                            @endif
                        </span>
                    </div>
                </div>

                <!-- Lado Derecho: Badge de Datos Demostrativos & Accesos -->
                <div class="flex items-center space-x-3">
                    <!-- Badge Institucional de Datos Demostrativos -->
                    <div class="hidden md:flex items-center space-x-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-900 border border-amber-200">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        <span>Cartera PGD: 10 Proyectos (S/ 8.62M)</span>
                    </div>

                    <!-- Botón Diagnóstico Técnico Rápido -->
                    <a href="{{ route('salud') }}" target="_blank"
                       title="Verificar salud de MariaDB 10.11 y servicios"
                       class="hidden sm:inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-lg text-slate-600 hover:text-slate-900 hover:bg-slate-100 border border-slate-200 transition">
                        <svg class="w-3.5 h-3.5 mr-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        Salud Motor
                    </a>

                    @auth
                        <!-- Botón Rápido de Salir -->
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" title="Cerrar sesión de {{ Auth::user()->nombre }}"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-semibold rounded-lg text-slate-700 hover:text-rose-700 hover:bg-rose-50 border border-slate-200 transition">
                                <svg class="w-3.5 h-3.5 mr-1 text-slate-400 group-hover:text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                <span>Salir</span>
                            </button>
                        </form>
                    @endauth
                </div>
            </header>

            <!-- Banner Académico Continuo -->
            <div class="bg-gradient-to-r from-brand-900 via-brand-800 to-slate-900 text-white text-[11px] px-4 sm:px-6 lg:px-8 py-1.5 flex items-center justify-between shadow-xs">
                <div class="flex items-center space-x-2">
                    <span class="inline-block px-1.5 py-0.2 rounded bg-amber-400 text-slate-950 font-bold uppercase text-[9px]">
                        Académico
                    </span>
                    <span class="font-medium text-slate-200">
                        Prototipo para Evaluación Gerencial &bull; Datos Oficiales del PGD 2023–2026 de Electrocentro S. A.
                    </span>
                </div>
                <div class="hidden md:flex items-center space-x-3 text-slate-300">
                    <span>MariaDB 10.11 en puerto 3307</span>
                    <span>&bull;</span>
                    <span>Laravel 12 / PHP 8.3</span>
                </div>
            </div>

            <!-- Contenido Principal -->
            <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

                <!-- Alertas Flash del Servidor -->
                @if (session('success'))
                    <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-900 rounded-r-xl shadow-xs text-xs flex items-center justify-between" role="alert">
                        <div class="flex items-center space-x-2.5">
                            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <span class="font-semibold">{{ session('success') }}</span>
                        </div>
                    </div>
                @endif

                @if (session('error'))
                    <div class="p-4 bg-rose-50 border-l-4 border-rose-500 text-rose-900 rounded-r-xl shadow-xs text-xs flex items-center justify-between" role="alert">
                        <div class="flex items-center space-x-2.5">
                            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span class="font-semibold">{{ session('error') }}</span>
                        </div>
                    </div>
                @endif

                @yield('content')
            </main>

            <!-- Footer Institucional -->
            <footer class="bg-white border-t border-slate-200 py-4 px-4 sm:px-6 lg:px-8 text-center text-xs text-slate-500 mt-auto">
                <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
                    <p>
                        <strong>Electrocentro S. A.</strong> &bull; Sistema de Información Gerencial para Seguimiento de Proyectos de Gobierno Digital (2023–2026)
                    </p>
                    <p class="text-slate-400">
                        Universidad Nacional de Huancavelica &bull; Sistemas 8vo Ciclo
                    </p>
                </div>
            </footer>
        </div>
    </div>
</body>
</html>
