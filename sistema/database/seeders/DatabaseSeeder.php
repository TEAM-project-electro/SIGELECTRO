<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Carga idempotente de objetivos, usuarios, proyectos, hitos y avances presupuestales.
     */
    public function run(): void
    {
        // 1. Establecer contexto de autor administrativo para la siembra inicial
        DB::connection('escritor')->statement('SET @id_responsable = 1;');
        DB::connection('escritor')->statement('SET @motivo_cambio = "Carga inicial demostrativa de la cartera";');

        try {
            $this->call([
                ResponsableSeeder::class,
                ObjetivoGdSeeder::class,
                ProyectoSeeder::class,
                SeguimientoSeeder::class,
                GestionGerencialSeeder::class,
            ]);
        } finally {
            // Limpieza estricta del contexto tras sembrar
            DB::connection('escritor')->statement('SET @id_responsable = NULL;');
            DB::connection('escritor')->statement('SET @motivo_cambio = NULL;');
        }
    }
}
