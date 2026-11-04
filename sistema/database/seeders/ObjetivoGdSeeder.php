<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ObjetivoGdSeeder extends Seeder
{
    /**
     * Carga de los 6 Objetivos de Gobierno Digital del PGD 2023-2026.
     * Idempotente por código de objetivo.
     */
    public function run(): void
    {
        $objetivos = [
            [
                'codigo' => 'OGD 01',
                'descripcion' => '(Descripcion pendiente de confirmacion documental - PGD p. 25)',
            ],
            [
                'codigo' => 'OGD 02',
                'descripcion' => 'Optimizar procesos, establecer politicas, procedimientos, metodologias y estandares',
            ],
            [
                'codigo' => 'OGD 03',
                'descripcion' => 'Fortalecer y modernizar la infraestructura tecnologica',
            ],
            [
                'codigo' => 'OGD 04',
                'descripcion' => 'Mejora continua del Sistema de Seguridad de la Informacion',
            ],
            [
                'codigo' => 'OGD 05',
                'descripcion' => 'Sistemas de informacion con datos de calidad para decidir',
            ],
            [
                'codigo' => 'OGD 06',
                'descripcion' => '(Descripcion pendiente de confirmacion documental - PGD p. 25)',
            ],
        ];

        foreach ($objetivos as $item) {
            DB::connection('escritor')->table('objetivo_gd')->updateOrInsert(
                ['codigo' => $item['codigo']],
                ['descripcion' => $item['descripcion']]
            );
        }
    }
}
