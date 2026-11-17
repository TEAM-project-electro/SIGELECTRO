@extends('layouts.app')

@section('title', 'Usuarios y Perfiles')

@section('content')
<div class="space-y-5" x-data="{ modalNuevo: false, usuarioEdit: null }">

    <!-- Encabezado -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Administración de Usuarios y Perfiles</h1>
            <p class="text-xs text-slate-500 mt-0.5">
                {{ $usuarios->where('activo', 1)->count() }} activos de {{ $usuarios->count() }} usuarios registrados
            </p>
        </div>

        @can('create', \App\Models\Responsable::class)
            <button type="button" @click="modalNuevo = true"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-semibold text-white bg-brand-800 hover:bg-brand-900 transition self-start sm:self-auto">
                + Nuevo usuario
            </button>
        @endcan
    </div>

    @if($errors->any())
        <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-lg text-rose-900 text-xs" role="alert">
            <p class="font-bold mb-1">Verifique los datos ingresados:</p>
            <ul class="list-disc list-inside space-y-0.5 text-rose-800">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Tabla de Usuarios -->
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-xs">
                <thead class="bg-slate-50 text-slate-600 uppercase font-bold text-[11px]">
                    <tr>
                        <th class="px-4 py-3 text-left">Usuario</th>
                        <th class="px-4 py-3 text-left">Nombre</th>
                        <th class="px-4 py-3 text-left">Área</th>
                        <th class="px-4 py-3 text-center">Perfil</th>
                        <th class="px-4 py-3 text-center">Estado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                    @foreach($usuarios as $usr)
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-4 py-3 font-mono font-bold text-brand-900 whitespace-nowrap">
                                {{ $usr->usuario }}
                            </td>
                            <td class="px-4 py-3 font-semibold text-slate-900 whitespace-nowrap">
                                {{ $usr->nombre }}
                            </td>
                            <td class="px-4 py-3 text-slate-600 whitespace-nowrap">
                                {{ $usr->area }}
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold
                                    @if($usr->perfil === 'Administrador') bg-purple-50 text-purple-800 border border-purple-200
                                    @elseif($usr->perfil === 'Responsable') bg-blue-50 text-blue-800 border border-blue-200
                                    @else bg-slate-100 text-slate-700 border border-slate-200 @endif">
                                    {{ $usr->perfil }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                @if($usr->activo)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        Activo
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-500 border border-slate-200">
                                        Inactivo
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                @can('update', $usr)
                                    <button type="button"
                                        @click="usuarioEdit = {{ json_encode($usr) }}"
                                        class="text-xs font-semibold text-brand-700 hover:text-brand-900">
                                        Editar
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal: Nuevo Usuario -->
    <div x-show="modalNuevo" x-cloak
         class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-lg w-full p-5 shadow-lg border border-slate-200 space-y-4"
             @click.away="modalNuevo = false">
            <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-900">Nuevo Usuario</h3>
                <button type="button" @click="modalNuevo = false" class="text-slate-400 hover:text-slate-700 text-lg leading-none">&times;</button>
            </div>

            <form method="POST" action="{{ route('usuarios.store') }}" class="space-y-3.5">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nombre completo <span class="text-rose-500">*</span></label>
                        <input name="nombre" value="{{ old('nombre') }}" required maxlength="100"
                            class="w-full text-xs border border-slate-300 rounded-lg px-3 py-2 focus:border-brand-600 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Área <span class="text-rose-500">*</span></label>
                        <input name="area" value="{{ old('area') }}" required maxlength="80"
                            class="w-full text-xs border border-slate-300 rounded-lg px-3 py-2 focus:border-brand-600 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Usuario <span class="text-rose-500">*</span></label>
                        <input name="usuario" value="{{ old('usuario') }}" required maxlength="30"
                            class="w-full text-xs font-mono border border-slate-300 rounded-lg px-3 py-2 focus:border-brand-600 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Perfil <span class="text-rose-500">*</span></label>
                        <select name="perfil" required
                            class="w-full text-xs border border-slate-300 rounded-lg px-3 py-2 focus:border-brand-600 focus:outline-none">
                            <option value="Responsable">Responsable</option>
                            <option value="Consulta">Consulta</option>
                            <option value="Administrador">Administrador</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Contraseña (mínimo 10 caracteres) <span class="text-rose-500">*</span></label>
                        <input name="clave" type="password" minlength="10" maxlength="128" required autocomplete="new-password"
                            class="w-full text-xs border border-slate-300 rounded-lg px-3 py-2 focus:border-brand-600 focus:outline-none">
                    </div>

                    <div class="sm:col-span-2">
                        <input type="hidden" name="activo" value="1">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Motivo de registro (opcional)</label>
                        <input name="motivo_cambio" maxlength="200"
                            class="w-full text-xs border border-slate-300 rounded-lg px-3 py-2 focus:border-brand-600 focus:outline-none">
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="modalNuevo = false"
                        class="px-3.5 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-4 py-1.5 bg-brand-800 hover:bg-brand-900 text-white rounded-lg text-xs font-semibold">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Editar Usuario -->
    <div x-show="usuarioEdit !== null" x-cloak
         class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-lg w-full p-5 shadow-lg border border-slate-200 space-y-4"
             @click.away="usuarioEdit = null">
            <div class="flex items-center justify-between pb-2.5 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-900">
                    Editar usuario: <span class="font-mono text-brand-800" x-text="usuarioEdit ? usuarioEdit.usuario : ''"></span>
                </h3>
                <button type="button" @click="usuarioEdit = null" class="text-slate-400 hover:text-slate-700 text-lg leading-none">&times;</button>
            </div>

            <template x-if="usuarioEdit">
                <form method="POST" :action="'/usuarios/' + usuarioEdit.id_responsable" class="space-y-3.5">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nombre completo <span class="text-rose-500">*</span></label>
                            <input name="nombre" :value="usuarioEdit.nombre" required maxlength="100"
                                class="w-full text-xs border border-slate-300 rounded-lg px-3 py-2 focus:border-brand-600 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Área <span class="text-rose-500">*</span></label>
                            <input name="area" :value="usuarioEdit.area" required maxlength="80"
                                class="w-full text-xs border border-slate-300 rounded-lg px-3 py-2 focus:border-brand-600 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Usuario <span class="text-rose-500">*</span></label>
                            <input name="usuario" :value="usuarioEdit.usuario" required maxlength="30"
                                class="w-full text-xs font-mono border border-slate-300 rounded-lg px-3 py-2 focus:border-brand-600 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Perfil <span class="text-rose-500">*</span></label>
                            <select name="perfil" required
                                class="w-full text-xs border border-slate-300 rounded-lg px-3 py-2 focus:border-brand-600 focus:outline-none">
                                <option value="Administrador" :selected="usuarioEdit.perfil === 'Administrador'">Administrador</option>
                                <option value="Responsable" :selected="usuarioEdit.perfil === 'Responsable'">Responsable</option>
                                <option value="Consulta" :selected="usuarioEdit.perfil === 'Consulta'">Consulta</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Estado <span class="text-rose-500">*</span></label>
                            <select name="activo" required
                                class="w-full text-xs border border-slate-300 rounded-lg px-3 py-2 focus:border-brand-600 focus:outline-none">
                                <option value="1" :selected="usuarioEdit.activo == 1">Activo</option>
                                <option value="0" :selected="usuarioEdit.activo == 0">Inactivo</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nueva contraseña (opcional)</label>
                            <input name="clave" type="password" minlength="10" maxlength="128" autocomplete="new-password"
                                placeholder="Sin cambios"
                                class="w-full text-xs border border-slate-300 rounded-lg px-3 py-2 focus:border-brand-600 focus:outline-none">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                Motivo del cambio <span class="text-slate-400 font-normal">(requerido al desactivar o cambiar perfil de administrador)</span>
                            </label>
                            <input name="motivo_cambio" maxlength="200"
                                class="w-full text-xs border border-slate-300 rounded-lg px-3 py-2 focus:border-brand-600 focus:outline-none">
                        </div>
                    </div>

                    <div class="pt-2 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button type="button" @click="usuarioEdit = null"
                            class="px-3.5 py-1.5 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-4 py-1.5 bg-brand-800 hover:bg-brand-900 text-white rounded-lg text-xs font-semibold">
                            Actualizar
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>

</div>
@endsection
