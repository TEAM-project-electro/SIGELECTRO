@extends('layouts.guest')

@section('content')
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <!-- Encabezado Institucional -->
        <div class="text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-blue-900 text-white shadow-lg shadow-blue-900/30 mb-4">
                <svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                ELECTROCENTRO S. A.
            </h1>
            <p class="text-sm font-medium text-blue-800 tracking-wide mt-1">
                Sistema de Información Gerencial (SIG)
            </p>
            <p class="text-xs text-slate-500 mt-1">
                Seguimiento de Cartera de Gobierno Digital
            </p>
        </div>

        <!-- Tarjeta de Login -->
        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-6 shadow-xl shadow-slate-200/50 rounded-2xl sm:px-10 border border-slate-200/80">
                <h2 class="text-lg font-semibold text-slate-800 mb-6 border-b border-slate-100 pb-3">
                    Iniciar Sesión
                </h2>

                @if (session('status'))
                    <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg" role="alert">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-700 text-sm rounded-lg" role="alert">
                        <p class="font-medium">No se pudo iniciar sesión:</p>
                        <ul class="mt-1 list-disc list-inside text-xs">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="usuario" class="block text-sm font-medium text-slate-700">
                            Usuario del Sistema
                        </label>
                        <div class="mt-1 relative">
                            <input id="usuario" name="usuario" type="text" autocomplete="username" required
                                value="{{ old('usuario') }}"
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent text-sm transition"
                                placeholder="p. ej. msalas">
                        </div>
                    </div>

                    <div>
                        <label for="clave" class="block text-sm font-medium text-slate-700">
                            Contraseña
                        </label>
                        <div class="mt-1 relative">
                            <input id="clave" name="clave" type="password" autocomplete="current-password" required
                                class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent text-sm transition"
                                placeholder="••••••••">
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center text-slate-600 cursor-pointer">
                            <input type="checkbox" name="remember" class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                            <span class="ml-2 text-xs text-slate-600 select-none">Recordar en este equipo</span>
                        </label>
                    </div>

                    <div>
                        <button type="submit"
                            class="w-full flex justify-center items-center py-2.5 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-900 hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-600 transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                            Ingresar al SIG
                        </button>
                    </div>
                </form>

                <!-- Atajos rápidos para demostración académica -->
                <div class="mt-6 pt-5 border-t border-slate-100">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                        Perfiles de Demostración:
                    </p>
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <button type="button" onclick="cargarCredencial('msalas', 'Admin2026!')"
                            class="p-2 text-left bg-slate-50 hover:bg-blue-50 hover:border-blue-200 border border-slate-200 rounded-md transition">
                            <span class="font-bold text-slate-700 block">msalas</span>
                            <span class="text-[11px] text-blue-700">Administrador</span>
                        </button>
                        <button type="button" onclick="cargarCredencial('aquispe', 'Resp2026!')"
                            class="p-2 text-left bg-slate-50 hover:bg-blue-50 hover:border-blue-200 border border-slate-200 rounded-md transition">
                            <span class="font-bold text-slate-700 block">aquispe</span>
                            <span class="text-[11px] text-emerald-700">Responsable TIC</span>
                        </button>
                        <button type="button" onclick="cargarCredencial('lrojas', 'Resp2026!')"
                            class="p-2 text-left bg-slate-50 hover:bg-blue-50 hover:border-blue-200 border border-slate-200 rounded-md transition">
                            <span class="font-bold text-slate-700 block">lrojas</span>
                            <span class="text-[11px] text-emerald-700">Responsable TIC</span>
                        </button>
                        <button type="button" onclick="cargarCredencial('cgarcia', 'Consulta2026!')"
                            class="p-2 text-left bg-slate-50 hover:bg-blue-50 hover:border-blue-200 border border-slate-200 rounded-md transition">
                            <span class="font-bold text-slate-700 block">cgarcia</span>
                            <span class="text-[11px] text-amber-700">Consulta Gerencia</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer con aviso legal -->
            <p class="text-center text-xs text-slate-500 mt-6">
                SIG Electrocentro S. A. &copy; 2026 &bull; Portafolio de Gobierno Digital
            </p>
        </div>
    </div>

    <script>
        function cargarCredencial(usr, pwd) {
            document.getElementById('usuario').value = usr;
            document.getElementById('clave').value = pwd;
        }
    </script>
@endsection
