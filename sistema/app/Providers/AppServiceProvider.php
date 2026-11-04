<?php

namespace App\Providers;

use App\Models\AvancePresupuestal;
use App\Models\Hito;
use App\Models\ObjetivoGd;
use App\Models\Proyecto;
use App\Policies\AvancePresupuestalPolicy;
use App\Policies\HitoPolicy;
use App\Policies\ObjetivoGdPolicy;
use App\Policies\ProyectoPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Proyecto::class, ProyectoPolicy::class);
        Gate::policy(Hito::class, HitoPolicy::class);
        Gate::policy(AvancePresupuestal::class, AvancePresupuestalPolicy::class);
        Gate::policy(ObjetivoGd::class, ObjetivoGdPolicy::class);
    }
}
