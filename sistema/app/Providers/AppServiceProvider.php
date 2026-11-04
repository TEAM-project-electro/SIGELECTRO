<?php

namespace App\Providers;

use App\Models\AvancePresupuestal;
use App\Models\BitacoraCambio;
use App\Models\Hito;
use App\Models\ObjetivoGd;
use App\Models\Proyecto;
use App\Policies\AvancePresupuestalPolicy;
use App\Policies\BitacoraCambioPolicy;
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
        \Illuminate\Support\Facades\Auth::provider('responsable', function ($app, array $config) {
            return new \App\Auth\ResponsableProvider($app['hash'], $config['model']);
        });
        Gate::policy(Proyecto::class, ProyectoPolicy::class);
        Gate::policy(\App\Models\Responsable::class, \App\Policies\ResponsablePolicy::class);
        Gate::policy(Hito::class, HitoPolicy::class);
        Gate::policy(AvancePresupuestal::class, AvancePresupuestalPolicy::class);
        Gate::policy(ObjetivoGd::class, ObjetivoGdPolicy::class);
        Gate::policy(BitacoraCambio::class, BitacoraCambioPolicy::class);
    }
}
