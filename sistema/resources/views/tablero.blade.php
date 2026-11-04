@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
            <h1 class="text-2xl font-bold text-slate-900">Tablero Gerencial de Seguimiento</h1>
            <p class="text-sm text-slate-500 mt-1">
                Bienvenido, <span class="font-semibold text-slate-800">{{ $usuario->nombre }}</span> ({{ $usuario->perfil }} - {{ $usuario->area }}).
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="text-xs uppercase font-bold text-slate-400">Cartera PGD</div>
                <div class="text-2xl font-bold text-slate-800 mt-2">10 Proyectos</div>
                <div class="text-xs text-slate-500 mt-1">S/ 8,623,550.00 presupuestados</div>
            </div>
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="text-xs uppercase font-bold text-slate-400">Tu Perfil Activo</div>
                <div class="text-2xl font-bold text-blue-900 mt-2">{{ $usuario->perfil }}</div>
                <div class="text-xs text-slate-500 mt-1">Control de acceso en servidor</div>
            </div>
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                <div class="text-xs uppercase font-bold text-slate-400">Motor de Base de Datos</div>
                <div class="text-2xl font-bold text-emerald-700 mt-2">MariaDB 10.11</div>
                <div class="text-xs text-slate-500 mt-1">Triggers y procedimientos operativos</div>
            </div>
        </div>
    </div>
@endsection
