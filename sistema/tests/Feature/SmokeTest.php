<?php

namespace Tests\Feature;

use Tests\TestCase;

class SmokeTest extends TestCase
{
    /**
     * Prueba de humo que verifica la disponibilidad de la aplicación y la conexión a MariaDB 10.11.
     */
    public function test_pagina_de_salud_responde_y_verifica_mariadb_10_11(): void
    {
        $response = $this->get('/salud');

        $response->assertStatus(200);
        $response->assertJson([
            'estado' => 'OPERATIVO',
            'sistema' => 'SIG Electrocentro S. A. - Prototipo Académico',
            'base_datos' => [
                'estado' => 'conectado',
                'puerto' => 3307,
                'esquema' => 'seguimiento_gd',
                'referencia_10_11' => true,
            ],
            'activos_compilados' => true,
        ]);
    }

    /**
     * Verifica que la ruta raíz responde HTTP 200.
     */
    public function test_ruta_raiz_responde_correctamente(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
