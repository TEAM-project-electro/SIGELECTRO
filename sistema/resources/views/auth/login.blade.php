@extends('layouts.guest')

@section('content')
<main class="min-h-screen flex items-center justify-center bg-slate-100 px-4 py-8">
    <div class="w-full max-w-sm">
        
        <!-- Identidad Institucional y Nombre del Sistema -->
        <div class="text-center mb-5">
            <div class="inline-flex items-center justify-center bg-white p-2.5 rounded-xl border border-slate-200/80 shadow-xs mb-3">
                <img src="{{ asset('images/logo-electrocentro.jpg') }}" alt="Logo Electrocentro" class="h-9 w-auto object-contain">
            </div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900">SIGELECTRO</h1>
            <p class="text-xs font-bold tracking-wider text-brand-900 uppercase mt-0.5">ELECTROCENTRO S. A.</p>
            <p class="text-xs text-slate-500 mt-1">Sistema de Información Gerencial · PGD</p>
        </div>

        <!-- Tarjeta de Acceso -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-7" x-data="{ enviando: false }">
            <h2 class="text-base font-bold text-slate-900 mb-4">Iniciar Sesión</h2>

            @if ($errors->any())
                <div class="mb-4 p-3 bg-rose-50 border border-rose-200 text-rose-800 text-xs rounded-lg" role="alert">
                    @foreach ($errors->all() as $error)
                        <p class="font-medium">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="space-y-4" @submit="enviando = true">
                @csrf
                <div>
                    <label for="usuario" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Usuario</label>
                    <input id="usuario" name="usuario" type="text" autocomplete="username" required autofocus
                           value="{{ old('usuario') }}"
                           placeholder="p. ej. msalas"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition">
                </div>
                <div>
                    <label for="clave" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">Contraseña</label>
                    <input id="clave" name="clave" type="password" autocomplete="current-password" required
                           placeholder="••••••••••••"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500 transition">
                </div>
                <button type="submit" :disabled="enviando"
                        class="w-full py-2.5 rounded-lg text-sm font-bold text-white bg-brand-800 hover:bg-brand-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 shadow-xs transition disabled:opacity-70">
                    <span x-text="enviando ? 'Ingresando…' : 'Ingresar'">Ingresar</span>
                </button>
            </form>
        </div>

        <p class="text-center text-[11px] text-slate-400 mt-4">Prototipo académico · Datos demostrativos</p>
    </div>
</main>
@endsection
