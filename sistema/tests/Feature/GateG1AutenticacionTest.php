<?php

namespace Tests\Feature;

use App\Models\Hito;
use App\Models\Proyecto;
use App\Models\Responsable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Suite de Verificación de la Compuerta de Seguridad Gate G1.
 * Valida autenticación, sesiones, rate-limiting, denegación por defecto y matriz de permisos por perfil.
 */
class GateG1AutenticacionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Asegurar que la muestra canónica esté disponible
        $this->seed();
    }

    /**
     * 1. Redirección de invitados a la pantalla de login.
     */
    public function test_invitados_son_redirigidos_a_login(): void
    {
        $response = $this->get('/tablero');
        $response->assertRedirect('/login');

        $responseProyectos = $this->get('/proyectos');
        $responseProyectos->assertRedirect('/login');
    }

    /**
     * 2. Pantalla de login es accesible públicamente.
     */
    public function test_pantalla_login_es_accesible(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('ELECTROCENTRO S. A.');
        $response->assertSee('Iniciar Sesión');
    }

    /**
     * 3. Autenticación exitosa para los tres perfiles del sistema.
     */
    public function test_login_exitoso_para_cada_perfil(): void
    {
        $this->conClavesDePrueba(['msalas' => 'PrivadaAdminPrueba2026!', 'aquispe' => 'PrivadaResponsablePrueba2026!', 'cgarcia' => 'PrivadaConsultaPrueba2026!'], function () {
            // Administrador (Marta Salas)
            $respAdmin = $this->post('/login', [
                'usuario' => 'msalas',
                'clave' => 'PrivadaAdminPrueba2026!',
            ]);
            $respAdmin->assertRedirect('/tablero');
            $this->assertAuthenticated();
            $this->assertEquals('Administrador', Auth::user()->perfil);

            $this->post('/logout');
            $this->assertGuest();

            // Responsable (Ana Quispe)
            $respResp = $this->post('/login', [
                'usuario' => 'aquispe',
                'clave' => 'PrivadaResponsablePrueba2026!',
            ]);
            $respResp->assertRedirect('/tablero');
            $this->assertAuthenticated();
            $this->assertEquals('Responsable', Auth::user()->perfil);

            $this->post('/logout');
            $this->assertGuest();

            // Consulta (Carlos García)
            $respCons = $this->post('/login', [
                'usuario' => 'cgarcia',
                'clave' => 'PrivadaConsultaPrueba2026!',
            ]);
            $respCons->assertRedirect('/tablero');
            $this->assertAuthenticated();
            $this->assertEquals('Consulta', Auth::user()->perfil);
        });
    }

    /**
     * 4. Rechazo de credenciales incorrectas.
     */
    public function test_login_falla_con_credenciales_invalidas(): void
    {
        $response = $this->from('/login')->post('/login', [
            'usuario' => 'msalas',
            'clave' => 'ClaveEquivocada999!',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('usuario');
        $this->assertGuest();
    }

    /**
     * 5. Bloqueo temporal por fuerza bruta (Rate Limiting tras 5 intentos fallidos).
     */
    public function test_bloqueo_por_fuerza_bruta_rate_limiting(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'usuario' => 'usuario_ataque',
                'clave' => 'password_erroneo',
            ]);
        }

        // El sexto intento debe ser bloqueado por limitación de tasa
        $response = $this->post('/login', [
            'usuario' => 'usuario_ataque',
            'clave' => 'password_erroneo',
        ]);

        $response->assertSessionHasErrors('usuario');
        $errors = session('errors')->get('usuario');
        $this->assertTrue(
            str_contains($errors[0], 'Demasiados intentos') || str_contains($errors[0], 'Too many login attempts'),
            'Debe indicar mensaje de limitación de tasa / rate limit.'
        );
    }

    /**
     * 6. Cierre de sesión invalida la sesión y redirige a login.
     */
    public function test_logout_cierra_sesion_e_invalida(): void
    {
        $user = Responsable::where('usuario', 'msalas')->first();
        $this->actingAs($user);
        $this->assertAuthenticated();

        $response = $this->post('/logout');
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    /**
     * 7. Denegación por defecto: Perfil Consulta recibe 403 Forbidden en toda mutación.
     */
    public function test_perfil_consulta_recibe_403_en_todas_las_mutaciones(): void
    {
        $consulta = Responsable::where('usuario', 'cgarcia')->first();
        $proyecto = Proyecto::first();
        $hito = Hito::first();

        $this->actingAs($consulta);

        // Crear proyecto -> 403
        $this->postJson('/proyectos', ['nombre' => 'Test'])->assertStatus(403);

        // Actualizar proyecto -> 403
        $this->putJson("/proyectos/{$proyecto->id_proyecto}", [
            'revision_esperada' => DB::table('control_revision')->where('id', 1)->value('version'),'nombre' => 'Modificado'])->assertStatus(403);

        // Eliminar proyecto -> 403
        $this->deleteJson("/proyectos/{$proyecto->id_proyecto}")->assertStatus(403);

        // Reabrir proyecto -> 403
        $this->postJson("/proyectos/{$proyecto->id_proyecto}/reabrir", ['motivo' => 'Test'])->assertStatus(403);

        // Crear hito -> 403
        $this->postJson("/proyectos/{$proyecto->id_proyecto}/hitos", ['descripcion' => 'Test'])->assertStatus(403);

        // Actualizar hito -> 403
        $this->putJson("/hitos/{$hito->id_hito}", [
            'revision_esperada' => DB::table('control_revision')->where('id', 1)->value('version'),'descripcion' => 'Modificado'])->assertStatus(403);
    }

    /**
     * 8. Responsable titular puede actualizar su propio proyecto en curso.
     */
    public function test_responsable_titular_puede_actualizar_su_proyecto_en_curso(): void
    {
        // Ana Quispe (id_responsable = 2) es titular de 005
        $ana = Responsable::where('usuario', 'aquispe')->first();
        $proyecto005 = Proyecto::where('codigo_pgd', '005')->first();

        $this->assertEquals((int) $ana->id_responsable, (int) $proyecto005->id_responsable);
        $this->assertEquals('En curso', $proyecto005->estado);

        $this->actingAs($ana);
        $response = $this->putJson("/proyectos/{$proyecto005->id_proyecto}", [
            'revision_esperada' => DB::table('control_revision')->where('id', 1)->value('version'),
            'nombre' => 'Nombre Actualizado',
            'monto' => $proyecto005->monto,
            'anio_inicio' => $proyecto005->anio_inicio,
            'anio_fin' => $proyecto005->anio_fin,
            'id_objetivo' => $proyecto005->id_objetivo,
            'id_responsable' => $proyecto005->id_responsable,
            'fecha_actualizacion_esperada' => (string) $proyecto005->fecha_actualizacion,
            'motivo_cambio' => 'Actualización de prueba G1',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['nombre' => 'Nombre Actualizado']);
    }

    /**
     * 9. Responsable recibe 403 al intentar mutar un proyecto ajeno.
     */
    public function test_responsable_recibe_403_en_proyecto_ajeno(): void
    {
        // Ana Quispe intenta modificar proyecto 011 cuyo titular es Luis Rojas
        $ana = Responsable::where('usuario', 'aquispe')->first();
        $proyecto011 = Proyecto::where('codigo_pgd', '011')->first();

        $this->assertNotEquals((int) $ana->id_responsable, (int) $proyecto011->id_responsable);

        $this->actingAs($ana);
        $response = $this->putJson("/proyectos/{$proyecto011->id_proyecto}", [
            'revision_esperada' => DB::table('control_revision')->where('id', 1)->value('version'),
            'nombre' => 'Intento No Autorizado',
        ]);

        $response->assertStatus(403);
    }

    /**
     * 10. Responsable recibe 403 al intentar mutar o agregar hitos a proyectos cerrados.
     */
    public function test_responsable_recibe_403_en_proyecto_cerrado(): void
    {
        // Ana Quispe intenta modificar su propio proyecto 014 que está Cerrado
        $ana = Responsable::where('usuario', 'aquispe')->first();
        $proyecto014 = Proyecto::where('codigo_pgd', '014')->first();

        $this->assertEquals('Cerrado', $proyecto014->estado);
        $this->assertEquals((int) $ana->id_responsable, (int) $proyecto014->id_responsable);

        $this->actingAs($ana);

        // Actualizar proyecto cerrado -> 403
        $this->putJson("/proyectos/{$proyecto014->id_proyecto}", [
            'revision_esperada' => DB::table('control_revision')->where('id', 1)->value('version'),'nombre' => 'Cambio'])->assertStatus(403);

        // Agregar hito a proyecto cerrado -> 403
        $this->postJson("/proyectos/{$proyecto014->id_proyecto}/hitos", ['descripcion' => 'Nuevo'])->assertStatus(403);

        // Reabrir proyecto -> 403 (solo Admin)
        $this->postJson("/proyectos/{$proyecto014->id_proyecto}/reabrir", ['motivo' => 'Reabrir'])->assertStatus(403);
    }

    /**
     * 11. Administrador tiene autorización plena para creación, eliminación y reapertura.
     */
    public function test_administrador_autorizado_para_operaciones_privilegiadas(): void
    {
        $admin = Responsable::where('usuario', 'msalas')->first();
        $proyectoCerrado = Proyecto::where('estado', 'Cerrado')->first();

        $this->actingAs($admin);

        // Crear proyecto -> 201
        $res = $this->postJson('/proyectos', [
            'codigo_pgd' => '098',
            'nombre' => 'Nuevo PGD Prueba Admin',
            'monto' => 150000.00,
            'anio_inicio' => 2024,
            'anio_fin' => 2025,
            'estado' => 'En curso',
            'id_objetivo' => 1,
            'id_responsable' => 1,
            'fuente' => 'Cartera PGD',
        ]);
        $res->assertStatus(201);
        $idNuevo = $res->json('id_proyecto');

        // Reabrir proyecto cerrado -> 200
        $this->postJson("/proyectos/{$proyectoCerrado->id_proyecto}/reabrir", ['motivo' => 'Reapertura oficial de prueba'])
            ->assertStatus(200);

        // Eliminar proyecto recién creado -> 200
        $this->deleteJson("/proyectos/{$idNuevo}")->assertStatus(200);
    }
}
