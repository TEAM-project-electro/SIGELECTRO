@extends('layouts.guest')

@section('content')
<main class="min-h-screen flex items-center justify-center px-4 py-10 bg-canvas">
    <div class="w-full max-w-[800px]">
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-elevated overflow-hidden">
            <div class="h-1 w-full electro-rail" aria-hidden="true"></div>

            <div class="grid grid-cols-1 md:grid-cols-12">
                {{-- Panel institucional --}}
                <div class="md:col-span-5 bg-brand-950 blueprint-grid text-white p-7 sm:p-8 flex flex-col justify-between">
                    <div>
                        <div class="inline-flex items-center bg-white rounded-lg px-3 py-2 shadow-xs">
                            <img src="{{ asset('images/logo-electrocentro.jpg') }}"
                                 alt="ELECTROCENTRO S. A."
                                 width="156"
                                 height="30"
                                 fetchpriority="high"
                                 class="h-7 w-auto object-contain">
                        </div>

                        <h1 translate="no" class="text-2xl sm:text-[26px] font-bold tracking-tight text-white mt-7">SIGELECTRO</h1>
                        <p class="text-[13px] text-slate-300 leading-relaxed mt-2.5">
                            Sistema de Información Gerencial para el seguimiento de proyectos, hitos y ejecución presupuestal del Plan de Gobierno Digital.
                        </p>
                    </div>

                    <div class="mt-8 pt-4 border-t border-white/10 flex items-center gap-2 text-[11px] text-slate-400">
                        <span class="w-2 h-2 rounded-full bg-energy-500 shrink-0" aria-hidden="true"></span>
                        <span>Prototipo académico con datos demostrativos</span>
                    </div>
                </div>

                {{-- Formulario de acceso sin autocompletado ni previsualización de clave --}}
                <div class="md:col-span-7 p-7 sm:p-10 flex flex-col justify-center" x-data="{ enviando: false }">
                    <div class="mb-6">
                        <h2 class="text-xl font-bold text-slate-900">Iniciar Sesión</h2>
                        <p class="text-xs text-slate-500 mt-1">Ingrese su cuenta autorizada para acceder a la cartera PGD.</p>
                    </div>

                    @if ($errors->any())
                        <div class="mb-5 p-3.5 bg-rose-50 border border-rose-200 text-rose-900 text-xs rounded-lg" role="alert" aria-live="assertive">
                            @foreach ($errors->all() as $error)
                                <p class="font-medium">{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.store') }}" autocomplete="off" class="space-y-4" @submit="enviando = true">
                        @csrf
                        <div>
                            <label for="usuario" class="block text-xs font-semibold text-slate-700 mb-1.5">Usuario</label>
                            <input id="usuario" name="usuario" type="text" autocomplete="off" spellcheck="false" required
                                   value="{{ old('usuario') }}"
                                   @if(!old('usuario')) readonly onfocus="this.removeAttribute('readonly')" @endif
                                   placeholder="Ej. msalas…"
                                   class="sig-input py-2.5 px-3.5">
                        </div>

                        <div>
                            <label for="clave" class="block text-xs font-semibold text-slate-700 mb-1.5">Contraseña</label>
                            <input id="clave" name="clave" type="password"
                                   autocomplete="new-password" required
                                   readonly onfocus="this.removeAttribute('readonly')"
                                   placeholder="Ingrese su contraseña…"
                                   class="sig-input py-2.5 px-3.5">
                        </div>

                        <div class="pt-2">
                            <button type="submit" :disabled="enviando"
                                    class="w-full sig-btn-primary py-2.5 text-sm">
                                <svg x-show="enviando" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                                </svg>
                                <span x-text="enviando ? 'Ingresando…' : 'Ingresar'">Ingresar</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
