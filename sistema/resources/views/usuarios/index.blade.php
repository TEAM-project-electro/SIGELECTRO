@extends('layouts.app')

@section('title', 'Usuarios y Perfiles')

@section('content')
<div class="space-y-6" x-data="{ modalNuevo: false, usuarioEdit: null }" @keydown.escape.window="modalNuevo = false; usuarioEdit = null">

    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Administración de Usuarios y Perfiles</h1>
            <p class="text-sm text-slate-600 mt-1 tabular-nums">
                {{ $usuarios->where('activo', 1)->count() }} activos de {{ $usuarios->count() }} usuarios registrados
            </p>
        </div>

        @can('create', \App\Models\Responsable::class)
            <button type="button" @click="modalNuevo = true" class="sig-btn-primary self-start sm:self-auto">
                <svg class="w-4 h-4 text-brand-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Nuevo usuario</span>
            </button>
        @endcan
    </div>

    @if($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-900 text-xs" role="alert" aria-live="assertive">
            <p class="font-bold mb-1">Verifique los datos ingresados:</p>
            <ul class="list-disc list-inside space-y-0.5 text-rose-800">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="sig-panel overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-[13px]">
                <thead class="bg-slate-50/90 text-slate-600 text-xs">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">Usuario</th>
                        <th class="px-4 py-3 text-left font-semibold">Nombre</th>
                        <th class="px-4 py-3 text-left font-semibold">Área</th>
                        <th class="px-4 py-3 text-center font-semibold">Perfil</th>
                        <th class="px-4 py-3 text-center font-semibold">Estado</th>
                        <th class="px-5 py-3 text-right font-semibold"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                    @foreach($usuarios as $usr)
                        <tr class="hover:bg-brand-50/35 transition-colors duration-150">
                            <td translate="no" class="px-5 py-3.5 font-mono text-xs font-bold text-brand-900 whitespace-nowrap">
                                {{ $usr->usuario }}
                            </td>
                            <td class="px-4 py-3.5 font-semibold text-slate-900 whitespace-nowrap">
                                {{ $usr->nombre }}
                            </td>
                            <td class="px-4 py-3.5 text-slate-600 whitespace-nowrap">
                                {{ $usr->area }}
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                                    @if($usr->perfil === 'Administrador') bg-brand-50 text-brand-900 border border-brand-200
                                    @elseif($usr->perfil === 'Responsable') bg-sky-50 text-sky-800 border border-sky-200
                                    @else bg-slate-100 text-slate-700 border border-slate-200 @endif">
                                    {{ $usr->perfil }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                @if($usr->activo)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-energy-50 text-energy-700 border border-energy-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-energy-500" aria-hidden="true"></span>
                                        Activo
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-500 border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400" aria-hidden="true"></span>
                                        Inactivo
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                @can('update', $usr)
                                    <button type="button"
                                            @click="usuarioEdit = {{ json_encode($usr) }}"
                                            class="text-xs font-semibold text-brand-700 hover:text-brand-900 hover:underline">
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

    {{-- Modal: Nuevo Usuario --}}
    <div x-show="modalNuevo" x-cloak
         class="fixed inset-0 bg-slate-950/60 backdrop-blur-[1px] z-50 flex items-center justify-center p-4 overscroll-contain"
         role="dialog" aria-modal="true" aria-labelledby="titulo-modal-nuevo-usr">
        <div class="bg-white rounded-xl max-w-lg w-full p-6 shadow-elevated border border-slate-200 space-y-4"
             @click.outside="modalNuevo = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 id="titulo-modal-nuevo-usr" class="text-base font-bold text-slate-900">Nuevo Usuario</h3>
                <button type="button" @click="modalNuevo = false" aria-label="Cerrar ventana"
                        class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('usuarios.store') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label for="nuevo_usr_nombre" class="block text-xs font-semibold text-slate-700 mb-1">Nombre completo <span class="text-rose-500">*</span></label>
                        <input id="nuevo_usr_nombre" name="nombre" value="{{ old('nombre') }}" required maxlength="100" autocomplete="off" class="sig-input text-xs py-2">
                    </div>

                    <div>
                        <label for="nuevo_usr_area" class="block text-xs font-semibold text-slate-700 mb-1">Área <span class="text-rose-500">*</span></label>
                        <input id="nuevo_usr_area" name="area" value="{{ old('area') }}" required maxlength="80" autocomplete="off" class="sig-input text-xs py-2">
                    </div>

                    <div>
                        <label for="nuevo_usr_login" class="block text-xs font-semibold text-slate-700 mb-1">Usuario <span class="text-rose-500">*</span></label>
                        <input id="nuevo_usr_login" name="usuario" value="{{ old('usuario') }}" required maxlength="30" spellcheck="false" autocomplete="off" class="sig-input font-mono text-xs py-2">
                    </div>

                    <div>
                        <label for="nuevo_usr_perfil" class="block text-xs font-semibold text-slate-700 mb-1">Perfil <span class="text-rose-500">*</span></label>
                        <select id="nuevo_usr_perfil" name="perfil" required class="sig-input text-xs py-2">
                            <option value="Responsable">Responsable</option>
                            <option value="Consulta">Consulta</option>
                            <option value="Administrador">Administrador</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="nuevo_usr_clave" class="block text-xs font-semibold text-slate-700 mb-1">Contraseña privada (mínimo 10 caracteres) <span class="text-rose-500">*</span></label>
                        <input id="nuevo_usr_clave" name="clave" type="password" minlength="10" maxlength="128" required autocomplete="new-password" class="sig-input text-xs py-2">
                    </div>

                    <div class="sm:col-span-2">
                        <input type="hidden" name="activo" value="1">
                        <label for="nuevo_usr_motivo" class="block text-xs font-semibold text-slate-700 mb-1">Motivo de registro (opcional)</label>
                        <input id="nuevo_usr_motivo" name="motivo_cambio" maxlength="200" autocomplete="off" class="sig-input text-xs py-2">
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="modalNuevo = false" class="sig-btn-secondary">Cancelar</button>
                    <button type="submit" class="sig-btn-primary">Crear usuario</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal: Editar Usuario --}}
    <div x-show="usuarioEdit !== null" x-cloak
         class="fixed inset-0 bg-slate-950/60 backdrop-blur-[1px] z-50 flex items-center justify-center p-4 overscroll-contain"
         role="dialog" aria-modal="true" aria-labelledby="titulo-modal-edit-usr">
        <div class="bg-white rounded-xl max-w-lg w-full p-6 shadow-elevated border border-slate-200 space-y-4"
             @click.outside="usuarioEdit = null">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 id="titulo-modal-edit-usr" class="text-base font-bold text-slate-900">
                    Editar usuario: <span translate="no" class="font-mono text-brand-800" x-text="usuarioEdit ? usuarioEdit.usuario : ''"></span>
                </h3>
                <button type="button" @click="usuarioEdit = null" aria-label="Cerrar ventana"
                        class="p-1 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <template x-if="usuarioEdit">
                <form method="POST" :action="'/usuarios/' + usuarioEdit.id_responsable" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div>
                            <label for="edit_usr_nombre" class="block text-xs font-semibold text-slate-700 mb-1">Nombre completo <span class="text-rose-500">*</span></label>
                            <input id="edit_usr_nombre" name="nombre" :value="usuarioEdit.nombre" required maxlength="100" autocomplete="off" class="sig-input text-xs py-2">
                        </div>

                        <div>
                            <label for="edit_usr_area" class="block text-xs font-semibold text-slate-700 mb-1">Área <span class="text-rose-500">*</span></label>
                            <input id="edit_usr_area" name="area" :value="usuarioEdit.area" required maxlength="80" autocomplete="off" class="sig-input text-xs py-2">
                        </div>

                        <div>
                            <label for="edit_usr_login" class="block text-xs font-semibold text-slate-700 mb-1">Usuario <span class="text-rose-500">*</span></label>
                            <input id="edit_usr_login" name="usuario" :value="usuarioEdit.usuario" required maxlength="30" spellcheck="false" autocomplete="off" class="sig-input font-mono text-xs py-2">
                        </div>

                        <div>
                            <label for="edit_usr_perfil" class="block text-xs font-semibold text-slate-700 mb-1">Perfil <span class="text-rose-500">*</span></label>
                            <select id="edit_usr_perfil" name="perfil" required class="sig-input text-xs py-2">
                                <option value="Administrador" :selected="usuarioEdit.perfil === 'Administrador'">Administrador</option>
                                <option value="Responsable" :selected="usuarioEdit.perfil === 'Responsable'">Responsable</option>
                                <option value="Consulta" :selected="usuarioEdit.perfil === 'Consulta'">Consulta</option>
                            </select>
                        </div>

                        <div>
                            <label for="edit_usr_activo" class="block text-xs font-semibold text-slate-700 mb-1">Estado <span class="text-rose-500">*</span></label>
                            <select id="edit_usr_activo" name="activo" required class="sig-input text-xs py-2">
                                <option value="1" :selected="usuarioEdit.activo == 1">Activo</option>
                                <option value="0" :selected="usuarioEdit.activo == 0">Inactivo</option>
                            </select>
                        </div>

                        <div>
                            <label for="edit_usr_clave" class="block text-xs font-semibold text-slate-700 mb-1">Nueva contraseña (opcional)</label>
                            <input id="edit_usr_clave" name="clave" type="password" minlength="10" maxlength="128" autocomplete="new-password"
                                   placeholder="Dejar en blanco…" class="sig-input text-xs py-2">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="edit_usr_motivo" class="block text-xs font-semibold text-slate-700 mb-1">
                                Motivo del cambio <span class="text-slate-500 font-normal">(requerido al desactivar o cambiar perfil de administrador)</span>
                            </label>
                            <input id="edit_usr_motivo" name="motivo_cambio" maxlength="200" autocomplete="off" class="sig-input text-xs py-2">
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                        <button type="button" @click="usuarioEdit = null" class="sig-btn-secondary">Cancelar</button>
                        <button type="submit" class="sig-btn-primary">Guardar cambios</button>
                    </div>
                </form>
            </template>
        </div>
    </div>

</div>
@endsection
