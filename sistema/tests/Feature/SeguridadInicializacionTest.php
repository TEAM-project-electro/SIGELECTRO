<?php

namespace Tests\Feature;

use App\Services\DatabaseTransactionService;
use Database\Seeders\ResponsableSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class SeguridadInicializacionTest extends TestCase
{
    public function test_cli_rechaza_claves_publicas_o_no_representables_en_el_login(): void
    {
        foreach (['Consulta2026!', ' EspacioInicial2026!', 'EspacioFinal2026! ', str_repeat('a', 73)] as $invalida) {
            $this->artisan('sig:cambiar-clave', ['usuario'=>'cgarcia'])
                ->expectsQuestion('Nueva contraseña privada (12 a 72 bytes)', $invalida)->assertExitCode(1);
        }
    }

    public function test_claves_publicas_legadas_se_rechazan_aunque_el_hash_siga_vigente(): void
    {
        foreach (\App\Services\ClavesPublicasLegadas::VALORES as $publica) {
            $this->conClavesDePrueba(['aquispe' => $publica], function () use ($publica) {
                $this->post('/login', ['usuario'=>'aquispe', 'clave'=>$publica])
                    ->assertSessionHasErrors('usuario');
                $this->assertGuest();
            });
        }
    }

    public function test_operador_renueva_clave_sin_cambiar_perfil_estado_y_permite_acceso(): void
    {
        $this->conClavesDePrueba(['cgarcia' => 'PrivadaAnteriorPrueba2026!'], function () {
            $antes = DB::connection('lector')->table('responsable')->select('perfil', 'activo')->where('usuario', 'cgarcia')->first();
            $this->artisan('sig:cambiar-clave', ['usuario'=>'cgarcia'])
                ->expectsQuestion('Nueva contraseña privada (12 a 72 bytes)', 'PrivadaRenovadaPrueba2026!')
                ->expectsQuestion('Repita la contraseña', 'PrivadaRenovadaPrueba2026!')->assertSuccessful();
            $this->assertEquals($antes, DB::connection('lector')->table('responsable')->select('perfil', 'activo')->where('usuario', 'cgarcia')->first());
            $this->post('/login', ['usuario'=>'cgarcia', 'clave'=>'PrivadaRenovadaPrueba2026!'])->assertRedirect('/tablero');
            $this->assertAuthenticated();
            $this->post('/logout');
        });
    }

    public function test_administracion_no_permite_asignar_clave_publica(): void
    {
        $this->actingAs(\App\Models\Responsable::findOrFail(1));
        $this->postJson('/usuarios', [
            'nombre'=>'Prueba clave', 'area'=>'TIC', 'usuario'=>'prueba_clave_publica',
            'perfil'=>'Consulta', 'activo'=>1, 'clave'=>'Consulta2026!',
        ])->assertUnprocessable()->assertJsonValidationErrors('clave');
        $this->assertFalse(DB::table('responsable')->where('usuario', 'prueba_clave_publica')->exists());
    }

    public function test_resembrar_preserva_cuenta_modificada_y_desactivada(): void
    {
        try {
        DatabaseTransactionService::run(1, function ($db) {
            $db->table('responsable')->where('id_responsable', 2)->update([
                'nombre' => 'Nombre privado modificado', 'perfil' => 'Consulta',
                'activo' => 0, 'clave_hash' => Hash::make('ClavePrivadaNueva2026!'),
            ]);
            $antes = $db->table('responsable')->where('id_responsable', 2)->first();
            (new ResponsableSeeder)->run();
            (new ResponsableSeeder)->run();
            $this->assertEquals($antes, $db->table('responsable')->where('id_responsable', 2)->first());
            // Revierte también los cambios de prueba y su auditoría al salir.
            throw new \RuntimeException('rollback esperado');
        }, 'Prueba de preservación de identidad', connection: 'mysql');
        } catch (\RuntimeException $e) {
            $this->assertSame('rollback esperado', $e->getMessage());
        }
    }

    public function test_cuentas_nuevas_no_reciben_claves_demostrativas(): void
    {
        $filas = [];
        $consulta = Mockery::mock();
        $consulta->shouldReceive('where')->andReturnSelf();
        $consulta->shouldReceive('select')->andReturnSelf();
        $consulta->shouldReceive('first')->andReturnNull();
        $consulta->shouldReceive('exists')->andReturnFalse();
        $consulta->shouldReceive('insert')->times(4)->andReturnUsing(function ($fila) use (&$filas) {
            $filas[] = $fila;
            return true;
        });
        $conexion = Mockery::mock();
        $conexion->shouldReceive('table')->with('responsable')->andReturn($consulta);
        DB::shouldReceive('connection')->with('escritor')->andReturn($conexion);
        (new ResponsableSeeder)->run();
        $this->assertCount(4, $filas);
        foreach ($filas as $fila) {
            foreach (['Admin2026!', 'Resp2026!', 'Consulta2026!'] as $publica) {
                $this->assertFalse(Hash::check($publica, $fila['clave_hash']));
            }
        }
        $this->assertCount(4, array_unique(array_column($filas, 'clave_hash')));
    }

    public function test_salud_no_expone_detalle_incluso_con_fallo(): void
    {
        DB::shouldReceive('connection')->with('lector')->andThrow(new \RuntimeException('detalle-interno-privado'));
        $this->get('/salud')->assertStatus(503)->assertExactJson(['estado' => 'DEGRADADO']);
    }

    public function test_inicializacion_repetida_no_solicita_ni_cambia_clave_existente(): void
    {
        $antes = DB::connection('auth')->table('responsable')->where('id_responsable', 1)->value('clave_hash');
        $this->artisan('sig:inicializar')->assertSuccessful();
        $this->assertSame($antes, DB::connection('auth')->table('responsable')->where('id_responsable', 1)->value('clave_hash'));
        $this->assertNull(config('sig.clave_administrador_inicial'));
    }

    public function test_alta_inicial_solicita_clave_privada_y_la_retira_de_memoria_configurada(): void
    {
        $consulta = Mockery::mock();
        $consulta->shouldReceive('where')->andReturnSelf();
        $consulta->shouldReceive('exists')->andReturnFalse();
        $conexion = Mockery::mock();
        $conexion->shouldReceive('table')->with('responsable')->andReturn($consulta);
        DB::shouldReceive('connection')->with('lector')->andReturn($conexion);
        DB::shouldReceive('getDefaultConnection')->andReturn('lector');
        DB::shouldReceive('setDefaultConnection')->with('lector')->andReturnNull();
        $seeder = Mockery::mock(\Database\Seeders\DatabaseSeeder::class)->makePartial();
        $seeder->shouldReceive('run')->once()->andReturnUsing(function () {
            $this->assertSame('PrivadaSoloParaPrueba2026!', config('sig.clave_administrador_inicial'));
        });
        $this->app->instance(\Database\Seeders\DatabaseSeeder::class, $seeder);
        $this->artisan('sig:inicializar')
            ->expectsQuestion('Contraseña privada del administrador (12 a 72 bytes)', 'PrivadaSoloParaPrueba2026!')
            ->expectsQuestion('Repita la contraseña', 'PrivadaSoloParaPrueba2026!')
            ->assertSuccessful();
        $this->assertNull(config('sig.clave_administrador_inicial'));
    }

    public function test_siembra_rechaza_usuario_asociado_a_otro_identificador(): void
    {
        $consulta = Mockery::mock();
        $consulta->shouldReceive('select')->andReturnSelf();
        $consulta->shouldReceive('where')->andReturnSelf();
        $consulta->shouldReceive('first')->andReturn((object) ['id_responsable' => 99]);
        $conexion = Mockery::mock();
        $conexion->shouldReceive('table')->with('responsable')->andReturn($consulta);
        DB::shouldReceive('connection')->with('escritor')->andReturn($conexion);
        $this->expectExceptionMessage('Conflicto de identidad');
        (new ResponsableSeeder)->run();
    }
}
