<?php

namespace Tests\Feature;

use App\Models\Proyecto;
use App\Models\Responsable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CierreLaudonCap10Test extends TestCase
{
    public function test_autoservicio_cambio_clave_funciona_para_consulta_y_preserva_perfil(): void
    {
        $this->conClavesDePrueba(['cgarcia' => 'ClaveInicialConsulta2026!'], function () {
            $consulta = Responsable::where('usuario', 'cgarcia')->firstOrFail();

            $this->actingAs($consulta)
                ->get('/perfil/clave')
                ->assertOk()
                ->assertSee('Cambiar contraseña');

            $this->actingAs($consulta)
                ->put('/perfil/clave', [
                    'clave_actual' => 'ClaveInicialConsulta2026!',
                    'clave_nueva' => 'ClaveRenovadaConsulta2026!',
                    'clave_nueva_confirmation' => 'ClaveRenovadaConsulta2026!',
                ])
                ->assertRedirect('/tablero');

            $despues = DB::connection('lector')
                ->table('responsable')
                ->select('perfil', 'activo')
                ->where('id_responsable', $consulta->id_responsable)
                ->first();

            $this->assertSame('Consulta', $despues->perfil);
            $this->assertSame(1, (int) $despues->activo);

            $this->post('/logout');
            $this->post('/login', [
                'usuario' => 'cgarcia',
                'clave' => 'ClaveRenovadaConsulta2026!',
            ])->assertRedirect('/tablero');
            $this->assertAuthenticated();
            $this->post('/logout');
        });
    }

    public function test_autoservicio_cambio_clave_rechaza_actual_incorrecta_y_claves_publicas_legadas(): void
    {
        $this->conClavesDePrueba(['aquispe' => 'ClavePrivadaTitular2026!'], function () {
            $titular = Responsable::where('usuario', 'aquispe')->firstOrFail();

            $this->actingAs($titular)
                ->put('/perfil/clave', [
                    'clave_actual' => 'Incorrecta2026!',
                    'clave_nueva' => 'NuevaClaveValida2026!',
                    'clave_nueva_confirmation' => 'NuevaClaveValida2026!',
                ])
                ->assertSessionHasErrors('clave_actual');

            $this->actingAs($titular)
                ->put('/perfil/clave', [
                    'clave_actual' => 'ClavePrivadaTitular2026!',
                    'clave_nueva' => 'Resp2026!',
                    'clave_nueva_confirmation' => 'Resp2026!',
                ])
                ->assertSessionHasErrors('clave_nueva');
        });
    }

    public function test_programacion_semanal_unica_de_etl_esta_registrada(): void
    {
        $eventos = collect(app(Schedule::class)->events())
            ->filter(fn ($e) => str_contains((string) $e->command, 'etl:cargar'))
            ->values();

        $this->assertCount(1, $eventos);
        $this->assertSame('0 2 * * 0', $eventos[0]->expression);
        $this->assertTrue($eventos[0]->withoutOverlapping);
    }

    public function test_plantilla_csv_abastecimiento_y_trazabilidad_de_carga_en_ficha(): void
    {
        DB::connection('mysql')->table('abastecimiento_compra')->delete();

        $admin = Responsable::findOrFail(1);
        $consulta = Responsable::findOrFail(4);
        $proyecto005 = Proyecto::where('codigo_pgd', '005')->firstOrFail();

        $resPlantilla = $this->actingAs($admin)->get('/abastecimiento/plantilla');
        $resPlantilla->assertOk();
        $this->assertStringContainsString('PROYGD-005', $resPlantilla->streamedContent());

        $this->actingAs($consulta)->get('/abastecimiento/plantilla')->assertForbidden();

        $csv = implode("\n", [
            'ID externo,Referencia de pedido,Fecha de orden,Proveedor,Producto,Descripción,Cantidad,Cantidad recibida,Total',
            '__export__.purchase_order_line_1_traza,P00001,2026-11-18,Grupo Electrodata S.A.C.,[FW-001] Firewall de seguridad perimetral,Proyecto PROYGD-005,2.00,1.00,70800.00',
        ]);

        $this->actingAs($admin)->post('/abastecimiento/confirmar', [
            'archivo_csv' => UploadedFile::fake()->createWithContent('traza.csv', $csv),
            'id_proyecto' => $proyecto005->id_proyecto,
        ])->assertRedirect();

        $this->actingAs($consulta)
            ->get(route('proyectos.show', $proyecto005))
            ->assertOk()
            ->assertSee('Control de carga')
            ->assertSee('msalas');
    }
}
