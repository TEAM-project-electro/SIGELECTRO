<?php

namespace Tests\Feature;

use App\Models\Hito;
use App\Models\Proyecto;
use App\Models\Responsable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Verifica la subsanación de los hallazgos técnicos P0-1, P0-2 y P1-7 de la auditoría del 25/11/2026. */
class SubsanacionAuditoriaEduardoTest extends TestCase
{
    public function test_responsable_no_puede_reprogramar_fecha_prevista_de_hito_en_linea_base(): void
    {
        $responsable = Responsable::where('usuario', 'aquispe')->firstOrFail();
        $proyecto024 = Proyecto::where('codigo_pgd', '024')->firstOrFail();
        $hitoLb = Hito::where('id_proyecto', $proyecto024->id_proyecto)
            ->where('en_linea_base', 1)
            ->whereNull('fecha_cierre')
            ->firstOrFail();

        $fechaOriginal = $hitoLb->fecha_prevista->format('Y-m-d');
        $revision = DB::table('control_revision')->where('id', 1)->value('version');

        $this->actingAs($responsable)
            ->putJson("/hitos/{$hitoLb->id_hito}", [
                'revision_esperada' => $revision,
                'descripcion' => $hitoLb->descripcion,
                'fecha_prevista' => '2025-12-31',
                'fecha_cierre' => null,
                'evidencia' => $hitoLb->evidencia,
                'en_linea_base' => 1,
                'motivo_cambio' => 'Intento de mover línea base sin aprobación',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('fecha_prevista');

        $this->assertSame($fechaOriginal, Hito::findOrFail($hitoLb->id_hito)->fecha_prevista->format('Y-m-d'));
    }

    public function test_admin_necesita_motivo_y_trigger_protege_fecha_prevista_de_linea_base(): void
    {
        $admin = Responsable::where('usuario', 'msalas')->firstOrFail();
        $hitoLb = Hito::where('en_linea_base', 1)->whereHas('proyecto', fn ($q) => $q->where('estado', 'En curso'))->firstOrFail();
        $fechaOriginal = $hitoLb->fecha_prevista->format('Y-m-d');
        $revision = DB::table('control_revision')->where('id', 1)->value('version');

        // Sin motivo: rechazado por FormRequest
        $this->actingAs($admin)
            ->putJson("/hitos/{$hitoLb->id_hito}", [
                'revision_esperada' => $revision,
                'descripcion' => $hitoLb->descripcion,
                'fecha_prevista' => '2025-11-30',
                'fecha_cierre' => $hitoLb->fecha_cierre?->format('Y-m-d'),
                'evidencia' => $hitoLb->evidencia,
                'en_linea_base' => 1,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('motivo_cambio');

        // Directo en BD sin @motivo_cambio: rechazado por trigger trg_hito_bu
        $db = DB::connection('escritor');
        $db->statement('SET @id_responsable = 1, @motivo_cambio = NULL');
        try {
            $db->table('hito')->where('id_hito', $hitoLb->id_hito)->update(['fecha_prevista' => '2025-11-30']);
            $this->fail('El trigger trg_hito_bu debía rechazar reprogramar fecha_prevista de línea base sin @motivo_cambio.');
        } catch (QueryException $e) {
            $this->assertStringContainsString('motivo', strtolower($e->getMessage()));
        } finally {
            $db->statement('SET @id_responsable = NULL, @motivo_cambio = NULL');
        }

        $this->assertSame($fechaOriginal, Hito::findOrFail($hitoLb->id_hito)->fecha_prevista->format('Y-m-d'));
    }

    public function test_alertas_del_tablero_respetan_filtros_y_explican_desviacion_y_cabeceras(): void
    {
        $consulta = Responsable::where('usuario', 'cgarcia')->firstOrFail();

        $res = $this->actingAs($consulta)
            ->get('/tablero?estado=Cerrado&fecha_corte=2024-09-30')
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertSee('desfase en el devengado, no ahorro')
            ->assertDontSee('Alertas activas');

        $this->assertNotNull($res);
    }
}
