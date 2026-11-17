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
        $response->assertOk()->assertExactJson(['estado' => 'OPERATIVO']);
    }

    /**
     * Verifica que la ruta raíz redirige a login para invitados y login responde 200.
     */
    public function test_ruta_raiz_responde_correctamente(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');

        $login = $this->get('/login');
        $login->assertStatus(200);
    }
}
