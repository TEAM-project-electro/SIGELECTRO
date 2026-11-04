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
                'nombre' => 'Marta Salas',
                'area' => 'Planeamiento',
                'perfil' => 'Administrador',
                'usuario' => 'msalas',
                'clave' => 'Admin2026!',
                'activo' => 1,
            ],
            [
                'nombre' => 'Ana Quispe',
                'area' => 'TIC',
                'perfil' => 'Responsable',
                'usuario' => 'aquispe',
                'clave' => 'Resp2026!',
                'activo' => 1,
            ],
            [
                'nombre' => 'Luis Rojas',
                'area' => 'TIC',
                'perfil' => 'Responsable',
                'usuario' => 'lrojas',
                'clave' => 'Resp2026!',
                'activo' => 1,
            ],
            [
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
