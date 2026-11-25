@extends('layouts.app')

@section('title', 'Cambiar contraseña')

@section('content')
<div class="max-w-lg mx-auto space-y-6" x-data="{ enviando: false }">
    <div class="flex items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Cambiar contraseña</h1>
            <p class="text-sm text-slate-600 mt-1">
                Cuenta activa: <span translate="no" class="font-mono font-semibold text-brand-800">{{ Auth::user()->usuario }}</span> ({{ Auth::user()->perfil }})
            </p>
        </div>
        <a href="{{ route('tablero') }}" class="sig-btn-secondary">Volver</a>
    </div>

    @if ($errors->any())
        <x-alert type="error">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-alert>
    @endif

    <form method="POST" action="{{ route('perfil.clave.update') }}" autocomplete="off" @submit="enviando = true"
          class="sig-panel p-6 space-y-5">
        @csrf
        @method('PUT')

        <div>
            <label for="clave_actual" class="block text-xs font-semibold text-slate-700 mb-1.5">Contraseña actual</label>
            <input id="clave_actual" name="clave_actual" type="password" autocomplete="current-password" required
                   placeholder="Ingrese su contraseña vigente…"
                   class="sig-input">
        </div>

        <div>
            <label for="clave_nueva" class="block text-xs font-semibold text-slate-700 mb-1.5">Nueva contraseña privada</label>
            <input id="clave_nueva" name="clave_nueva" type="password" minlength="12" maxlength="72" autocomplete="new-password" required
                   placeholder="Entre 12 y 72 caracteres…"
                   class="sig-input">
            <p class="text-[11px] text-slate-500 mt-1">Use entre 12 y 72 caracteres sin espacios al inicio ni al final.</p>
        </div>

        <div>
            <label for="clave_nueva_confirmation" class="block text-xs font-semibold text-slate-700 mb-1.5">Confirmar nueva contraseña</label>
            <input id="clave_nueva_confirmation" name="clave_nueva_confirmation" type="password" minlength="12" maxlength="72" autocomplete="new-password" required
                   placeholder="Repita la nueva contraseña…"
                   class="sig-input">
        </div>

        <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
            <a href="{{ route('tablero') }}" class="sig-btn-secondary">Cancelar</a>
            <button type="submit" :disabled="enviando" class="sig-btn-primary">
                <span x-text="enviando ? 'Actualizando…' : 'Actualizar contraseña'">Actualizar contraseña</span>
            </button>
        </div>
    </form>
</div>
@endsection
