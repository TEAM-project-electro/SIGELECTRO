@extends('layouts.guest')

@section('content')
<div class="min-h-screen flex flex-col justify-between bg-slate-900 bg-gradient-to-br from-brand-950 via-slate-900 to-brand-900 text-slate-100">

    <!-- Top Banner Académico / Demostrativo -->
    <header class="w-full bg-brand-900/60 backdrop-blur-sm border-b border-white/10 px-4 py-2.5">
        <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between text-xs gap-2">
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-400 text-slate-950 uppercase tracking-wide">
                    Prototipo Académico
                </span>
                <span class="text-slate-300 hidden sm:inline">Universidad Nacional de Huancavelica &bull; Sistemas (8vo Ciclo)</span>
            </div>
            <div class="flex items-center space-x-3 text-slate-300">
                <span class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>MariaDB 10.11 (3307)</span>
                </span>
                <a href="{{ route('salud') }}" class="text-brand-300 hover:text-white underline font-mono text-[11px]" target="_blank">
                    Diagnóstico API
                </a>
            </div>
        </div>
    </header>

    <!-- Contenedor Central: Panel Institucional + Formulario -->
    <main class="flex-1 flex items-center justify-center p-4 sm:p-6 lg:p-8">
        <div class="w-full max-w-5xl bg-white rounded-2xl shadow-2xl overflow-hidden grid grid-cols-1 lg:grid-cols-12 border border-slate-200/80 text-slate-800">

            <!-- Columna Izquierda: Identidad Institucional y Resumen del PGD -->
            <div class="lg:col-span-5 bg-gradient-to-br from-brand-900 via-brand-800 to-brand-900 text-white p-8 sm:p-10 flex flex-col justify-between relative overflow-hidden">
                <!-- Decoración sutil de fondo -->
                <div class="absolute -right-16 -top-16 w-56 h-56 rounded-full bg-brand-500/10 blur-2xl pointer-events-none"></div>
                <div class="absolute -left-16 -bottom-16 w-56 h-56 rounded-full bg-amber-400/10 blur-2xl pointer-events-none"></div>

                <div class="relative z-10">
                    <!-- Isotipo / Monograma Electrocentro -->
                    <div class="flex items-center space-x-3 mb-6">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-400 to-amber-500 text-brand-950 flex items-center justify-center shadow-lg shadow-amber-500/20">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <span class="block text-xl font-extrabold tracking-tight text-white leading-tight">ELECTROCENTRO S. A.</span>
                            <span class="block text-xs font-semibold text-amber-300 uppercase tracking-wider">Grupo Distriluz</span>
                        </div>
                    </div>

                    <h1 class="text-2xl font-bold tracking-tight text-white mt-4">
                        Sistema de Información Gerencial
                    </h1>
                    <p class="text-sm text-brand-100 mt-2 leading-relaxed">
                        Supervisión estratégica y seguimiento físico-financiero del Portafolio del Plan de Gobierno Digital (PGD 2023–2026).
                    </p>

                    <!-- Fichas de contexto de cartera -->
                    <div class="mt-8 space-y-3 pt-6 border-t border-white/10 text-xs">
                        <div class="flex items-center justify-between p-3 rounded-lg bg-white/5 border border-white/10">
                            <span class="text-brand-200">Cartera Estratégica:</span>
                            <span class="font-bold text-white font-mono">10 Proyectos PGD</span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-lg bg-white/5 border border-white/10">
                            <span class="text-brand-200">Presupuesto Adjudicado:</span>
                            <span class="font-bold text-amber-300 font-mono">S/ 8,623,550.00</span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-lg bg-white/5 border border-white/10">
                            <span class="text-brand-200">Hitos y Cortes Evaluados:</span>
                            <span class="font-bold text-white font-mono">26 Hitos &bull; 24 Cortes</span>
                        </div>
                    </div>
                </div>

                <div class="relative z-10 mt-8 pt-6 border-t border-white/10 text-[11px] text-brand-200/80">
                    <p>&bull; Control de perfiles por políticas en servidor (Policies)</p>
                    <p>&bull; Reglas de negocio e integridad R1 a R4</p>
                </div>
            </div>

            <!-- Columna Derecha: Formulario de Autenticación y Cuentas Demo -->
            <div class="lg:col-span-7 p-8 sm:p-10 flex flex-col justify-between bg-white"
                 x-data="{ submitting: false, showPassword: false }">
                <div>
                    <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-100">
                        <div>
                            <h2 class="text-xl font-bold text-slate-900">Iniciar Sesión</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Ingrese sus credenciales corporativas autorizadas</p>
                        </div>
                        <span class="px-2.5 py-1 text-[11px] font-semibold rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                            Portal Seguro
                        </span>
                    </div>

                    <!-- Mensajes de Estado o Error -->
                    @if (session('status'))
                        <div class="mb-5 p-3.5 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 text-xs rounded-r" role="alert">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2 shrink-0 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                <span>{{ session('status') }}</span>
                            </div>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-5 p-3.5 bg-rose-50 border-l-4 border-rose-500 text-rose-800 text-xs rounded-r" role="alert">
                            <p class="font-bold flex items-center">
                                <svg class="w-4 h-4 mr-1.5 shrink-0 text-rose-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                No se pudo autenticar:
                            </p>
                            <ul class="mt-1 list-disc list-inside space-y-0.5 text-rose-700">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- Formulario de Acceso -->
                    <form method="POST" action="{{ route('login.store') }}" class="space-y-4" @submit="submitting = true">
                        @csrf

                        <!-- Campo Usuario -->
                        <div>
                            <label for="usuario" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                                Identificador de Usuario <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative rounded-lg shadow-xs">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <input id="usuario" name="usuario" type="text" autocomplete="username" required
                                    value="{{ old('usuario') }}"
                                    class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-slate-800 placeholder-slate-400 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition focus:bg-white"
                                    placeholder="p. ej. msalas">
                            </div>
                        </div>

                        <!-- Campo Contraseña -->
                        <div>
                            <label for="clave" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 mb-1.5">
                                Contraseña <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative rounded-lg shadow-xs">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                </div>
                                <input id="clave" name="clave" :type="showPassword ? 'text' : 'password'" autocomplete="current-password" required
                                    class="w-full pl-10 pr-10 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-slate-800 placeholder-slate-400 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition focus:bg-white"
                                    placeholder="••••••••">
                                <button type="button" @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none"
                                        title="Mostrar u ocultar contraseña">
                                    <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg x-show="showPassword" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Botón de Ingreso -->
                        <div class="pt-2">
                            <button type="submit" :disabled="submitting"
                                class="w-full inline-flex items-center justify-center py-2.5 px-4 rounded-lg text-sm font-semibold text-white bg-brand-700 hover:bg-brand-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 shadow-md transition disabled:opacity-75 disabled:cursor-not-allowed">
                                <svg x-show="!submitting" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                </svg>
                                <svg x-show="submitting" x-cloak class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <span x-text="submitting ? 'Autenticando...' : 'Acceder al Tablero'">Acceder al Tablero</span>
                            </button>
                        </div>
                    </form>

                    <!-- Atajos de Demostración Rápida -->
                    <div class="mt-6 pt-5 border-t border-slate-100">
                        <div class="flex items-center justify-between mb-2.5">
                            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                                Perfiles de Demostración Académica:
                            </span>
                            <span class="text-[10px] text-slate-400">Clic para autocompletar</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <button type="button" onclick="cargarCredencial('msalas', 'Admin2026!')"
                                class="group p-2.5 text-left bg-slate-50 hover:bg-brand-50 hover:border-brand-300 border border-slate-200 rounded-lg transition">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-slate-800 group-hover:text-brand-900 font-mono">msalas</span>
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-purple-100 text-purple-800 uppercase">Admin</span>
                                </div>
                                <span class="text-[11px] text-slate-500 block mt-0.5">Planeamiento / Pleno</span>
                            </button>

                            <button type="button" onclick="cargarCredencial('aquispe', 'Resp2026!')"
                                class="group p-2.5 text-left bg-slate-50 hover:bg-brand-50 hover:border-brand-300 border border-slate-200 rounded-lg transition">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-slate-800 group-hover:text-brand-900 font-mono">aquispe</span>
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800 uppercase">Titular</span>
                                </div>
                                <span class="text-[11px] text-slate-500 block mt-0.5">Oficina de TIC</span>
                            </button>

                            <button type="button" onclick="cargarCredencial('lrojas', 'Resp2026!')"
                                class="group p-2.5 text-left bg-slate-50 hover:bg-brand-50 hover:border-brand-300 border border-slate-200 rounded-lg transition">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-slate-800 group-hover:text-brand-900 font-mono">lrojas</span>
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800 uppercase">Delegado</span>
                                </div>
                                <span class="text-[11px] text-slate-500 block mt-0.5">Oficina de TIC</span>
                            </button>

                            <button type="button" onclick="cargarCredencial('cgarcia', 'Consulta2026!')"
                                class="group p-2.5 text-left bg-slate-50 hover:bg-brand-50 hover:border-brand-300 border border-slate-200 rounded-lg transition">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-slate-800 group-hover:text-brand-900 font-mono">cgarcia</span>
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-800 uppercase">Consulta</span>
                                </div>
                                <span class="text-[11px] text-slate-500 block mt-0.5">Comité de Gobierno Digital</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-100 text-center text-xs text-slate-400">
                    Electrocentro S. A. &bull; Empresa Pública de Distribución y Comercialización Eléctrica
                </div>
            </div>
        </div>
    </main>

    <!-- Footer Institucional -->
    <footer class="w-full bg-brand-950/80 border-t border-white/10 px-4 py-3 text-center text-xs text-slate-400">
        Sistema de Información Gerencial para el Seguimiento de Cartera del PGD 2023–2026 &bull; Sede Central Huancayo, Perú
    </footer>
</div>

<script>
    function cargarCredencial(usr, pwd) {
        var u = document.getElementById('usuario');
        var c = document.getElementById('clave');
        if (u && c) {
            u.value = usr;
            c.value = pwd;
            u.focus();
        }
    }
</script>
@endsection
