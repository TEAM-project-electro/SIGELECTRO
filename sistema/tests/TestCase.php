<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function conClavesDePrueba(array $claves, callable $prueba): void
    {
        $anteriores = [];
        foreach ($claves as $usuario => $clave) {
            $anteriores[$usuario] = \Illuminate\Support\Facades\DB::connection('auth')->table('responsable')
                ->where('usuario', $usuario)->value('clave_hash');
        }
        $guardar = function (array $hashes): void {
            \App\Services\DatabaseTransactionService::run(1, function ($db) use ($hashes) {
                foreach ($hashes as $usuario => $hash) {
                    $db->table('responsable')->where('usuario', $usuario)->update(['clave_hash' => $hash]);
                }
            }, 'Credenciales aisladas de prueba');
        };
        try {
            $guardar(array_map(fn ($clave) => \Illuminate\Support\Facades\Hash::make($clave), $claves));
            $prueba();
        } finally {
            $guardar($anteriores);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Salvaguarda obligatoria de aislamiento (Hallazgo A02):
        // Garantizar que toda prueba se ejecute exclusivamente contra esquemas descartables (*_test o *_restaurada).
        foreach (['mysql','lector','auth','escritor','dw_migrador','dw_etl','dw_lector'] as $nombre) {
            $db = \Illuminate\Support\Facades\DB::connection($nombre);
            $activeDb = $db->getDatabaseName();
            if (!str_ends_with($activeDb, '_test') || $db->selectOne('SELECT DATABASE() d')->d !== $activeDb) {
                throw new RuntimeException("PELIGRO DE AISLAMIENTO: {$nombre} no apunta al esquema de prueba esperado.");
            }
        }
    }
}
