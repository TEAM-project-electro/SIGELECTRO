<?php

namespace Tests\Feature;

use App\Models\Responsable;
use App\Models\Hito;
use App\Services\DatabaseTransactionService;
use App\Services\EtlService;
use App\Services\IndicadoresService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CorreccionesAuditoriaTest extends TestCase
{
    public function test_dw_retira_proyectos_eliminados_sin_reasignar_claves_vigentes(): void
    {
        $db=DB::connection('mysql'); $db->statement('SET @id_responsable=1');
        $id=$db->table('proyecto')->insertGetId(['codigo_pgd'=>'AUD-DEL','nombre'=>'Proyecto descartable','monto'=>10,'anio_inicio'=>2024,'anio_fin'=>2025,'estado'=>'En curso','id_objetivo'=>1,'id_responsable'=>1,'fuente'=>'Prueba']);
        $etl=app(EtlService::class);
        try {
            $this->assertTrue($etl->ejecutar()['exito']);
            $clave=DB::connection('dw_lector')->table('dim_proyecto')->where('id_proyecto',1)->value('sk_proyecto');
            $this->assertEquals(11,$etl->consultarDw()['resumen']['total_proyectos']);
            $db->table('proyecto')->where('id_proyecto',$id)->delete();
            $this->assertTrue($etl->ejecutar()['exito']);
            $this->assertEquals(10,$etl->consultarDw()['resumen']['total_proyectos']);
            $this->assertEquals($clave,DB::connection('dw_lector')->table('dim_proyecto')->where('id_proyecto',1)->value('sk_proyecto'));
        } finally {
            $db->table('proyecto')->where('id_proyecto',$id)->delete();
            $etl->ejecutar(); $db->statement('SET @id_responsable=NULL, @motivo_cambio=NULL');
        }
    }

    public function test_revocacion_de_perfil_se_revalida_dentro_del_bloqueo(): void
    {
        $this->actingAs(Responsable::findOrFail(2));
        // Una identidad de sesión obsoleta no puede conservar la autorización anterior.
        \Illuminate\Support\Facades\Auth::user()->perfil='Administrador';
        try {
            DatabaseTransactionService::run(2, function () {
                \Illuminate\Support\Facades\Gate::authorize('create', \App\Models\Proyecto::class);
                $this->fail('El perfil vigente es Responsable, no Administrador.');
            });
            $this->fail('Autorización obsoleta aceptada.');
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            $this->assertEquals('Responsable', \Illuminate\Support\Facades\Auth::user()->perfil);
        }
        \Illuminate\Support\Facades\Auth::user()->perfil='Administrador';
        $this->postJson('/proyectos',[
            'codigo_pgd'=>'REVOCADO','nombre'=>'Alta con perfil revocado','monto'=>100,
            'anio_inicio'=>2024,'anio_fin'=>2025,'estado'=>'En curso','id_objetivo'=>1,'id_responsable'=>2,'fuente'=>'Prueba',
        ])->assertForbidden();
        $this->assertFalse(DB::table('proyecto')->where('codigo_pgd','REVOCADO')->exists());
    }
    public function test_administracion_usuarios_y_catalogo_con_policies_y_auditoria(): void
    {
        $this->actingAs(Responsable::findOrFail(4));
        $this->postJson('/usuarios',[])->assertForbidden();
        $this->postJson('/objetivos',[])->assertForbidden();
        $this->actingAs(Responsable::findOrFail(1));
        $id=null; $objetivo=null;
        try {
            $datos=['nombre'=>'Usuario de prueba administrativa','area'=>'TIC','usuario'=>'usuario_correccion','perfil'=>'Consulta','activo'=>1,'clave'=>'ClaveDePrueba2026!'];
            $respuesta=$this->postJson('/usuarios',$datos)->assertCreated()->assertDontSee('clave_hash');
            $id=$respuesta->json('id_responsable');
            $datos['perfil']='Responsable'; unset($datos['clave']);
            $this->putJson('/usuarios/'.$id,$datos)->assertOk()->assertJsonPath('perfil','Responsable');
            $ultimo=['nombre'=>'Marta Salas','area'=>'Planeamiento','usuario'=>'msalas','perfil'=>'Consulta','activo'=>1];
            $this->putJson('/usuarios/1',$ultimo)->assertUnprocessable();
            $this->assertEquals('Administrador',Responsable::findOrFail(1)->perfil);
            $objetivo=$this->postJson('/objetivos',['codigo'=>'TEST-AUD','descripcion'=>'Objetivo de prueba'])->assertCreated()->json('id_objetivo');
            $this->putJson('/objetivos/'.$objetivo,['codigo'=>'TEST-AUD','descripcion'=>'Objetivo actualizado'])->assertOk();
            $this->assertTrue(DB::table('bitacora_cambio')->where('tabla_afectada','objetivo_gd')->where('id_registro',$objetivo)->where('campo','descripcion')->exists());
        } finally {
            $db=DB::connection('mysql'); $db->statement('SET @id_responsable=1');
            if ($id) $db->table('responsable')->where('id_responsable',$id)->delete();
            if ($objetivo) $db->table('objetivo_gd')->where('id_objetivo',$objetivo)->delete();
            $db->statement('SET @id_responsable=NULL, @motivo_cambio=NULL');
        }
    }

    public function test_lector_no_lee_hashes_y_auth_solo_columnas_del_proveedor(): void
    {
        foreach (['lector'=>'clave_hash','auth'=>'area'] as $conexion=>$columna) {
            try { DB::connection($conexion)->select("SELECT {$columna} FROM responsable WHERE 1=0"); $this->fail('Columna no autorizada accesible.'); }
            catch (\Illuminate\Database\QueryException $e) { $this->assertContains($e->errorInfo[1],[1142,1143]); }
        }
        $this->assertArrayNotHasKey('clave_hash',Responsable::first()->getAttributes());
    }
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_dw_conserva_cartera_y_concilia_ambos_modos_fechas_y_filtros(): void
    {
        $etl = app(EtlService::class);
        $this->assertTrue($etl->ejecutar()['exito']);
        foreach ([IndicadoresService::MODO_CORTE_COMUN, IndicadoresService::MODO_ULTIMO_REPORTE] as $modo) {
            foreach (['2023-03-31','2023-12-31','2024-05-15','2024-06-30','2024-09-30'] as $fecha) {
                foreach ([[], ['id_responsable'=>2], ['id_objetivo'=>1], ['estado'=>'Cerrado'], ['q'=>'005'], ['q'=>'ninguno'], ['id_responsable'=>2,'estado'=>'En curso']] as $filtro) {
                    $filtro += ['modo'=>$modo,'fecha_corte'=>$fecha];
                    $op = app(IndicadoresService::class)->calcularCartera($filtro);
                    $dw = $etl->consultarDw($filtro);
                    $this->assertEquals($op['resumen'], $dw['resumen'], json_encode($filtro));
                    foreach ($op['proyectos'] as $i=>$p) {
                        $this->assertEquals($p['hitos'], $dw['proyectos'][$i]['hitos']);
                        $this->assertEquals($p['financiero'], $dw['proyectos'][$i]['financiero']);
                    }
                }
            }
        }
    }

    public function test_edicion_obsoleta_de_hito_no_sobrescribe(): void
    {
        $this->actingAs(Responsable::findOrFail(1));
        $hito = Hito::findOrFail(2);
        $revision = DB::table('control_revision')->where('id',1)->value('version');
        $datos = ['revision_esperada'=>$revision,'descripcion'=>'Cambio vigente del hito','fecha_prevista'=>$hito->fecha_prevista->format('Y-m-d'),
            'fecha_cierre'=>$hito->fecha_cierre?->format('Y-m-d'),'evidencia'=>$hito->evidencia,'en_linea_base'=>(int)$hito->en_linea_base];
        try {
            $this->putJson('/hitos/2',$datos)->assertOk();
            $datos['descripcion'] = 'Sobrescritura obsoleta';
            $this->putJson('/hitos/2',$datos)->assertUnprocessable()->assertJsonValidationErrors('concurrencia');
            $this->assertEquals('Cambio vigente del hito', Hito::findOrFail(2)->descripcion);
        } finally {
            $db=DB::connection('mysql');
            $db->statement('SET @id_responsable=1');
            $db->table('hito')->where('id_hito',2)->update(['descripcion'=>$hito->descripcion]);
            $db->statement('SET @id_responsable=NULL, @motivo_cambio=NULL');
        }
    }

    public function test_sesion_de_usuario_desactivado_es_revocada(): void
    {
        $this->actingAs(Responsable::findOrFail(4));
        DB::connection('mysql')->statement('SET @id_responsable=1');
        DB::connection('mysql')->statement("SET @motivo_cambio='Revocación controlada de sesión'");
        try {
            DB::connection('mysql')->table('responsable')->where('id_responsable',4)->update(['activo'=>0]);
            $this->getJson('/tablero')->assertForbidden();
            $this->assertGuest();
        } finally {
            DB::connection('mysql')->table('responsable')->where('id_responsable',4)->update(['activo'=>1]);
            DB::connection('mysql')->statement('SET @id_responsable=NULL, @motivo_cambio=NULL');
        }
    }

    public function test_csrf_efectivo_rechaza_token_ausente_y_acepta_token_valido(): void
    {
        $this->app->bind(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class, function ($app) {
            return new class($app, $app['encrypter']) extends \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken {
                protected function runningUnitTests() { return false; }
            };
        });
        $datos = ['usuario'=>'msalas','clave'=>'Admin2026!','remember'=>1];
        $this->post('/login',$datos)->assertStatus(419);
        $this->withSession(['_token'=>'token-controlado'])->post('/login',$datos+['_token'=>'token-controlado'])->assertRedirect('/tablero');
        $this->assertAuthenticated();
    }

    public function test_desactivar_sin_motivo_no_guarda_y_no_expone_credenciales(): void
    {
        $this->actingAs(Responsable::findOrFail(1));
        $this->patchJson('/usuarios/4/activo',['activo'=>0])->assertUnprocessable()->assertJsonValidationErrors('motivo_cambio');
        $this->patchJson('/usuarios/4/activo',['activo'=>'incorrecto'])->assertUnprocessable()->assertJsonValidationErrors('activo');
        $this->assertTrue(Responsable::findOrFail(4)->activo);
        $this->getJson('/usuarios')->assertOk()->assertDontSee('clave_hash')->assertDontSee('$2y$');
        $this->get('/usuarios')->assertOk();
    }

    public function test_creacion_usuario_exige_actor_e_incrementa_revision(): void
    {
        $db = DB::connection('mysql');
        $db->beginTransaction();
        try {
            $db->statement('SET @id_responsable=NULL');
            $fila = ['nombre'=>'Prueba','area'=>'TIC','perfil'=>'Consulta','usuario'=>'audit_creacion','clave_hash'=>'no-es-credencial-real','activo'=>1];
            try { $db->table('responsable')->insert($fila); $this->fail('Falta de actor debe rechazarse.'); }
            catch (\Illuminate\Database\QueryException $e) { $this->assertStringContainsString('actor existente',$e->getMessage()); }
            $db->statement('SET @id_responsable=1');
            $antes = $db->table('control_revision')->where('id',1)->value('version');
            $id=$db->table('responsable')->insertGetId($fila);
            $this->assertEquals($antes+1,$db->table('control_revision')->where('id',1)->value('version'));
            $db->table('responsable')->where('id_responsable',$id)->update(['usuario'=>'audit_renombrado']);
            $this->assertTrue($db->table('bitacora_cambio')->where('id_registro',$id)->where('campo','usuario')->exists());
        } finally { $db->rollBack(); $db->statement('SET @id_responsable=NULL, @motivo_cambio=NULL'); }
    }

    public function test_etl_fallo_intermedio_revierte_dimensiones_y_hechos(): void
    {
        $etl=app(EtlService::class); $this->assertTrue($etl->ejecutar()['exito']);
        $dw=DB::connection('dw_migrador');
        $antes=$dw->table('dim_objetivo')->where('id_objetivo',1)->value('descripcion');
        $hechos=$dw->table('fact_avance_proyecto')->get()->toJson();
        DB::connection('mysql')->statement('SET @id_responsable=1');
        DB::connection('mysql')->table('objetivo_gd')->where('id_objetivo',1)->update(['descripcion'=>'Cambio para comprobar rollback ETL']);
        $dw->unprepared("CREATE TRIGGER prueba_fallo_etl BEFORE INSERT ON fact_avance_proyecto FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Fallo deliberado de prueba'");
        try {
            $this->assertFalse($etl->ejecutar()['exito']);
            $this->assertEquals($antes,$dw->table('dim_objetivo')->where('id_objetivo',1)->value('descripcion'));
            $this->assertEquals($hechos,$dw->table('fact_avance_proyecto')->get()->toJson());
        } finally {
            $dw->unprepared('DROP TRIGGER IF EXISTS prueba_fallo_etl');
            $this->seed(); $etl->ejecutar();
            DB::connection('mysql')->statement('SET @id_responsable=NULL, @motivo_cambio=NULL');
        }
    }

    public function test_etl_exclusion_mutua_y_calendario_fuera_del_rango_inicial(): void
    {
        $lock=DB::connection('mysql');
        $lock->select("SELECT GET_LOCK('sig_etl_mutex',1)");
        try { $this->assertFalse(app(EtlService::class)->ejecutar()['exito']); }
        finally { $lock->select("SELECT RELEASE_LOCK('sig_etl_mutex')"); }
        $db=DB::connection('mysql');
        $db->statement('SET @id_responsable=1');
        $db->statement("SET @motivo_cambio='Prueba de calendario extendido'");
        $id=$db->table('hito')->insertGetId(['id_proyecto'=>1,'descripcion'=>'Calendario externo','fecha_prevista'=>'2030-04-15','fecha_cierre'=>null,'evidencia'=>null,'en_linea_base'=>0]);
        try {
            $this->assertTrue(app(EtlService::class)->ejecutar()['exito']);
            $this->assertTrue(DB::connection('dw_lector')->table('fact_hito')->where('id_hito',$id)->exists());
        } finally {
            $db->table('hito')->where('id_hito',$id)->delete();
            app(EtlService::class)->ejecutar();
            $db->statement('SET @id_responsable=NULL, @motivo_cambio=NULL');
        }
    }
}
