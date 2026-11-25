<?php

namespace Tests\Feature;

use App\Models\Hito;
use App\Models\Proyecto;
use App\Models\Responsable;
use App\Services\DatabaseTransactionService;
use App\Services\IndicadoresService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Prioridad 1 de la auditoría: la línea base solo la modifica un Administrador y cada cambio
 * queda versionado. Cubre la evasión "retirar, reprogramar y reincorporar" del Responsable.
 */
class LineaBaseVersionadaTest extends TestCase
{
    private function hitoVencidoDe024(): Hito
    {
        $proyecto = Proyecto::where('codigo_pgd', '024')->firstOrFail();
        return Hito::where('id_proyecto', $proyecto->id_proyecto)
            ->where('en_linea_base', 1)->whereNull('fecha_cierre')->firstOrFail();
    }

    private function retraso(): array
    {
        $k = app(IndicadoresService::class)
            ->calcularCartera(['modo' => IndicadoresService::MODO_CORTE_COMUN, 'fecha_corte' => '2024-09-30'])['resumen']['proyectos_retraso'];
        return [$k['numerador'], $k['denominador']];
    }

    private function editarHito(Responsable $usuario, Hito $hito, array $cambios)
    {
        $hito->refresh();
        return $this->actingAs($usuario)->putJson("/hitos/{$hito->id_hito}", array_merge([
            'revision_esperada' => DB::table('control_revision')->where('id', 1)->value('version'),
            'descripcion' => $hito->descripcion,
            'fecha_prevista' => $hito->fecha_prevista->format('Y-m-d'),
            'fecha_cierre' => $hito->fecha_cierre?->format('Y-m-d'),
            'evidencia' => $hito->evidencia,
            'en_linea_base' => $hito->en_linea_base ? 1 : 0,
        ], $cambios));
    }

    private function versiones(int $idHito)
    {
        return DB::connection('lector')->table('hito_linea_base_version')
            ->where('id_hito', $idHito)->orderBy('version')->get();
    }

    private function restaurar(Hito $hito, string $fecha, int $enLineaBase = 1): void
    {
        DatabaseTransactionService::run(1, fn ($db) => $db->table('hito')->where('id_hito', $hito->id_hito)
            ->update(['fecha_prevista' => $fecha, 'en_linea_base' => $enLineaBase]), 'Restauración de prueba de línea base');
    }

    public function test_responsable_no_puede_retirar_un_hito_de_la_linea_base(): void
    {
        $aquispe = Responsable::where('usuario', 'aquispe')->firstOrFail();
        $hito = $this->hitoVencidoDe024();
        $antes = $this->retraso();

        $this->editarHito($aquispe, $hito, ['en_linea_base' => 0, 'motivo_cambio' => 'Se retira temporalmente'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('en_linea_base');

        $this->assertTrue((bool) $hito->fresh()->en_linea_base);
        $this->assertSame($antes, $this->retraso(), 'El retraso no debe cambiar si se rechaza el retiro.');
    }

    public function test_evasion_retirar_reprogramar_y_reincorporar_queda_bloqueada(): void
    {
        $aquispe = Responsable::where('usuario', 'aquispe')->firstOrFail();
        $hito = $this->hitoVencidoDe024();
        $fechaOriginal = $hito->fecha_prevista->format('Y-m-d');
        $antes = $this->retraso();

        $this->editarHito($aquispe, $hito, ['en_linea_base' => 0, 'motivo_cambio' => 'Se retira temporalmente'])->assertUnprocessable();
        $this->editarHito($aquispe, $hito, ['fecha_prevista' => '2025-12-31', 'motivo_cambio' => 'Nueva fecha'])->assertUnprocessable();
        $this->editarHito($aquispe, $hito, ['en_linea_base' => 1, 'motivo_cambio' => 'Se reincorpora'])->assertOk();

        $hito->refresh();
        $this->assertSame($fechaOriginal, $hito->fecha_prevista->format('Y-m-d'));
        $this->assertTrue((bool) $hito->en_linea_base);
        $this->assertSame($antes, $this->retraso());
    }

    public function test_responsable_no_puede_incorporar_un_hito_extraordinario_a_la_linea_base(): void
    {
        $aquispe = Responsable::where('usuario', 'aquispe')->firstOrFail();
        $proyecto = Proyecto::where('codigo_pgd', '024')->firstOrFail();

        $this->actingAs($aquispe)->postJson("/proyectos/{$proyecto->id_proyecto}/hitos", [
            'descripcion' => 'Hito extraordinario de prueba LB',
            'fecha_prevista' => '2026-12-15',
            'en_linea_base' => 0,
        ])->assertCreated();
        $hito = Hito::where('descripcion', 'Hito extraordinario de prueba LB')->firstOrFail();

        try {
            $this->editarHito($aquispe, $hito, ['en_linea_base' => 1, 'motivo_cambio' => 'Se incorpora a la línea base'])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('en_linea_base');
            $this->assertFalse((bool) $hito->fresh()->en_linea_base);
            $this->assertCount(0, $this->versiones($hito->id_hito));
        } finally {
            DatabaseTransactionService::run(1, fn ($db) => $db->table('hito')->where('id_hito', $hito->id_hito)->delete(), 'Limpieza de prueba de línea base');
        }
    }

    public function test_responsable_crea_hito_de_linea_base_y_queda_version_de_alta(): void
    {
        $aquispe = Responsable::where('usuario', 'aquispe')->firstOrFail();
        $proyecto = Proyecto::where('codigo_pgd', '024')->firstOrFail();

        $this->actingAs($aquispe)->postJson("/proyectos/{$proyecto->id_proyecto}/hitos", [
            'descripcion' => 'Hito planificado de prueba LB',
            'fecha_prevista' => '2026-11-30',
            'en_linea_base' => 1,
        ])->assertCreated();
        $hito = Hito::where('descripcion', 'Hito planificado de prueba LB')->firstOrFail();

        $versiones = $this->versiones($hito->id_hito);
        $this->assertCount(1, $versiones);
        $this->assertSame('ALTA', $versiones[0]->accion);
        $this->assertSame('2026-11-30', $versiones[0]->fecha_prevista);
        $this->assertSame((int) $aquispe->id_responsable, (int) $versiones[0]->id_responsable);

        // Un Responsable no puede eliminarlo de la base aunque salte la interfaz.
        try {
            DatabaseTransactionService::run($aquispe->id_responsable, fn ($db) => $db->table('hito')->where('id_hito', $hito->id_hito)->delete(), 'Intento de eliminar');
            $this->fail('El trigger debía impedir que un Responsable elimine un hito de línea base.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('Solo un Administrador puede eliminar', $e->getMessage());
        }

        DatabaseTransactionService::run(1, fn ($db) => $db->table('hito')->where('id_hito', $hito->id_hito)->delete(), 'Limpieza de prueba de línea base');
        $this->assertSame(['ALTA', 'ELIMINACION'], $this->versiones($hito->id_hito)->pluck('accion')->all());
    }

    public function test_admin_reprograma_con_motivo_y_queda_version_con_fecha_anterior(): void
    {
        $admin = Responsable::where('usuario', 'msalas')->firstOrFail();
        $hito = $this->hitoVencidoDe024();
        $fechaOriginal = $hito->fecha_prevista->format('Y-m-d');
        $previas = $this->versiones($hito->id_hito)->count();

        try {
            $this->editarHito($admin, $hito, ['fecha_prevista' => '2025-12-31', 'motivo_cambio' => 'Ampliación aprobada por el Comité (acta 12)'])
                ->assertOk();

            $ultima = $this->versiones($hito->id_hito)->last();
            $this->assertSame($previas + 1, (int) $ultima->version);
            $this->assertSame('REPROGRAMACION', $ultima->accion);
            $this->assertSame($fechaOriginal, $ultima->fecha_prevista_anterior);
            $this->assertSame('2025-12-31', $ultima->fecha_prevista);
            $this->assertSame('Ampliación aprobada por el Comité (acta 12)', $ultima->motivo);
            $this->assertSame((int) $admin->id_responsable, (int) $ultima->id_responsable);

            // La ficha muestra la reprogramación a cualquier perfil, incluido Consulta.
            $consulta = Responsable::where('usuario', 'cgarcia')->firstOrFail();
            $this->actingAs($consulta)->get("/proyectos/{$hito->id_proyecto}")
                ->assertOk()
                ->assertSee('Historial de la línea base')
                ->assertSee('Reprogramación')
                ->assertSee('Ampliación aprobada por el Comité (acta 12)')
                ->assertSee('Original:');
        } finally {
            $this->restaurar($hito, $fechaOriginal);
        }
    }

    public function test_trigger_rechaza_cambios_de_linea_base_de_un_actor_no_administrador(): void
    {
        $aquispe = Responsable::where('usuario', 'aquispe')->firstOrFail();
        $hito = $this->hitoVencidoDe024();

        foreach ([['en_linea_base' => 0], ['fecha_prevista' => '2025-12-31']] as $cambio) {
            try {
                DatabaseTransactionService::run($aquispe->id_responsable,
                    fn ($db) => $db->table('hito')->where('id_hito', $hito->id_hito)->update($cambio), 'Motivo cualquiera');
                $this->fail('El trigger debía rechazar el cambio de línea base de un Responsable: '.json_encode($cambio));
            } catch (QueryException $e) {
                $this->assertStringContainsString('Solo un Administrador puede modificar la linea base', $e->getMessage());
            }
        }

        $this->assertTrue((bool) $hito->fresh()->en_linea_base);
    }

    public function test_versiones_de_linea_base_son_inmutables_para_las_cuentas_de_la_aplicacion(): void
    {
        $escritor = DB::connection('escritor');
        foreach ([
            fn () => $escritor->insert("INSERT INTO hito_linea_base_version (id_hito, id_proyecto, version, accion, id_responsable) VALUES (1, 1, 999, 'ALTA', 1)"),
            fn () => $escritor->update('UPDATE hito_linea_base_version SET motivo = ? WHERE id_version = 1', ['alterado']),
            fn () => $escritor->delete('DELETE FROM hito_linea_base_version WHERE id_version = 1'),
        ] as $intento) {
            try {
                $intento();
                $this->fail('La cuenta de escritura no debe alterar las versiones de línea base.');
            } catch (QueryException $e) {
                $this->assertStringContainsString('denied', $e->getMessage());
            }
        }
    }
}
