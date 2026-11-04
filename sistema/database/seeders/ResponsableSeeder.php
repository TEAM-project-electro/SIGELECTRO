<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ResponsableSeeder extends Seeder
{
    /**
     * Carga de usuarios y perfiles demostrativos.
     * Idempotente por usuario. Contraseñas seguras con bcrypt (sin SHA2).
     */
    public function run(): void
    {
        $usuarios = [
            [
                'id_responsable' => 1,
                'nombre' => 'Marta Salas',
                'area' => 'Planeamiento',
                'perfil' => 'Administrador',
                'usuario' => 'msalas',
                'clave' => 'Admin2026!',
                'activo' => 1,
            ],
            [
                'id_responsable' => 2,
                'nombre' => 'Ana Quispe',
                'area' => 'TIC',
                'perfil' => 'Responsable',
                'usuario' => 'aquispe',
                'clave' => 'Resp2026!',
                'activo' => 1,
            ],
            [
                'id_responsable' => 3,
                'nombre' => 'Luis Rojas',
                'area' => 'TIC',
                'perfil' => 'Responsable',
                'usuario' => 'lrojas',
                'clave' => 'Resp2026!',
                'activo' => 1,
            ],
            [
                'id_responsable' => 4,
                'nombre' => 'Carlos Garcia',
                'area' => 'Comite de Gobierno Digital',
                'perfil' => 'Consulta',
                'usuario' => 'cgarcia',
                'clave' => 'Consulta2026!',
                'activo' => 1,
            ],
        ];

        foreach ($usuarios as $u) {
            DB::connection('escritor')->table('responsable')->updateOrInsert(
                ['usuario' => $u['usuario']],
                [
                    'id_responsable' => $u['id_responsable'],
                    'nombre' => $u['nombre'],
                    'area' => $u['area'],
                    'perfil' => $u['perfil'],
                    'clave_hash' => Hash::make($u['clave']),
                    'activo' => $u['activo'],
                ]
            );
        }
    }
}
