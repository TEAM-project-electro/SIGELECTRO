<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProyectoSeeder extends Seeder
{
    /**
     * Carga de los 10 proyectos canónicos de la muestra PGD 2023-2026.
     * Monto total exacto: S/ 8,623,550.00.
     * Idempotente por codigo_pgd.
     */
    public function run(): void
    {
        $idOgd = DB::connection('escritor')->table('objetivo_gd')->pluck('id_objetivo', 'codigo')->toArray();
        $idResp = DB::connection('escritor')->table('responsable')->pluck('id_responsable', 'usuario')->toArray();

        $proyectos = [
            [
                'codigo_pgd' => '005',
                'nombre' => 'Equipos de ciberseguridad',
                'ogd_codigo' => 'OGD 04',
                'monto' => 500000.00,
                'anio_inicio' => 2023,
                'anio_fin' => 2024,
                'estado' => 'En curso',
                'resp_usuario' => 'aquispe',
                'fuente' => 'Cartera PGD 2023 a 2026',
            ],
            [
                'codigo_pgd' => '010',
                'nombre' => 'Servicio Software Factory (FONAFE 5)',
                'ogd_codigo' => 'OGD 05',
                'monto' => 2383550.00,
                'anio_inicio' => 2023,
                'anio_fin' => 2026,
                'estado' => 'En curso',
                'resp_usuario' => 'aquispe',
                'fuente' => 'Cartera PGD 2023 a 2026',
            ],
            [
                'codigo_pgd' => '011',
                'nombre' => 'Enlaces de fibra optica en subestaciones',
                'ogd_codigo' => 'OGD 03',
                'monto' => 2500000.00,
                'anio_inicio' => 2023,
                'anio_fin' => 2025,
                'estado' => 'En curso',
                'resp_usuario' => 'lrojas',
                'fuente' => 'Cartera PGD 2023 a 2026',
            ],
            [
                'codigo_pgd' => '013',
                'nombre' => 'Implementacion de SAP S/4 HANA',
                'ogd_codigo' => 'OGD 06',
                'monto' => 2000000.00,
                'anio_inicio' => 2023,
                'anio_fin' => 2026,
                'estado' => 'En curso',
                'resp_usuario' => 'lrojas',
                'fuente' => 'Cartera PGD 2023 a 2026',
            ],
            [
                'codigo_pgd' => '014',
                'nombre' => 'Actualizacion del marco de gobierno e incorporacion del Comite de Gobierno Digital',
                'ogd_codigo' => 'OGD 02',
                'monto' => 120000.00,
                'anio_inicio' => 2023,
                'anio_fin' => 2023,
                'estado' => 'En curso', // Se cierra en SeguimientoSeeder tras registrar sus hitos cerrados
                'resp_usuario' => 'aquispe',
                'fuente' => 'Cartera PGD 2023 a 2026',
            ],
            [
                'codigo_pgd' => '017',
                'nombre' => 'Fortalecimiento de las competencias digitales del personal',
                'ogd_codigo' => 'OGD 01',
                'monto' => 300000.00,
                'anio_inicio' => 2023,
                'anio_fin' => 2026,
                'estado' => 'En curso',
                'resp_usuario' => 'aquispe',
                'fuente' => 'Cartera PGD 2023 a 2026',
            ],
            [
                'codigo_pgd' => '020',
                'nombre' => 'Transicion al protocolo IPv6',
                'ogd_codigo' => 'OGD 03',
                'monto' => 400000.00,
                'anio_inicio' => 2023,
                'anio_fin' => 2025,
                'estado' => 'En curso',
                'resp_usuario' => 'lrojas',
                'fuente' => 'Cartera PGD 2023 a 2026',
            ],
            [
                'codigo_pgd' => '022',
                'nombre' => 'Publicacion de datos abiertos en la Plataforma Nacional de Datos Abiertos',
                'ogd_codigo' => 'OGD 05',
                'monto' => 108000.00,
                'anio_inicio' => 2023,
                'anio_fin' => 2023,
                'estado' => 'En curso', // Se cierra en SeguimientoSeeder tras registrar sus hitos cerrados
                'resp_usuario' => 'aquispe',
                'fuente' => 'Cartera PGD 2023 a 2026',
            ],
            [
                'codigo_pgd' => '023',
                'nombre' => 'Implementacion y certificacion del sistema de gestion de seguridad de la informacion (NTP ISO/IEC 27001:2014)',
                'ogd_codigo' => 'OGD 04',
                'monto' => 212000.00,
                'anio_inicio' => 2023,
                'anio_fin' => 2023,
                'estado' => 'En curso', // Se cierra en SeguimientoSeeder tras registrar sus hitos cerrados
                'resp_usuario' => 'aquispe',
                'fuente' => 'Cartera PGD 2023 a 2026',
            ],
            [
                'codigo_pgd' => '024',
                'nombre' => 'Mesa de partes digital integrada al sistema de gestion documental',
                'ogd_codigo' => 'OGD 05',
                'monto' => 100000.00,
                'anio_inicio' => 2023,
                'anio_fin' => 2023,
                'estado' => 'En curso',
                'resp_usuario' => 'aquispe',
                'fuente' => 'Cartera PGD 2023 a 2026',
            ],
        ];

        foreach ($proyectos as $p) {
            DB::connection('escritor')->table('proyecto')->updateOrInsert(
                ['codigo_pgd' => $p['codigo_pgd']],
                [
                    'nombre' => $p['nombre'],
                    'monto' => $p['monto'],
                    'anio_inicio' => $p['anio_inicio'],
                    'anio_fin' => $p['anio_fin'],
                    'estado' => $p['estado'],
                    'id_objetivo' => $idOgd[$p['ogd_codigo']],
                    'id_responsable' => $idResp[$p['resp_usuario']],
                    'fuente' => $p['fuente'],
                ]
            );
        }
    }
}
