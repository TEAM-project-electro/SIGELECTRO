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
        $activeDb = config('database.connections.lector.database') ?: config('database.connections.mysql.database');
        if (!str_ends_with($activeDb, '_test') && !str_ends_with($activeDb, '_restaurada')) {
            throw new RuntimeException("PELIGRO DE AISLAMIENTO (A02): Los tests intentaron ejecutarse contra '{$activeDb}'. Las pruebas deben ejecutarse exclusivamente sobre bases de prueba terminadas en '_test' o '_restaurada'.");
        }
    }
}
