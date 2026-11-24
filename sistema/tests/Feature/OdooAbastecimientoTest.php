<?php

namespace Tests\Feature;

use App\Models\AbastecimientoCompra;
use App\Models\Proyecto;
use App\Models\Responsable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OdooAbastecimientoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::connection('mysql')->table('abastecimiento_compra')->delete();
    }

    public function test_carga_inicial_y_repetida_conservan_idempotencia_en_proyecto_005(): void
    {
        $admin = Responsable::findOrFail(1);
        $proyecto005 = Proyecto::where('codigo_pgd', '005')->firstOrFail();

        $csv = implode("\n", [
            'ID externo,Referencia de pedido,Fecha de orden,Proveedor,Producto,Descripción,Cantidad,Cantidad recibida,Total',
            '__export__.purchase_order_line_1_82733e66,P00001,2026-11-18,Grupo Electrodata S.A.C.,[FW-001] Firewall de seguridad perimetral,[FW-001] Firewall de seguridad perimetral - Proyecto PROYGD-005,2.00,1.00,70800.00',
        ]);

        // 1. Vista previa y carga inicial
        $archivo = UploadedFile::fake()->createWithContent('odoo_p00001.csv', $csv);
        $this->actingAs($admin)
            ->post('/abastecimiento/previsualizar', [
                'archivo_csv' => $archivo,
                'id_proyecto' => $proyecto005->id_proyecto,
            ])
            ->assertOk()
            ->assertSee('Nueva')
            ->assertSee('P00001')
            ->assertSee('005');

        $archivoConfirm = UploadedFile::fake()->createWithContent('odoo_p00001.csv', $csv);
        $this->actingAs($admin)
            ->post('/abastecimiento/confirmar', [
                'archivo_csv' => $archivoConfirm,
                'id_proyecto' => $proyecto005->id_proyecto,
            ])
            ->assertRedirect(route('proyectos.show', $proyecto005));

        $this->assertDatabaseCount('abastecimiento_compra', 1);
        $this->assertDatabaseHas('abastecimiento_compra', [
            'id_proyecto' => $proyecto005->id_proyecto,
            'id_externo_linea' => '__export__.purchase_order_line_1_82733e66',
            'orden_compra' => 'P00001',
            'cantidad_comprada' => 2.00,
            'cantidad_recibida' => 1.00,
            'saldo_pendiente' => 1.00,
        ]);

        // 2. Carga repetida con el mismo CSV conserva una sola línea
        $archivoRepetido = UploadedFile::fake()->createWithContent('odoo_p00001.csv', $csv);
        $this->actingAs($admin)
            ->post('/abastecimiento/confirmar', [
                'archivo_csv' => $archivoRepetido,
                'id_proyecto' => $proyecto005->id_proyecto,
            ])
            ->assertRedirect(route('proyectos.show', $proyecto005));

        $this->assertDatabaseCount('abastecimiento_compra', 1);

        // Verificar que la ficha 005 muestra la línea
        $this->actingAs($admin)
            ->get(route('proyectos.show', $proyecto005))
            ->assertOk()
            ->assertSee('Compras y recepciones')
            ->assertSee('P00001')
            ->assertSee('FW-001');
    }

    public function test_actualizacion_por_id_externo_actualiza_saldo_pendiente_a_cero(): void
    {
        $admin = Responsable::findOrFail(1);
        $proyecto005 = Proyecto::where('codigo_pgd', '005')->firstOrFail();

        $csvInicial = implode("\n", [
            'ID externo,Referencia de pedido,Fecha de orden,Proveedor,Producto,Descripción,Cantidad,Cantidad recibida,Total',
            '__export__.purchase_order_line_1_82733e66,P00001,2026-11-18,Grupo Electrodata S.A.C.,[FW-001] Firewall de seguridad perimetral,Proyecto PROYGD-005,2.00,1.00,70800.00',
        ]);
        $this->actingAs($admin)->post('/abastecimiento/confirmar', [
            'archivo_csv' => UploadedFile::fake()->createWithContent('c1.csv', $csvInicial),
        ]);

        $csvActualizado = implode("\n", [
            'ID externo,Referencia de pedido,Fecha de orden,Proveedor,Producto,Descripción,Cantidad,Cantidad recibida,Total',
            '__export__.purchase_order_line_1_82733e66,P00001,2026-11-18,Grupo Electrodata S.A.C.,[FW-001] Firewall de seguridad perimetral,Proyecto PROYGD-005,2.00,2.00,70800.00',
        ]);
        $this->actingAs($admin)->post('/abastecimiento/confirmar', [
            'archivo_csv' => UploadedFile::fake()->createWithContent('c2.csv', $csvActualizado),
        ])->assertRedirect();

        $this->assertDatabaseCount('abastecimiento_compra', 1);
        $this->assertDatabaseHas('abastecimiento_compra', [
            'id_externo_linea' => '__export__.purchase_order_line_1_82733e66',
            'cantidad_comprada' => 2.00,
            'cantidad_recibida' => 2.00,
            'saldo_pendiente' => 0.00,
        ]);
    }

    public function test_datos_invalidos_quedan_sin_publicar(): void
    {
        $admin = Responsable::findOrFail(1);

        $csvInvalido = implode("\n", [
            'ID externo,Referencia de pedido,Fecha de orden,Proveedor,Producto,Descripción,Cantidad,Cantidad recibida,Total',
            ',P00001,2026-11-18,Proveedor X,Producto Y,Proyecto PROYGD-999,2.00,5.00,1000.00',
        ]);

        $this->actingAs($admin)
            ->post('/abastecimiento/previsualizar', [
                'archivo_csv' => UploadedFile::fake()->createWithContent('invalido.csv', $csvInvalido),
            ])
            ->assertOk()
            ->assertSee('Error')
            ->assertSee('Contiene observaciones (no publicable)');

        $this->actingAs($admin)
            ->post('/abastecimiento/confirmar', [
                'archivo_csv' => UploadedFile::fake()->createWithContent('invalido.csv', $csvInvalido),
            ])
            ->assertSessionHasErrors('archivo_csv');

        $this->assertDatabaseCount('abastecimiento_compra', 0);
    }

    public function test_permisos_consulta_puede_ver_ficha_pero_no_importar(): void
    {
        $admin = Responsable::findOrFail(1);
        $responsable = Responsable::findOrFail(2);
        $consulta = Responsable::findOrFail(4);
        $proyecto005 = Proyecto::where('codigo_pgd', '005')->firstOrFail();

        $csv = implode("\n", [
            'ID externo,Referencia de pedido,Fecha de orden,Proveedor,Producto,Descripción,Cantidad,Cantidad recibida,Total',
            '__export__.purchase_order_line_1_82733e66,P00001,2026-11-18,Grupo Electrodata S.A.C.,[FW-001] Firewall de seguridad perimetral,Proyecto PROYGD-005,2.00,1.00,70800.00',
        ]);
        $this->actingAs($admin)->post('/abastecimiento/confirmar', [
            'archivo_csv' => UploadedFile::fake()->createWithContent('ok.csv', $csv),
        ]);

        // Consulta y Responsable pueden ver el detalle en la ficha 005
        $this->actingAs($consulta)
            ->get(route('proyectos.show', $proyecto005))
            ->assertOk()
            ->assertSee('Compras y recepciones')
            ->assertSee('P00001')
            ->assertDontSee('+ Importar CSV Odoo');

        // Responsable y Consulta reciben 403 al intentar importar o confirmar
        $this->actingAs($consulta)->get('/abastecimiento/importar')->assertStatus(403);
        $this->actingAs($responsable)->get('/abastecimiento/importar')->assertStatus(403);
        $this->actingAs($consulta)->post('/abastecimiento/confirmar', [
            'archivo_csv' => UploadedFile::fake()->createWithContent('ok.csv', $csv),
        ])->assertStatus(403);
    }
}
