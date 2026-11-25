<?php

namespace Tests\Feature;

use App\Models\Proyecto;
use App\Models\Responsable;
use App\Services\DatabaseTransactionService as Tx;
use App\Services\Gestion\AlertasService;
use App\Services\Gestion\FichaGerencialService;
use App\Services\Gestion\PriorizacionService;
use App\Services\Gestion\ValorGanadoService;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/** Segundo grupo de mejoras: priorización, riesgos, valor ganado, presupuesto, alertas, documentos y beneficios. */
class GestionGerencialTest extends TestCase
{
    /** @var int[] Proyectos aislados creados por la prueba en curso. */
    private array $creados = [];

    /** Retira los proyectos de prueba para no alterar la cartera canónica que usan las demás suites. */
    protected function tearDown(): void
    {
        if ($this->creados !== []) {
            $db = DB::connection('mysql'); // Cuenta migradora: mantenimiento exclusivo de esquemas _test.
            if (str_ends_with($db->getDatabaseName(), '_test')) {
                $ids = implode(',', array_map('intval', $this->creados));
                $db->statement('SET @id_responsable = 1, @motivo_cambio = ?', ['Limpieza de prueba de gestión gerencial']);
                $db->statement('SET FOREIGN_KEY_CHECKS = 0');
                $db->statement("DELETE FROM hito_peso WHERE id_hito IN (SELECT id_hito FROM hito WHERE id_proyecto IN ({$ids}))");
                foreach (['evaluacion_priorizacion', 'riesgo_proyecto', 'compromiso_presupuestal', 'proyecto_presupuesto_version',
                    'documento_proyecto', 'beneficio_proyecto', 'avance_presupuestal', 'hito_linea_base_version', 'hito', 'proyecto'] as $tabla) {
                    $db->statement("DELETE FROM {$tabla} WHERE id_proyecto IN ({$ids})");
                }
                $db->statement('SET FOREIGN_KEY_CHECKS = 1');
                $db->statement('SET @id_responsable = NULL, @motivo_cambio = NULL');
            }
            $this->creados = [];
        }
        parent::tearDown();
    }

    private function usuario(string $usuario): Responsable
    {
        return Responsable::where('usuario', $usuario)->firstOrFail();
    }

    /** Proyecto aislado de prueba: monto 1 000, hitos de línea base 2 cerrados y 1 abierto, corte al 2024-03-31 (plan 600, gasto 400). */
    private function proyectoDePrueba(string $titular = 'aquispe'): Proyecto
    {
        $codigo = 'T'.strtoupper(Str::random(6));
        $idTitular = $this->usuario($titular)->id_responsable;
        $id = Tx::run(1, function ($db) use ($codigo, $idTitular) {
            $id = $db->table('proyecto')->insertGetId([
                'codigo_pgd' => $codigo, 'nombre' => "Proyecto de prueba {$codigo}", 'monto' => 1000, 'anio_inicio' => 2024, 'anio_fin' => 2025,
                'estado' => 'En curso', 'id_objetivo' => $db->table('objetivo_gd')->min('id_objetivo'), 'id_responsable' => $idTitular,
            ]);
            foreach ([['H1', '2024-01-15', '2024-01-10'], ['H2', '2024-02-15', '2024-02-10'], ['H3', '2024-06-30', null]] as [$d, $prev, $cierre]) {
                $db->table('hito')->insert(['id_proyecto' => $id, 'descripcion' => "{$d} {$codigo}", 'fecha_prevista' => $prev, 'fecha_cierre' => $cierre, 'en_linea_base' => 1]);
            }
            $db->table('avance_presupuestal')->insert(['id_proyecto' => $id, 'fecha_reporte' => '2024-03-31', 'presupuesto_planificado' => 600, 'gasto_acumulado' => 400]);
            return $id;
        }, 'Proyecto aislado de prueba de gestión gerencial');
        $this->creados[] = $id;

        return Proyecto::findOrFail($id);
    }

    private function restaurarPesosCriterios(): void
    {
        Tx::run(1, fn ($db) => $db->table('criterio_priorizacion')->update(['peso' => 1.00, 'activo' => 1]), 'Restauración de prueba');
    }

    // ---------------------------------------------------------------- Priorización

    public function test_ranking_pondera_las_calificaciones_con_los_pesos_y_es_explicable(): void
    {
        $admin = $this->usuario('msalas');
        $criterios = DB::table('criterio_priorizacion')->orderBy('orden')->pluck('id_criterio')->all();
        [$a, $b] = [$criterios[0], $criterios[1]];
        $pesos = array_fill_keys($criterios, 0);
        $pesos[$a] = 3;
        $pesos[$b] = 1;
        $x = $this->proyectoDePrueba();
        $y = $this->proyectoDePrueba('lrojas');

        try {
            $this->actingAs($admin)->putJson('/priorizacion/pesos', [
                'pesos' => $pesos, 'activos' => array_fill_keys($criterios, 1), 'motivo' => 'Pesos de prueba',
            ])->assertOk();
            $calificar = fn ($p, $pa, $pb) => $this->actingAs($admin)->postJson("/priorizacion/proyectos/{$p->id_proyecto}", [
                'puntajes' => [$a => $pa, $b => $pb],
                'sustentos' => [$a => 'Sustento verificable de prueba A', $b => 'Sustento verificable de prueba B'],
            ])->assertOk();
            $calificar($x, 5, 1);
            $calificar($y, 1, 5);

            $ranking = app(PriorizacionService::class)->ranking();
            $fx = $ranking['filas']->firstWhere('id_proyecto', $x->id_proyecto);
            $fy = $ranking['filas']->firstWhere('id_proyecto', $y->id_proyecto);
            $this->assertEqualsWithDelta(75.0, $fx['puntaje'], 0.01); // 3 × (5−1)/4 ÷ 4 × 100
            $this->assertEqualsWithDelta(25.0, $fy['puntaje'], 0.01);
            $this->assertEqualsWithDelta($fx['puntaje'], array_sum($fx['aportes']), 0.01, 'Los aportes por criterio deben sumar el puntaje.');
            $this->assertLessThan(
                $ranking['filas']->search(fn ($f) => $f['id_proyecto'] === $y->id_proyecto),
                $ranking['filas']->search(fn ($f) => $f['id_proyecto'] === $x->id_proyecto)
            );

            $this->actingAs($this->usuario('cgarcia'))->get('/priorizacion')->assertOk()->assertSee($x->codigo_pgd)->assertDontSee('Guardar pesos');
        } finally {
            $this->restaurarPesosCriterios();
        }
    }

    public function test_solo_el_administrador_califica_y_exige_sustento(): void
    {
        $p = $this->proyectoDePrueba();
        $criterio = DB::table('criterio_priorizacion')->orderBy('orden')->value('id_criterio');
        $cuerpo = ['puntajes' => [$criterio => 4], 'sustentos' => [$criterio => 'Sustento suficiente de prueba']];

        $this->actingAs($this->usuario('aquispe'))->postJson("/priorizacion/proyectos/{$p->id_proyecto}", $cuerpo)->assertForbidden();
        $this->actingAs($this->usuario('aquispe'))->putJson('/priorizacion/pesos', ['pesos' => [$criterio => 5], 'motivo' => 'Intento'])->assertForbidden();
        $this->actingAs($this->usuario('msalas'))->postJson("/priorizacion/proyectos/{$p->id_proyecto}", ['puntajes' => [$criterio => 4], 'sustentos' => [$criterio => 'corto']])
            ->assertUnprocessable()->assertJsonValidationErrors("sustentos.{$criterio}");

        try {
            Tx::run($this->usuario('aquispe')->id_responsable, fn ($db) => $db->table('evaluacion_priorizacion')->insert([
                'id_proyecto' => $p->id_proyecto, 'id_criterio' => $criterio, 'puntaje' => 5, 'sustento' => 'Intento directo en la base', 'id_responsable' => 2,
            ]), 'Intento');
            $this->fail('La base de datos debía rechazar la calificación de un Responsable.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('Solo un Administrador', $e->getMessage());
        }
    }

    // ---------------------------------------------------------------- Riesgos

    public function test_titular_registra_riesgos_solo_en_sus_proyectos_y_genera_alerta_critica(): void
    {
        $p = $this->proyectoDePrueba('aquispe');
        $riesgo = ['tipo' => 'Riesgo', 'descripcion' => 'Demora del proveedor de prueba', 'probabilidad' => 5, 'impacto' => 4,
            'id_responsable' => $this->usuario('aquispe')->id_responsable, 'plan_respuesta' => 'Aplicar penalidad y proveedor alterno'];

        $this->actingAs($this->usuario('aquispe'))->postJson("/proyectos/{$p->id_proyecto}/riesgos", $riesgo)->assertCreated();
        $this->actingAs($this->usuario('lrojas'))->postJson("/proyectos/{$p->id_proyecto}/riesgos", $riesgo)->assertForbidden();
        $this->actingAs($this->usuario('cgarcia'))->postJson("/proyectos/{$p->id_proyecto}/riesgos", $riesgo)->assertForbidden();
        $this->actingAs($this->usuario('aquispe'))->postJson("/proyectos/{$p->id_proyecto}/riesgos", ['tipo' => 'Incidencia', 'probabilidad' => 1] + $riesgo)->assertCreated();

        $guardados = DB::table('riesgo_proyecto')->where('id_proyecto', $p->id_proyecto)->orderBy('id_riesgo')->get();
        $this->assertSame(20, (int) $guardados[0]->exposicion);
        $this->assertSame(5, (int) $guardados[1]->probabilidad, 'Una incidencia ya ocurrió: probabilidad 5.');
        $this->assertTrue(DB::table('bitacora_cambio')->where('tabla_afectada', 'riesgo_proyecto')->where('id_registro', $guardados[0]->id_riesgo)->exists());

        try {
            Tx::run($this->usuario('lrojas')->id_responsable, fn ($db) => $db->table('riesgo_proyecto')->where('id_riesgo', $guardados[0]->id_riesgo)->update(['estado' => 'Cerrado']), 'Intento');
            $this->fail('La base de datos debía impedir que otro responsable modifique el riesgo.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('Responsable titular', $e->getMessage());
        }

        $alertas = app(AlertasService::class)->generar('2024-03-31')['alertas']->where('id_proyecto', $p->id_proyecto);
        $this->assertTrue($alertas->contains('tipo', 'Riesgo crítico'));
        $this->actingAs($this->usuario('cgarcia'))->get('/riesgos')->assertOk()->assertSee('Demora del proveedor de prueba');

        $this->actingAs($this->usuario('aquispe'))->putJson("/riesgos/{$guardados[0]->id_riesgo}", ['estado' => 'Cerrado', 'motivo' => 'Proveedor entregó'] + $riesgo)->assertOk();
        $this->assertNotNull(DB::table('riesgo_proyecto')->where('id_riesgo', $guardados[0]->id_riesgo)->value('fecha_cierre'));
    }

    // ---------------------------------------------------------------- Avance físico y valor ganado

    public function test_valor_ganado_solo_con_pesos_aprobados_y_calculo_correcto(): void
    {
        $p = $this->proyectoDePrueba();
        $vg = app(ValorGanadoService::class);

        $antes = $vg->calcular($p->id_proyecto);
        $this->assertNull($antes['spi']);
        $this->assertStringContainsString('Faltan pesos en 3 de 3', $antes['motivo_sin_indices']);
        $this->assertEqualsWithDelta(66.67, $antes['avance_fisico'], 0.01);

        $hitos = DB::table('hito')->where('id_proyecto', $p->id_proyecto)->orderBy('fecha_prevista')->pluck('id_hito')->all();
        $pesos = [$hitos[0] => 30, $hitos[1] => 20, $hitos[2] => 50];
        $this->actingAs($this->usuario('aquispe'))->putJson("/proyectos/{$p->id_proyecto}/pesos-hitos", ['pesos' => $pesos, 'motivo' => 'Intento'])->assertForbidden();
        $this->actingAs($this->usuario('msalas'))->putJson("/proyectos/{$p->id_proyecto}/pesos-hitos", ['pesos' => [$hitos[0] => 30, $hitos[1] => 20, $hitos[2] => 40], 'motivo' => 'Suma 90'])
            ->assertUnprocessable()->assertJsonValidationErrors('pesos');
        $this->actingAs($this->usuario('msalas'))->putJson("/proyectos/{$p->id_proyecto}/pesos-hitos", ['pesos' => $pesos, 'motivo' => 'Pesos por esfuerzo'])->assertOk();

        $r = $vg->calcular($p->id_proyecto);
        $this->assertTrue($r['ponderado']);
        $this->assertEqualsWithDelta(50.0, $r['avance_fisico'], 0.001);
        $this->assertEqualsWithDelta(500.0, $r['ev'], 0.001);   // 50 % × 1 000
        $this->assertEqualsWithDelta(0.833, $r['spi'], 0.001);   // 500 / 600
        $this->assertEqualsWithDelta(1.25, $r['cpi'], 0.001);    // 500 / 400
        $this->assertEqualsWithDelta(800.0, $r['eac'], 0.01);    // 1 000 / 1,25
        $this->assertEqualsWithDelta(200.0, $r['vac'], 0.01);
    }

    // ---------------------------------------------------------------- Presupuesto vigente y comprometido

    public function test_compromisos_acotados_al_presupuesto_y_versiones_del_monto(): void
    {
        $p = $this->proyectoDePrueba();
        $admin = $this->usuario('msalas');
        $comp = fn ($doc, $monto) => $this->actingAs($admin)->postJson("/proyectos/{$p->id_proyecto}/compromisos", [
            'documento' => $doc, 'descripcion' => 'Orden de compra de prueba', 'monto' => $monto, 'fecha' => '2024-03-01',
        ]);

        $comp('OC-1', 700)->assertCreated();
        $comp('OC-2', 400)->assertUnprocessable()->assertJsonFragment(['error' => 'Los compromisos vigentes superarian el presupuesto vigente del proyecto.']);
        $this->actingAs($this->usuario('aquispe'))->postJson("/proyectos/{$p->id_proyecto}/compromisos", ['documento' => 'X', 'descripcion' => 'Intento', 'monto' => 1, 'fecha' => '2024-03-01'])->assertForbidden();

        try {
            Tx::run(1, fn ($db) => $db->table('proyecto')->where('id_proyecto', $p->id_proyecto)->update(['monto' => 650]), 'Reducción de prueba');
            $this->fail('No debía poder reducirse el monto por debajo de lo comprometido.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('compromisos vigentes', $e->getMessage());
        }

        $id = DB::table('compromiso_presupuestal')->where('id_proyecto', $p->id_proyecto)->where('documento', 'OC-1')->value('id_compromiso');
        $this->actingAs($admin)->patchJson("/compromisos/{$id}/anular", ['motivo' => 'Orden reemplazada'])->assertOk();
        $comp('OC-2', 400)->assertCreated();

        Tx::run(1, fn ($db) => $db->table('proyecto')->where('id_proyecto', $p->id_proyecto)->update(['monto' => 1500]), 'Ampliación aprobada');
        $versiones = DB::table('proyecto_presupuesto_version')->where('id_proyecto', $p->id_proyecto)->orderBy('version')->get();
        $this->assertCount(2, $versiones);
        $this->assertEquals(1000, (float) $versiones[1]->monto_anterior);
        $this->assertEquals(1500, (float) $versiones[1]->monto);
        $this->assertSame('Ampliación aprobada', $versiones[1]->motivo);

        $pp = app(FichaGerencialService::class)->datos($p->id_proyecto)['presupuesto'];
        $this->assertEquals(1000, $pp['inicial']);
        $this->assertEquals(1500, $pp['vigente']);
        $this->assertEquals(400, $pp['comprometido']);
        $this->assertEquals(400, $pp['devengado']);
        $this->assertEquals(1100, $pp['por_comprometer']);
    }

    // ---------------------------------------------------------------- Alertas preventivas

    public function test_alertas_preventivas_usan_umbrales_configurables(): void
    {
        $p = $this->proyectoDePrueba(); // H3 de línea base abierto, previsto el 2024-06-30
        $servicio = app(AlertasService::class);
        $tipos = fn (string $fecha) => $servicio->generar($fecha)['alertas']->where('id_proyecto', $p->id_proyecto)->pluck('tipo');

        $this->assertContains('Hito próximo a vencer', $tipos('2024-06-20')->all());
        $this->assertNotContains('Hito próximo a vencer', $tipos('2024-06-01')->all(), 'A 29 días no avisa con el umbral de 15.');
        $this->assertContains('Hito vencido', $tipos('2024-07-05')->all());
        $this->assertContains('Proyecto sin actualizar', $tipos('2024-09-30')->all(), 'Último corte del 2024-03-31: más de 100 días.');
        $this->assertContains('Desfase de devengado', $tipos('2024-04-01')->all(), 'Gasto 400 frente a plan 600: −33 %.');

        $this->actingAs($this->usuario('aquispe'))->putJson('/alertas/parametros', ['valores' => ['dias_aviso_hito' => 30], 'motivo' => 'Intento'])->assertForbidden();
        try {
            $this->actingAs($this->usuario('msalas'))->putJson('/alertas/parametros', ['valores' => ['dias_aviso_hito' => 30], 'motivo' => 'Aviso con un mes'])->assertOk();
            $this->assertContains('Hito próximo a vencer', $tipos('2024-06-01')->all());
            $this->actingAs($this->usuario('cgarcia'))->get('/alertas?fecha=2024-06-20')->assertOk()->assertSee($p->codigo_pgd)->assertDontSee('Guardar umbrales');
        } finally {
            Tx::run(1, fn ($db) => $db->table('parametro_alerta')->where('clave', 'dias_aviso_hito')->update(['valor' => 15]), 'Restauración de prueba');
        }
    }

    // ---------------------------------------------------------------- Documentos y beneficios

    public function test_documentos_inmutables_con_huella_y_permisos(): void
    {
        Storage::fake('local');
        $p = $this->proyectoDePrueba('aquispe');
        $archivo = UploadedFile::fake()->createWithContent('acta.pdf', '%PDF-1.4 acta de prueba');

        $this->actingAs($this->usuario('aquispe'))->post("/proyectos/{$p->id_proyecto}/documentos", [
            'tipo' => 'Acta', 'titulo' => 'Acta de conformidad de prueba', 'archivo' => $archivo,
        ], ['Accept' => 'application/json'])->assertCreated();
        $this->actingAs($this->usuario('lrojas'))->post("/proyectos/{$p->id_proyecto}/documentos", [
            'tipo' => 'Acta', 'titulo' => 'Intento ajeno', 'archivo' => UploadedFile::fake()->createWithContent('x.pdf', 'x'),
        ], ['Accept' => 'application/json'])->assertForbidden();
        $this->actingAs($this->usuario('aquispe'))->post("/proyectos/{$p->id_proyecto}/documentos", [
            'tipo' => 'Acta', 'titulo' => 'Ejecutable', 'archivo' => UploadedFile::fake()->create('virus.exe', 10),
        ], ['Accept' => 'application/json'])->assertUnprocessable();

        $doc = DB::table('documento_proyecto')->where('id_proyecto', $p->id_proyecto)->first();
        $this->assertSame(hash('sha256', '%PDF-1.4 acta de prueba'), $doc->hash_sha256);
        Storage::disk('local')->assertExists($doc->ruta);
        $this->actingAs($this->usuario('cgarcia'))->get("/documentos/{$doc->id_documento}")->assertOk()->assertDownload('acta.pdf');

        try {
            DB::connection('escritor')->update('UPDATE documento_proyecto SET titulo = ? WHERE id_documento = ?', ['alterado', $doc->id_documento]);
            $this->fail('La aplicación no debe poder modificar un documento registrado.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('denied', $e->getMessage());
        }
    }

    public function test_beneficios_planificados_y_medidos_y_regla_de_cierre(): void
    {
        $p = $this->proyectoDePrueba('aquispe');
        $this->actingAs($this->usuario('aquispe'))->postJson("/proyectos/{$p->id_proyecto}/beneficios", [
            'descripcion' => 'Menos días para atender trámites', 'indicador' => 'Tiempo de atención', 'unidad' => 'días', 'linea_base' => 10, 'meta' => 5,
        ])->assertCreated();
        $id = DB::table('beneficio_proyecto')->where('id_proyecto', $p->id_proyecto)->value('id_beneficio');
        $this->actingAs($this->usuario('aquispe'))->patchJson("/beneficios/{$id}/medicion", ['valor_logrado' => 6, 'fecha_medicion' => '2024-12-31'])->assertOk();

        $b = app(FichaGerencialService::class)->datos($p->id_proyecto)['beneficios']->first();
        $this->assertEqualsWithDelta(80.0, $b->logro, 0.01); // (6 − 10) / (5 − 10)

        try {
            Tx::run(1, fn ($db) => $db->table('parametro_alerta')->where('clave', 'exigir_acta_cierre')->update(['valor' => 1]), 'Prueba de regla de cierre');
            $this->actingAs($this->usuario('msalas'))->postJson("/proyectos/{$p->id_proyecto}/cerrar", [])
                ->assertUnprocessable()->assertJsonFragment(['error' => 'falta un acta adjunta.']);
        } finally {
            Tx::run(1, fn ($db) => $db->table('parametro_alerta')->where('clave', 'exigir_acta_cierre')->update(['valor' => 0]), 'Restauración de prueba');
        }

        $this->actingAs($this->usuario('cgarcia'))->get("/proyectos/{$p->id_proyecto}")->assertOk()
            ->assertSee('Gestión gerencial del proyecto')
            ->assertSee('Tiempo de atención')
            ->assertSee('Versiones del presupuesto')
            ->assertSee('Beneficio #'.$id);
    }
}
