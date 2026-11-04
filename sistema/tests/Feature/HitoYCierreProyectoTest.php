<?php

namespace Tests\Feature;

use App\Models\Hito;
use App\Models\Proyecto;
use App\Models\Responsable;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Suite de Pruebas de la Etapa 07: Hitos, Línea Base y Cierre de Proyectos (Regla R3).
 * Valida creación, edición, regla R3 de cierre, reapertura con motivo obligatorio y bitácora técnica.
 */
class HitoYCierreProyectoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Asegurar que la base tenga la muestra canónica sembrada
        $this->seed();
    }

    protected function tearDown(): void
    {
        DB::connection('mysql')->table('hito')
            ->where('descripcion', 'like', 'Recepción y homologación%')
            ->orWhere('descripcion', 'like', 'Mantenimiento preventivo adicional%')
            ->orWhere('descripcion', 'like', 'Hito temporal para prueba%')
            ->delete();

        parent::tearDown();
    }

    /**
     * 1. Responsable titular puede crear un hito en su proyecto.
     */
    public function test_titular_crea_hito_en_su_proyecto(): void
    {
        $ana = Responsable::where('usuario', 'aquispe')->first();
        $proyecto005 = Proyecto::where('codigo_pgd', '005')->first();

        $this->actingAs($ana);

        $response = $this->post("/proyectos/{$proyecto005->id_proyecto}/hitos", [
            'descripcion' => 'Recepción y homologación técnica de servidores',
            'fecha_prevista' => '2024-11-30',
            'en_linea_base' => 1,
        ]);

        $response->assertRedirect("/proyectos/{$proyecto005->id_proyecto}");
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('hito', [
            'id_proyecto' => $proyecto005->id_proyecto,
            'descripcion' => 'Recepción y homologación técnica de servidores',
            'en_linea_base' => 1,
        ]);
    }

    /**
     * 2. Creación de hito fuera de línea base (extraordinario).
     */
    public function test_crear_hito_fuera_de_linea_base(): void
    {
        $ana = Responsable::where('usuario', 'aquispe')->first();
        $proyecto005 = Proyecto::where('codigo_pgd', '005')->first();

        $this->actingAs($ana);

        $response = $this->post("/proyectos/{$proyecto005->id_proyecto}/hitos", [
            'descripcion' => 'Mantenimiento preventivo adicional por variación eléctrica',
            'fecha_prevista' => '2024-12-15',
            'en_linea_base' => 0,
        ]);

        $response->assertRedirect("/proyectos/{$proyecto005->id_proyecto}");

        $this->assertDatabaseHas('hito', [
            'id_proyecto' => $proyecto005->id_proyecto,
            'descripcion' => 'Mantenimiento preventivo adicional por variación eléctrica',
            'en_linea_base' => 0,
        ]);
    }

    /**
     * 3. Responsable ajeno recibe 403 al intentar crear hito en proyecto no asignado.
     */
    public function test_responsable_ajeno_recibe_403_al_crear_hito(): void
    {
        $luis = Responsable::where('usuario', 'lrojas')->first();
        $proyecto005 = Proyecto::where('codigo_pgd', '005')->first(); // Titular: Ana Quispe

        $this->actingAs($luis);

        $response = $this->post("/proyectos/{$proyecto005->id_proyecto}/hitos", [
            'descripcion' => 'Intento No Autorizado',
            'fecha_prevista' => '2024-12-31',
            'en_linea_base' => 1,
        ]);

        $response->assertStatus(403);
    }

    /**
     * 4. Rechazo de creación de hito en proyecto ya cerrado (Regla de proyecto cerrado).
     */
    public function test_rechazo_creacion_hito_en_proyecto_cerrado(): void
    {
        $admin = Responsable::where('usuario', 'msalas')->first();
        $proyectoCerrado = Proyecto::where('estado', 'Cerrado')->first();

        $this->actingAs($admin);

        $response = $this->post("/proyectos/{$proyectoCerrado->id_proyecto}/hitos", [
            'descripcion' => 'Intento en proyecto cerrado',
            'fecha_prevista' => '2024-12-31',
            'en_linea_base' => 1,
        ]);

        $response->assertStatus(403);
    }

    /**
     * 5. Titular actualiza y cierra un hito con evidencia documental.
     */
    public function test_titular_actualiza_y_cierra_hito(): void
    {
        $ana = Responsable::where('usuario', 'aquispe')->first();
        $proyecto005 = Proyecto::where('codigo_pgd', '005')->first();
        $hito = $proyecto005->hitos()->whereNull('fecha_cierre')->first();

        $this->actingAs($ana);

        $response = $this->put("/hitos/{$hito->id_hito}", [
            'descripcion' => $hito->descripcion . ' - Concluido',
            'fecha_prevista' => $hito->fecha_prevista,
            'fecha_cierre' => '2024-10-15',
            'evidencia' => 'Acta de entrega técnica y recepción de equipamiento N° 2024-089',
            'en_linea_base' => $hito->en_linea_base,
        ]);

        $response->assertRedirect("/proyectos/{$proyecto005->id_proyecto}");
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('hito', [
            'id_hito' => $hito->id_hito,
            'fecha_cierre' => '2024-10-15',
            'evidencia' => 'Acta de entrega técnica y recepción de equipamiento N° 2024-089',
        ]);
    }

    /**
     * 6. Regla R3: Cierre de proyecto falla si existen hitos de línea base abiertos.
     */
    public function test_regla_r3_impide_cierre_con_hitos_abiertos(): void
    {
        $ana = Responsable::where('usuario', 'aquispe')->first();
        $proyecto005 = Proyecto::where('codigo_pgd', '005')->first();

        // 005 tiene hitos de línea base abiertos
        $this->assertGreaterThan(0, $proyecto005->hitos()->where('en_linea_base', 1)->whereNull('fecha_cierre')->count());

        $this->actingAs($ana);

        $response = $this->post("/proyectos/{$proyecto005->id_proyecto}/cerrar");

        $response->assertRedirect("/proyectos/{$proyecto005->id_proyecto}");
        $response->assertSessionHas('error');

        // El proyecto debe seguir en curso
        $this->assertDatabaseHas('proyecto', [
            'id_proyecto' => $proyecto005->id_proyecto,
            'estado' => 'En curso',
        ]);
    }

    /**
     * 7. Regla R3: Cierre exitoso cuando todos los hitos de línea base están concluidos.
     */
    public function test_regla_r3_permite_cierre_con_todos_los_hitos_lb_cerrados(): void
    {
        $ana = Responsable::where('usuario', 'aquispe')->first();
        $proyecto005 = Proyecto::where('codigo_pgd', '005')->first();

        // Cerramos todos los hitos de LB del proyecto 005
        DB::connection('escritor')->statement('SET @id_responsable = 2;');
        DB::connection('escritor')->table('hito')
            ->where('id_proyecto', $proyecto005->id_proyecto)
            ->where('en_linea_base', 1)
            ->update([
                'fecha_cierre' => '2024-10-01',
                'evidencia' => 'Conformidad total para pase a cierre',
            ]);
        DB::connection('escritor')->statement('SET @id_responsable = NULL;');

        $this->actingAs($ana);

        $response = $this->post("/proyectos/{$proyecto005->id_proyecto}/cerrar");

        $response->assertRedirect("/proyectos/{$proyecto005->id_proyecto}");
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('proyecto', [
            'id_proyecto' => $proyecto005->id_proyecto,
            'estado' => 'Cerrado',
        ]);
    }

    /**
     * 8. Responsable no puede reabrir un proyecto cerrado (Solo Administrador).
     */
    public function test_responsable_no_puede_reabrir_proyecto(): void
    {
        $ana = Responsable::where('usuario', 'aquispe')->first();
        $proyectoCerrado = Proyecto::where('estado', 'Cerrado')->first();

        $this->actingAs($ana);

        $response = $this->post("/proyectos/{$proyectoCerrado->id_proyecto}/reabrir", [
            'motivo' => 'Intento de reapertura por responsable',
        ]);

        $response->assertStatus(403);
    }

    /**
     * 9. Reapertura requiere motivo obligatorio (falla si viene vacío).
     */
    public function test_reapertura_requiere_motivo_obligatorio(): void
    {
        $admin = Responsable::where('usuario', 'msalas')->first();
        $proyectoCerrado = Proyecto::where('estado', 'Cerrado')->first();

        $this->actingAs($admin);

        $response = $this->post("/proyectos/{$proyectoCerrado->id_proyecto}/reabrir", [
            'motivo' => '',
        ]);

        $response->assertSessionHasErrors('motivo');
    }

    /**
     * 10. Administrador reabre proyecto cerrado con motivo y se audita en bitacora_motivo.
     */
    public function test_administrador_reabre_proyecto_con_motivo_y_se_audita(): void
    {
        $admin = Responsable::where('usuario', 'msalas')->first();
        $proyectoCerrado = Proyecto::where('estado', 'Cerrado')->first();

        $this->actingAs($admin);

        $motivoJustificado = 'Reapertura aprobada por Comité de Gobierno Digital según Resolución N° 088-2024';

        $response = $this->post("/proyectos/{$proyectoCerrado->id_proyecto}/reabrir", [
            'motivo' => $motivoJustificado,
        ]);

        $response->assertRedirect("/proyectos/{$proyectoCerrado->id_proyecto}");
        $response->assertSessionHas('success');

        // Estado del proyecto debe haber cambiado a 'En curso'
        $this->assertDatabaseHas('proyecto', [
            'id_proyecto' => $proyectoCerrado->id_proyecto,
            'estado' => 'En curso',
        ]);

        // Verificamos que se haya registrado en bitacora_cambio y en bitacora_motivo (Decisión 11)
        $cambio = DB::table('bitacora_cambio')
            ->where('tabla_afectada', 'proyecto')
            ->where('id_registro', $proyectoCerrado->id_proyecto)
            ->where('campo', 'estado')
            ->orderByDesc('id_cambio')
            ->first();

        $this->assertNotNull($cambio, 'Debe existir un registro de bitácora de cambio.');
        $this->assertEquals((int) $admin->id_responsable, (int) $cambio->id_responsable);

        $this->assertDatabaseHas('bitacora_motivo', [
            'id_cambio' => $cambio->id_cambio,
            'motivo' => $motivoJustificado,
        ]);
    }

    /**
     * 11. Administrador puede eliminar hito en proyecto en curso.
     */
    public function test_administrador_elimina_hito(): void
    {
        $admin = Responsable::where('usuario', 'msalas')->first();
        $proyecto005 = Proyecto::where('codigo_pgd', '005')->first();

        // Creamos un hito de prueba para eliminar
        DB::connection('escritor')->statement('SET @id_responsable = 1;');
        $idHito = DB::connection('escritor')->table('hito')->insertGetId([
            'id_proyecto' => $proyecto005->id_proyecto,
            'descripcion' => 'Hito temporal para prueba de eliminación',
            'fecha_prevista' => '2024-12-31',
            'en_linea_base' => 0,
        ]);
        DB::connection('escritor')->statement('SET @id_responsable = NULL;');

        $this->actingAs($admin);

        $response = $this->delete("/hitos/{$idHito}");

        $response->assertRedirect("/proyectos/{$proyecto005->id_proyecto}");
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('hito', [
            'id_hito' => $idHito,
        ]);
    }
}
