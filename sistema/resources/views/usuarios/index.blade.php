@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="{ modalNuevo: false, usuarioEdit: null }">

    <!-- Encabezado de Página -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-purple-50 text-purple-800 border border-purple-200">
                    GOBERNANZA Y SEGURIDAD
                </span>
                <span class="text-xs text-slate-400">&bull; Control de Acceso RBAC</span>
            </div>
            <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                Administración de Usuarios y Perfiles
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Segregación estricta de privilegios: Administrador, Responsable y Consulta (Decisión 5).
            </p>
        </div>

        @can('create', \App\Models\Responsable::class)
            <button type="button" @click="modalNuevo = true"
                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-semibold text-white bg-brand-700 hover:bg-brand-800 shadow-xs transition self-start sm:self-auto">
                <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                </svg>
                <span>Nuevo Usuario</span>
            </button>
        @endcan
    </div>

    <!-- Alertas de Error -->
    @if($errors->any())
        <div class="p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-xl text-rose-900 text-xs shadow-xs" role="alert">
            <p class="font-bold flex items-center mb-1">
                <svg class="w-4 h-4 mr-1.5 text-rose-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                Ocurrió un error en la operación:
            </p>
            <ul class="list-disc list-inside space-y-0.5 text-rose-800 pl-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Tarjetas de Resumen de Cuentas -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200/90 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 block">Total Usuarios</span>
            <div class="text-2xl font-extrabold text-slate-900 mt-1 tabular-nums">{{ $usuarios->count() }}</div>
            <span class="text-[11px] text-slate-400 mt-0.5 block">Cuentas institucionales</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-purple-200/80 bg-purple-50/20 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-purple-900 block">Administradores</span>
            <div class="text-2xl font-extrabold text-purple-800 mt-1 tabular-nums">{{ $usuarios->where('perfil', 'Administrador')->count() }}</div>
            <span class="text-[11px] text-purple-600 mt-0.5 block">Gestión total protegida</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-emerald-200/80 bg-emerald-50/20 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-900 block">Responsables</span>
            <div class="text-2xl font-extrabold text-emerald-700 mt-1 tabular-nums">{{ $usuarios->where('perfil', 'Responsable')->count() }}</div>
            <span class="text-[11px] text-emerald-600 mt-0.5 block">Gestores de proyectos TIC</span>
        </div>
        <div class="bg-white p-4 rounded-xl border border-amber-200/80 bg-amber-50/20 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-amber-900 block">Consulta</span>
            <div class="text-2xl font-extrabold text-amber-700 mt-1 tabular-nums">{{ $usuarios->where('perfil', 'Consulta')->count() }}</div>
            <span class="text-[11px] text-amber-600 mt-0.5 block">Solo lectura / Directorio</span>
        </div>
    </div>

    <!-- Tabla Principal de Usuarios -->
    <div class="bg-white rounded-xl border border-slate-200/90 shadow-xs overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-200/80 flex flex-wrap items-center justify-between gap-3 bg-slate-50/50">
            <div>
                <h2 class="text-base font-extrabold text-slate-900 tracking-tight">Directorio de Cuentas Autorizadas</h2>
                <p class="text-xs text-slate-500">Credenciales cifradas con Bcrypt. La conexión lectora nunca lee el hash (A05).</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 border border-slate-200">
                {{ $usuarios->where('activo', 1)->count() }} activos / {{ $usuarios->count() }} total
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-xs">
                <thead class="bg-slate-100/80 text-slate-700 uppercase font-bold text-[11px] tracking-wider">
                    <tr>
                        <th class="px-4 py-3.5 text-center">ID</th>
                        <th class="px-4 py-3.5 text-left">Usuario (Login)</th>
                        <th class="px-4 py-3.5 text-left">Nombre Completo</th>
                        <th class="px-4 py-3.5 text-left">Área / Dependencia</th>
                        <th class="px-4 py-3.5 text-center">Perfil de Acceso</th>
                        <th class="px-4 py-3.5 text-center">Estado</th>
                        <th class="px-4 py-3.5 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                    @foreach($usuarios as $usr)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="px-4 py-3.5 text-center font-mono font-bold text-slate-500">
                                #{{ $usr->id_responsable }}
                            </td>
                            <td class="px-4 py-3.5 font-mono font-bold text-brand-900 whitespace-nowrap">
                                {{ $usr->usuario }}
                            </td>
                            <td class="px-4 py-3.5 font-semibold text-slate-900 whitespace-nowrap">
                                {{ $usr->nombre }}
                            </td>
                            <td class="px-4 py-3.5 text-slate-600 whitespace-nowrap">
                                {{ $usr->area }}
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase tracking-wider
                                    @if($usr->perfil === 'Administrador') bg-purple-50 text-purple-800 border border-purple-200
                                    @elseif($usr->perfil === 'Responsable') bg-emerald-50 text-emerald-800 border border-emerald-200
                                    @else bg-amber-50 text-amber-800 border border-amber-200 @endif">
                                    {{ $usr->perfil }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                @if($usr->activo)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                        Activo
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-500 border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400 mr-1.5"></span>
                                        Inactivo
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                @can('update', $usr)
                                    <button type="button"
                                        @click="usuarioEdit = {{ json_encode($usr) }}"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-brand-700 hover:text-brand-900 hover:bg-brand-50 rounded transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                        <span>Editar</span>
                                    </button>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">Sin permisos</span>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODAL: NUEVO USUARIO                                         -->
    <!-- ============================================================ -->
    <div x-show="modalNuevo" x-cloak
         class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-slate-200/90 space-y-5"
             @click.away="modalNuevo = false">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-brand-600"></span>
                    <h3 class="text-base font-extrabold text-slate-900">Registrar Nuevo Usuario</h3>
                </div>
                <button type="button" @click="modalNuevo = false" class="text-slate-400 hover:text-slate-700 text-lg leading-none">&times;</button>
            </div>

            <form method="POST" action="{{ route('usuarios.store') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Nombre Completo <span class="text-rose-500">*</span>
                        </label>
                        <input name="nombre" value="{{ old('nombre') }}" required maxlength="100" placeholder="p. ej. Carlos Gómez"
                            class="w-full text-xs sm:text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Área / Gerencia <span class="text-rose-500">*</span>
                        </label>
                        <input name="area" value="{{ old('area') }}" required maxlength="80" placeholder="p. ej. TIC o Planeamiento"
                            class="w-full text-xs sm:text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Usuario de Acceso <span class="text-rose-500">*</span>
                        </label>
                        <input name="usuario" value="{{ old('usuario') }}" required maxlength="30" placeholder="p. ej. cgomez"
                            class="w-full text-xs sm:text-sm font-mono border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Perfil Institucional <span class="text-rose-500">*</span>
                        </label>
                        <select name="perfil" required
                            class="w-full text-xs sm:text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500 font-semibold">
                            <option value="Responsable">Responsable (Gestor TIC)</option>
                            <option value="Consulta">Consulta (Directorio/Comité)</option>
                            <option value="Administrador">Administrador (Pleno)</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Contraseña Inicial (mínimo 10 caracteres) <span class="text-rose-500">*</span>
                        </label>
                        <input name="clave" type="password" minlength="10" maxlength="128" required autocomplete="new-password"
                            placeholder="••••••••••••"
                            class="w-full text-xs sm:text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                    </div>

                    <div class="sm:col-span-2">
                        <input type="hidden" name="activo" value="1">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                            Motivo / Justificación de la Alta (Opcional)
                        </label>
                        <input name="motivo_cambio" maxlength="200" placeholder="Justificación para la bitácora técnica de auditoría..."
                            class="w-full text-xs sm:text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                    <button type="button" @click="modalNuevo = false"
                        class="px-4 py-2 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-5 py-2 bg-brand-700 hover:bg-brand-800 text-white rounded-lg text-xs font-semibold transition shadow-xs">
                        Crear Cuenta
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODAL: EDITAR USUARIO                                        -->
    <!-- ============================================================ -->
    <div x-show="usuarioEdit !== null" x-cloak
         class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-slate-200/90 space-y-5"
             @click.away="usuarioEdit = null">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                    <h3 class="text-base font-extrabold text-slate-900">
                        Editar Usuario: <span class="font-mono text-brand-800" x-text="usuarioEdit ? usuarioEdit.usuario : ''"></span>
                    </h3>
                </div>
                <button type="button" @click="usuarioEdit = null" class="text-slate-400 hover:text-slate-700 text-lg leading-none">&times;</button>
            </div>

            <template x-if="usuarioEdit">
                <form method="POST" :action="'/usuarios/' + usuarioEdit.id_responsable" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Nombre Completo <span class="text-rose-500">*</span>
                            </label>
                            <input name="nombre" :value="usuarioEdit.nombre" required maxlength="100"
                                class="w-full text-xs sm:text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Área / Dependencia <span class="text-rose-500">*</span>
                            </label>
                            <input name="area" :value="usuarioEdit.area" required maxlength="80"
                                class="w-full text-xs sm:text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Usuario (Login) <span class="text-rose-500">*</span>
                            </label>
                            <input name="usuario" :value="usuarioEdit.usuario" required maxlength="30"
                                class="w-full text-xs sm:text-sm font-mono border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Perfil Institucional <span class="text-rose-500">*</span>
                            </label>
                            <select name="perfil" required
                                class="w-full text-xs sm:text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500 font-semibold">
                                <option value="Administrador" :selected="usuarioEdit.perfil === 'Administrador'">Administrador</option>
                                <option value="Responsable" :selected="usuarioEdit.perfil === 'Responsable'">Responsable</option>
                                <option value="Consulta" :selected="usuarioEdit.perfil === 'Consulta'">Consulta</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Estado de la Cuenta <span class="text-rose-500">*</span>
                            </label>
                            <select name="activo" required
                                class="w-full text-xs sm:text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500 font-semibold">
                                <option value="1" :selected="usuarioEdit.activo == 1">Activo</option>
                                <option value="0" :selected="usuarioEdit.activo == 0">Inactivo</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Nueva Contraseña (Opcional)
                            </label>
                            <input name="clave" type="password" minlength="10" maxlength="128" autocomplete="new-password"
                                placeholder="Dejar en blanco para no cambiarla"
                                class="w-full text-xs sm:text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">
                                Justificación / Motivo del Cambio <span class="text-slate-400 font-normal text-[11px] normal-case">(Obligatorio si desactiva o degrada administrador)</span>
                            </label>
                            <input name="motivo_cambio" maxlength="200" placeholder="Motivo para la bitácora técnica de auditoría..."
                                class="w-full text-xs sm:text-sm border-slate-300 rounded-lg px-3 py-2 bg-slate-50 focus:bg-white focus:ring-brand-500 focus:border-brand-500">
                        </div>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                        <button type="button" @click="usuarioEdit = null"
                            class="px-4 py-2 border border-slate-300 rounded-lg text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-5 py-2 bg-brand-700 hover:bg-brand-800 text-white rounded-lg text-xs font-semibold transition shadow-xs">
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>

</div>
@endsection
