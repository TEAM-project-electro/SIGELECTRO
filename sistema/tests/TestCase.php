<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
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
