<?php

namespace Tests\Feature;

use App\Models\AvancePresupuestal;
use App\Models\Proyecto;
use App\Models\Responsable;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Suite de Pruebas de la Etapa 08: Avances Presupuestales y Reglas Financieras (R1 y R2).
 * Valida límites presupuestales, monotonicidad no decreciente, unicidad por fecha y protección en proyectos cerrados.
 */
class AvancePresupuestalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Asegurar que la muestra canónica esté sembrada
        $this->seed();
    }

    protected function tearDown(): void
    {
        // Limpiar posibles cortes temporales creados en pruebas
        DB::connection('mysql')->statement("SET @id_responsable = 1;");
        DB::connection('mysql')->statement("SET @motivo_cambio = 'Limpieza prueba';");
        DB::connection('mysql')->table('avance_presupuestal')
            ->where('fecha_reporte', '2024-12-31')
            ->orWhere('fecha_reporte', '2023-04-30')
            ->delete();
        DB::connection('mysql')->statement("SET @id_responsable = NULL;");

        parent::tearDown();
    }

    /**
     * 1. Responsable titular registra nuevo corte presupuestal válido cumpliendo R1 y R2.
     */
    public function test_titular_registra_corte_presupuestal_valido(): void
    {
        $ana = Responsable::where('usuario', 'aquispe')->first();
        $proyecto005 = Proyecto::where('codigo_pgd', '005')->first(); // Monto: 500,000. Último corte: 2024-09-30, gasto: 450,000

        $this->actingAs($ana);

        $response = $this->post("/proyectos/{$proyecto005->id_proyecto}/avances", [
            'fecha_reporte' => '2024-12-31',
            'presupuesto_planificado' => 500000.00,
            'gasto_acumulado' => 480000.00,
        ]);

        $response->assertRedirect("/proyectos/{$proyecto005->id_proyecto}");
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('avance_presupuestal', [
            'id_proyecto' => $proyecto005->id_proyecto,
            'fecha_reporte' => '2024-12-31',
            'presupuesto_planificado' => 500000.00,
            'gasto_acumulado' => 480000.00,
        ]);
    }

    /**
     * 2. Regla R1: Rechaza presupuesto planificado mayor al monto total del proyecto.
     */
    public function test_regla_r1_rechaza_planificado_mayor_al_monto(): void
    {
        $ana = Responsable::where('usuario', 'aquispe')->first();
        $proyecto005 = Proyecto::where('codigo_pgd', '005')->first(); // Monto: 500,000

        $this->actingAs($ana);

        $response = $this->post("/proyectos/{$proyecto005->id_proyecto}/avances", [
            'fecha_reporte' => '2024-12-31',
            'presupuesto_planificado' => 550000.00, // Supera monto de 500k
            'gasto_acumulado' => 400000.00,
        ]);

        $response->assertSessionHasErrors('presupuesto_planificado');
    }

    /**
     * 3. Regla R1: Rechaza gasto acumulado mayor al monto total del proyecto.
     */
    public function test_regla_r1_rechaza_gasto_mayor_al_monto(): void
    {
        $ana = Responsable::where('usuario', 'aquispe')->first();
        $proyecto005 = Proyecto::where('codigo_pgd', '005')->first(); // Monto: 500,000

        $this->actingAs($ana);

        $response = $this->post("/proyectos/{$proyecto005->id_proyecto}/avances", [
            'fecha_reporte' => '2024-12-31',
            'presupuesto_planificado' => 480000.00,
            'gasto_acumulado' => 550000.00, // Supera monto de 500k
        ]);

        $response->assertSessionHasErrors('gasto_acumulado');
    }

    /**
     * 4. Regla R2: Rechaza gasto acumulado menor al corte cronológico anterior.
     */
    public function test_regla_r2_rechaza_gasto_menor_al_corte_anterior(): void
    {
        $ana = Responsable::where('usuario', 'aquispe')->first();
        $proyecto005 = Proyecto::where('codigo_pgd', '005')->first();
        // El corte al 2024-09-30 tiene gasto_acumulado = 450,000.00

        $this->actingAs($ana);

        $response = $this->post("/proyectos/{$proyecto005->id_proyecto}/avances", [
            'fecha_reporte' => '2024-12-31',
            'presupuesto_planificado' => 480000.00,
            'gasto_acumulado' => 400000.00, // Menor a 450,000 previo
        ]);

        $response->assertSessionHas('error');
        $error = session('error');
        $this->assertTrue(str_contains($error, 'Regla R2') || str_contains($error, 'menor al reporte'));
    }

    /**
     * 5. Regla R2: Rechaza corte intermedio con gasto superior al corte posterior.
     */
    public function test_regla_r2_rechaza_corte_intermedio_con_gasto_superior_al_posterior(): void
    {
        $ana = Responsable::where('usuario', 'aquispe')->first();
        $proyecto005 = Proyecto::where('codigo_pgd', '005')->first();
        // Cortes existentes: 2023-03-31 (150k) y 2023-06-30 (380k).
        // Intentamos insertar corte intermedio al 2023-04-30 con 400,000 (superior a los 380,000 de junio).

        $this->actingAs($ana);

        $response = $this->post("/proyectos/{$proyecto005->id_proyecto}/avances", [
            'fecha_reporte' => '2023-04-30',
            'presupuesto_planificado' => 250000.00,
            'gasto_acumulado' => 400000.00,
        ]);

        $response->assertSessionHas('error');
        $error = session('error');
        $this->assertTrue(str_contains($error, 'Regla R2') || str_contains($error, 'superior'));
    }

    /**
     * 6. Unicidad UQ: Rechaza corte con fecha ya existente en el mismo proyecto.
     */
    public function test_unicidad_fecha_reporte_por_proyecto(): void
    {
        $ana = Responsable::where('usuario', 'aquispe')->first();
        $proyecto005 = Proyecto::where('codigo_pgd', '005')->first();
        // 2023-03-31 ya existe

        $this->actingAs($ana);

        $response = $this->post("/proyectos/{$proyecto005->id_proyecto}/avances", [
            'fecha_reporte' => '2023-03-31',
            'presupuesto_planificado' => 200000.00,
            'gasto_acumulado' => 150000.00,
        ]);

        $response->assertSessionHas('error');
        $error = session('error');
        $this->assertTrue(str_contains($error, 'Ya existe un corte') || str_contains($error, '1062'));
    }

    /**
     * 7. Bloqueo en proyecto cerrado: rechaza registrar cortes.
     */
    public function test_rechazo_registro_corte_en_proyecto_cerrado(): void
    {
        $admin = Responsable::where('usuario', 'msalas')->first();
        $proyectoCerrado = Proyecto::where('estado', 'Cerrado')->first();

        $this->actingAs($admin);

        $response = $this->post("/proyectos/{$proyectoCerrado->id_proyecto}/avances", [
            'fecha_reporte' => '2024-12-31',
            'presupuesto_planificado' => 100000.00,
            'gasto_acumulado' => 100000.00,
        ]);

        $response->assertStatus(403);
    }

    /**
     * 8. Responsable ajeno recibe 403 al intentar registrar avances.
     */
    public function test_responsable_ajeno_recibe_403_al_registrar_avance(): void
    {
        $luis = Responsable::where('usuario', 'lrojas')->first();
        $proyecto005 = Proyecto::where('codigo_pgd', '005')->first(); // Titular: Ana Quispe

        $this->actingAs($luis);

        $response = $this->post("/proyectos/{$proyecto005->id_proyecto}/avances", [
            'fecha_reporte' => '2024-12-31',
            'presupuesto_planificado' => 500000.00,
            'gasto_acumulado' => 460000.00,
        ]);

        $response->assertStatus(403);
    }

    /**
     * 9. Perfil Consulta recibe 403.
     */
    public function test_perfil_consulta_recibe_403_en_avances(): void
    {
        $consulta = Responsable::where('usuario', 'cgarcia')->first();
        $proyecto005 = Proyecto::where('codigo_pgd', '005')->first();

        $this->actingAs($consulta);

        $response = $this->post("/proyectos/{$proyecto005->id_proyecto}/avances", [
            'fecha_reporte' => '2024-12-31',
            'presupuesto_planificado' => 500000.00,
            'gasto_acumulado' => 460000.00,
        ]);

        $response->assertStatus(403);
    }

    /**
     * 10. Titular edita corte presupuestal con montos válidos.
     */
    public function test_titular_edita_corte_presupuestal(): void
    {
        $ana = Responsable::where('usuario', 'aquispe')->first();
        $proyecto005 = Proyecto::where('codigo_pgd', '005')->first();
        // Tomamos el último corte al 2024-09-30 (plan 480k, gasto 450k)
        $corte = $proyecto005->avances()->where('fecha_reporte', '2024-09-30')->first();

        $this->actingAs($ana);

        $response = $this->put("/avances/{$corte->id_avance}", [
            'presupuesto_planificado' => 490000.00,
            'gasto_acumulado' => 460000.00,
        ]);

        $response->assertRedirect("/proyectos/{$proyecto005->id_proyecto}");
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('avance_presupuestal', [
            'id_avance' => $corte->id_avance,
            'presupuesto_planificado' => 490000.00,
            'gasto_acumulado' => 460000.00,
        ]);
    }

    /**
     * 11. Administrador puede eliminar corte presupuestal en proyecto no cerrado.
     */
    public function test_administrador_elimina_corte_presupuestal(): void
    {
        $admin = Responsable::where('usuario', 'msalas')->first();
        $proyecto005 = Proyecto::where('codigo_pgd', '005')->first();

        // Insertamos un corte temporal para eliminar
        DB::connection('escritor')->statement('SET @id_responsable = 1;');
        $idAvance = DB::connection('escritor')->table('avance_presupuestal')->insertGetId([
            'id_proyecto' => $proyecto005->id_proyecto,
            'fecha_reporte' => '2024-12-31',
            'presupuesto_planificado' => 490000.00,
            'gasto_acumulado' => 470000.00,
        ]);
        DB::connection('escritor')->statement('SET @id_responsable = NULL;');

        $this->actingAs($admin);

        $response = $this->delete("/avances/{$idAvance}");

        $response->assertRedirect("/proyectos/{$proyecto005->id_proyecto}");
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('avance_presupuestal', [
            'id_avance' => $idAvance,
        ]);
    }
}
