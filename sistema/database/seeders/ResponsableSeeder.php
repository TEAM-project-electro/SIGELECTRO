<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class ResponsableSeeder extends Seeder
{
    /**
     * Carga de usuarios y perfiles demostrativos.
     * Inserta únicamente identidades ausentes; nunca restablece cuentas existentes.
     * Sin alta interactiva, las claves aleatorias no se publican ni se entregan.
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
                'activo' => 1,
            ],
            [
                'id_responsable' => 2,
                'nombre' => 'Ana Quispe',
                'area' => 'TIC',
                'perfil' => 'Responsable',
                'usuario' => 'aquispe',
                'activo' => 1,
            ],
            [
                'id_responsable' => 3,
                'nombre' => 'Luis Rojas',
                'area' => 'TIC',
                'perfil' => 'Responsable',
                'usuario' => 'lrojas',
                'activo' => 1,
            ],
            [
                'id_responsable' => 4,
                'nombre' => 'Carlos Garcia',
                'area' => 'Comite de Gobierno Digital',
                'perfil' => 'Consulta',
                'usuario' => 'cgarcia',
                'activo' => 1,
            ],
        ];

        foreach ($usuarios as $u) {
            $db = DB::connection('escritor');
            $existente = $db->table('responsable')->select('id_responsable')->where('usuario', $u['usuario'])->first();
            if ($existente) {
                if ((int) $existente->id_responsable !== $u['id_responsable']) {
                    throw new RuntimeException('Conflicto de identidad en la siembra de responsables.');
                }
                continue; // Nunca restablecer credenciales, privilegios ni datos editados.
            }
            if ($db->table('responsable')->where('id_responsable', $u['id_responsable'])->exists()) {
                throw new RuntimeException('El identificador canónico ya pertenece a otra cuenta.');
            }
            $clave = $u['id_responsable'] === 1
                ? (config('sig.clave_administrador_inicial') ?: Str::random(64))
                : Str::random(64);
            $db->table('responsable')->insert([
                    'id_responsable' => $u['id_responsable'],
                    'nombre' => $u['nombre'],
                    'area' => $u['area'],
                    'perfil' => $u['perfil'],
                    'usuario' => $u['usuario'],
                    'clave_hash' => Hash::make($clave),
                    'activo' => $u['activo'],
            ]);
        }
    }
}
