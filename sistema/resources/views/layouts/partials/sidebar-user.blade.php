@auth
<div class="p-4 border-t border-white/10 text-xs">
    <p class="font-semibold text-white truncate">{{ Auth::user()->nombre }}</p>
    <p class="text-slate-400">{{ Auth::user()->perfil }}</p>
    <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf
        <button type="submit" class="w-full px-3 py-1.5 rounded-lg border border-white/15 text-slate-200 hover:bg-white/10">Cerrar sesión</button>
    </form>
</div>
@endauth
