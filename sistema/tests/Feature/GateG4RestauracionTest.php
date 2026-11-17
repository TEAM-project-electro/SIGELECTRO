<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GateG4RestauracionTest extends TestCase
{
    public function test_permisos_restaurados_impiden_modificar_o_borrar_auditoria(): void
    {
        foreach (['bitacora_cambio','bitacora_motivo'] as $tabla) {
            foreach (['UPDATE '.$tabla.' SET id_cambio=id_cambio WHERE 1=0', 'DELETE FROM '.$tabla.' WHERE 1=0'] as $sql) {
                $sql=str_replace($tabla,'seguimiento_gd_restaurada.'.$tabla,$sql);
                try { DB::connection('escritor')->statement($sql); $this->fail('Permiso de auditoría demasiado amplio.'); }
                catch (\Illuminate\Database\QueryException $e) { $this->assertEquals(1142,$e->errorInfo[1]); }
            }
        }
    }

    public function test_recorrido_http_y_etl_sobre_restauracion_independiente(): void
    {
        $original=config('database.connections');
        $nombres=['mysql','lector','auth','escritor','dw_migrador','dw_etl','dw_lector'];
        $idHito=null;
        try {
            foreach ($nombres as $nombre) {
                config(["database.connections.{$nombre}.database"=>str_starts_with($nombre,'dw_') ? 'dw_seguimiento_gd_restaurada' : 'seguimiento_gd_restaurada']);
                DB::purge($nombre);
            }
            \Illuminate\Support\Facades\Auth::forgetGuards();
            $this->conClavesDePrueba(['msalas'=>'PrivadaAdminRestaurada2026!', 'cgarcia'=>'PrivadaConsultaRestaurada2026!'], function () use (&$idHito) {
                $this->post('/login',['usuario'=>'msalas','clave'=>'PrivadaAdminRestaurada2026!'])->assertRedirect('/tablero');
                $this->get('/tablero')->assertOk();
                $respuesta=$this->postJson('/proyectos/1/hitos',['descripcion'=>'Recorrido HTTP restaurado','fecha_prevista'=>'2025-01-01','en_linea_base'=>0]);
                $respuesta->assertCreated();
                $idHito=$respuesta->json('id_hito');
                $this->get('/proyectos/1')->assertOk()->assertSee('Recorrido HTTP restaurado');
                $this->assertTrue(DB::table('bitacora_cambio')->where('tabla_afectada','hito')->where('id_registro',$idHito)->exists());
                $this->assertTrue(app(\App\Services\EtlService::class)->ejecutar()['exito']);
                $this->assertTrue(DB::connection('dw_lector')->table('fact_hito')->where('id_hito',$idHito)->exists());
                $this->post('/logout')->assertRedirect('/login');
                $this->post('/login',['usuario'=>'cgarcia','clave'=>'PrivadaConsultaRestaurada2026!'])->assertRedirect('/tablero');
                $this->postJson('/proyectos/1/hitos',['descripcion'=>'Denegado','fecha_prevista'=>'2025-01-01','en_linea_base'=>0])->assertForbidden();
            });
        } finally {
            if ($idHito) {
                DB::connection('escritor')->statement('SET @id_responsable=1');
                DB::connection('escritor')->table('hito')->where('id_hito',$idHito)->delete();
                DB::connection('escritor')->statement('SET @id_responsable=NULL, @motivo_cambio=NULL');
                app(\App\Services\EtlService::class)->ejecutar();
            }
            config(['database.connections'=>$original]);
            foreach ($nombres as $nombre) DB::purge($nombre);
            \Illuminate\Support\Facades\Auth::forgetGuards();
        }
    }
    /**
     * Verificar que los scripts de respaldo y restauración existen y generaron volcados válidos.
     */
    public function test_scripts_de_respaldo_y_restauracion_existen(): void
    {
        $backupScript = base_path('../scripts/respaldo_db.ps1');
        $restoreScript = base_path('../scripts/restaurar_db.ps1');

        $this->assertFileExists($backupScript, 'El script scripts/respaldo_db.ps1 debe existir');
        $this->assertFileExists($restoreScript, 'El script scripts/restaurar_db.ps1 debe existir');

        $opSql = base_path('../backups/seguimiento_gd_latest.sql');
        $dwSql = base_path('../backups/dw_seguimiento_gd_latest.sql');

        $this->assertFileExists($opSql, 'Debe existir backups/seguimiento_gd_latest.sql');
        $this->assertFileExists($dwSql, 'Debe existir backups/dw_seguimiento_gd_latest.sql');

        $this->assertGreaterThan(10000, filesize($opSql), 'El volcado operativo debe ser mayor a 10 KB');
        $this->assertGreaterThan(10000, filesize($dwSql), 'El volcado DW debe ser mayor a 10 KB');
    }

    /**
     * Verificar que el esquema operativo restaurado contiene las 6 tablas canónicas más las 2 extensiones.
     */
    public function test_esquema_operativo_restaurado_tiene_las_6_tablas_y_extensiones(): void
    {
        $tablas = DB::connection('mysql')->select("
            SELECT TABLE_NAME 
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = 'seguimiento_gd_restaurada' AND TABLE_TYPE = 'BASE TABLE'
        ");

        $nombresTablas = array_map(fn($t) => $t->TABLE_NAME, $tablas);

        $tablasEsperadas = [
            'objetivo_gd',
            'proyecto',
            'hito',
            'avance_presupuestal',
            'responsable',
            'bitacora_cambio',
            'control_revision',
            'bitacora_motivo'
        ];

        foreach ($tablasEsperadas as $tabla) {
            $this->assertContains($tabla, $nombresTablas, "La tabla {$tabla} debe existir en seguimiento_gd_restaurada");
        }
    }

    /**
     * Verificar que los triggers y procedimientos se restauraron en seguimiento_gd_restaurada.
     */
    public function test_esquema_operativo_restaurado_tiene_triggers_y_rutinas(): void
    {
        // Triggers canónicos
        $triggers = DB::connection('mysql')->select("
            SELECT TRIGGER_NAME 
            FROM information_schema.TRIGGERS 
            WHERE TRIGGER_SCHEMA = 'seguimiento_gd_restaurada'
        ");
        $nombresTriggers = array_map(fn($t) => $t->TRIGGER_NAME, $triggers);

        $this->assertContains('trg_avance_bi', $nombresTriggers, 'Trigger R1/R2 INSERT debe existir');
        $this->assertContains('trg_avance_bu', $nombresTriggers, 'Trigger R1/R2 UPDATE debe existir');
        $this->assertContains('trg_proyecto_bu', $nombresTriggers, 'Trigger R3 cierre/monto debe existir');
        $this->assertContains('trg_hito_bu', $nombresTriggers, 'Trigger LB hito debe existir');

        // Procedimientos almacenados
        $rutinas = DB::connection('mysql')->select("
            SELECT ROUTINE_NAME 
            FROM information_schema.ROUTINES 
            WHERE ROUTINE_SCHEMA = 'seguimiento_gd_restaurada'
        ");
        $nombresRutinas = array_map(fn($r) => $r->ROUTINE_NAME, $rutinas);

        $this->assertContains('sp_validar_avance', $nombresRutinas, 'Procedimiento sp_validar_avance debe existir');
        $this->assertContains('sp_registrar_cambio', $nombresRutinas, 'Procedimiento sp_registrar_cambio debe existir');

        // Vista de indicadores
        $vistas = DB::connection('mysql')->select("
            SELECT TABLE_NAME 
            FROM information_schema.VIEWS 
            WHERE TABLE_SCHEMA = 'seguimiento_gd_restaurada'
        ");
        $nombresVistas = array_map(fn($v) => $v->TABLE_NAME, $vistas);
        $this->assertContains('v_indicadores_proyecto', $nombresVistas, 'Vista v_indicadores_proyecto debe existir');
    }

    /**
     * Verificar que el Data Warehouse restaurado contiene dimensiones, hechos, vistas y procedimientos.
     */
    public function test_esquema_dw_restaurado_tiene_dimensiones_hechos_y_vistas(): void
    {
        $tablasDw = DB::connection('mysql')->select("
            SELECT TABLE_NAME 
            FROM information_schema.TABLES 
            WHERE TABLE_SCHEMA = 'dw_seguimiento_gd_restaurada' AND TABLE_TYPE = 'BASE TABLE'
        ");
        $nombresDw = array_map(fn($t) => $t->TABLE_NAME, $tablasDw);

        $tablasEsperadas = [
            'dim_tiempo',
            'dim_proyecto',
            'dim_objetivo',
            'dim_responsable',
            'fact_avance_proyecto',
            'fact_hito',
            'etl_control'
        ];

        foreach ($tablasEsperadas as $tabla) {
            $this->assertContains($tabla, $nombresDw, "La tabla {$tabla} debe existir en dw_seguimiento_gd_restaurada");
        }

        // Vistas analíticas
        $vistasDw = DB::connection('mysql')->select("
            SELECT TABLE_NAME 
            FROM information_schema.VIEWS 
            WHERE TABLE_SCHEMA = 'dw_seguimiento_gd_restaurada'
        ");
        $nombresVistasDw = array_map(fn($v) => $v->TABLE_NAME, $vistasDw);

        $vistasEsperadas = [
            'v_kpi_cartera',
            'v_kpi_proyecto',
            'v_proyectos_por_estado',
            'v_evolucion_por_corte',
            'v_alertas',
            'v_calidad_datos'
        ];

        foreach ($vistasEsperadas as $vista) {
            $this->assertContains($vista, $nombresVistasDw, "La vista analítica {$vista} debe existir en DW restaurado");
        }
    }

    /**
     * Verificar que los conteos de registros y montos financieros concilian al 100%.
     */
    public function test_conteos_y_montos_concilian_al_100_por_ciento(): void
    {
        // 1. Proyectos: 10 proyectos, S/ 8,623,550.00
        $proyectosOp = DB::connection('mysql')->selectOne("
            SELECT COUNT(*) AS total, SUM(monto) AS monto 
            FROM seguimiento_gd_restaurada.proyecto
        ");
        $this->assertEquals(10, $proyectosOp->total, 'Deben existir exactamente 10 proyectos en la base restaurada');
        $this->assertEquals(8623550.00, (float)$proyectosOp->monto, 'El monto total debe ser S/ 8,623,550.00');

        // 2. Hitos: 26 hitos
        $hitosOp = DB::connection('mysql')->selectOne("
            SELECT COUNT(*) AS total 
            FROM seguimiento_gd_restaurada.hito
        ");
        $this->assertEquals(26, $hitosOp->total, 'Deben existir exactamente 26 hitos en la base restaurada');

        // 3. Cortes de avance presupuestal: 24 cortes
        $avancesOp = DB::connection('mysql')->selectOne("
            SELECT COUNT(*) AS total 
            FROM seguimiento_gd_restaurada.avance_presupuestal
        ");
        $this->assertEquals(24, $avancesOp->total, 'Deben existir exactamente 24 cortes presupuestales');

        // 4. Objetos y responsables
        $ogdOp = DB::connection('mysql')->selectOne("SELECT COUNT(*) AS total FROM seguimiento_gd_restaurada.objetivo_gd");
        $this->assertEquals(6, $ogdOp->total, 'Deben existir exactamente 6 objetivos OGD');

        $usersOp = DB::connection('mysql')->selectOne("SELECT COUNT(*) AS total FROM seguimiento_gd_restaurada.responsable");
        $this->assertEquals(4, $usersOp->total, 'Deben existir exactamente 4 usuarios responsables/admin');

        // 5. DW reconciliación
        $dimProy = DB::connection('mysql')->selectOne("
            SELECT COUNT(*) AS total, SUM(monto) AS monto 
            FROM dw_seguimiento_gd_restaurada.dim_proyecto
        ");
        $this->assertEquals(10, $dimProy->total, 'DW debe tener 10 proyectos');
        $this->assertEquals(8623550.00, (float)$dimProy->monto, 'DW monto total adjudicado debe coincidir');

        $factHitos = DB::connection('mysql')->selectOne("SELECT COUNT(*) AS total FROM dw_seguimiento_gd_restaurada.fact_hito");
        $this->assertEquals(26, $factHitos->total, 'DW debe tener 26 hitos');

        $factAvances = DB::connection('mysql')->selectOne("SELECT COUNT(*) AS total FROM dw_seguimiento_gd_restaurada.fact_avance_proyecto");
        $this->assertEquals(24, $factAvances->total, 'DW debe tener 24 registros de avance');
    }

    /**
     * Verificar que los cuatro indicadores canónicos concilian exactamente entre la base operativa y la restaurada.
     */
    public function test_paridad_exacta_de_los_cuatro_indicadores_en_esquema_restaurado(): void
    {
        // Comparar indicadores de proyectos entre seguimiento_gd y seguimiento_gd_restaurada
        $indicadoresOriginal = DB::connection('mysql')->select("
            SELECT id_proyecto, codigo_pgd, hitos_lb_concluidos, hitos_lb_total, avance_hitos_pct, desviacion_presupuesto_pct 
            FROM seguimiento_gd.v_indicadores_proyecto 
            ORDER BY id_proyecto ASC
        ");

        $indicadoresRestaurado = DB::connection('mysql')->select("
            SELECT id_proyecto, codigo_pgd, hitos_lb_concluidos, hitos_lb_total, avance_hitos_pct, desviacion_presupuesto_pct 
            FROM seguimiento_gd_restaurada.v_indicadores_proyecto 
            ORDER BY id_proyecto ASC
        ");

        $this->assertCount(10, $indicadoresOriginal);
        $this->assertCount(10, $indicadoresRestaurado);

        for ($i = 0; $i < 10; $i++) {
            $orig = $indicadoresOriginal[$i];
            $rest = $indicadoresRestaurado[$i];

            $this->assertEquals($orig->codigo_pgd, $rest->codigo_pgd, "Código PGD debe coincidir en proyecto {$orig->id_proyecto}");
            $this->assertEquals((float)$orig->avance_hitos_pct, (float)$rest->avance_hitos_pct, "Avance % debe ser idéntico en proyecto {$orig->codigo_pgd}");
            $this->assertEquals((float)$orig->desviacion_presupuesto_pct, (float)$rest->desviacion_presupuesto_pct, "Desviación debe ser idéntica en proyecto {$orig->codigo_pgd}");
            $this->assertEquals($orig->hitos_lb_concluidos, $rest->hitos_lb_concluidos, "Hitos cumplidos deben coincidir en {$orig->codigo_pgd}");
            $this->assertEquals($orig->hitos_lb_total, $rest->hitos_lb_total, "Total hitos debe coincidir en {$orig->codigo_pgd}");
        }

        // Comparar KPI de cartera en DW
        $kpiOriginal = DB::connection('mysql')->selectOne("SELECT * FROM dw_seguimiento_gd.v_kpi_cartera");
        $kpiRestaurado = DB::connection('mysql')->selectOne("SELECT * FROM dw_seguimiento_gd_restaurada.v_kpi_cartera");

        $this->assertNotNull($kpiOriginal);
        $this->assertNotNull($kpiRestaurado);

        $this->assertEquals($kpiOriginal->total_proyectos, $kpiRestaurado->total_proyectos);
        $this->assertEquals((float)$kpiOriginal->monto_total_cartera, (float)$kpiRestaurado->monto_total_cartera);
        $this->assertEquals((float)$kpiOriginal->total_gasto, (float)$kpiRestaurado->total_gasto);
        $this->assertEquals((float)$kpiOriginal->avance_hitos_pct, (float)$kpiRestaurado->avance_hitos_pct);
    }

    /**
     * Recorrido funcional completo y verificación de independencia en esquema restaurado (A08).
     */
    public function test_recorrido_minimo_funcional_sobre_esquema_restaurado(): void
    {
        // 1. Acceso a usuario administrador restaurado
        $admin = DB::connection('mysql')->selectOne("
            SELECT id_responsable, nombre, perfil, usuario, clave_hash 
            FROM seguimiento_gd_restaurada.responsable 
            WHERE usuario = 'msalas'
        ");

        $this->assertNotNull($admin, 'Usuario administrador msalas debe existir en esquema restaurado');
        $this->assertEquals('Administrador', $admin->perfil);
        $this->assertStringStartsWith('$2y$', $admin->clave_hash, 'Hash de clave debe ser bcrypt válido');

        // 2. Control de revisión activo
        $revisionInicial = (int) DB::connection('mysql')->table('seguimiento_gd_restaurada.control_revision')
            ->where('id', 1)
            ->value('version');

        $this->assertGreaterThan(0, $revisionInicial, 'Versión de revisión debe ser mayor a 0');

        // 3. Auditoría registrada en bitácora
        $auditoriasInicial = (int) DB::connection('mysql')->table('seguimiento_gd_restaurada.bitacora_cambio')->count();
        $this->assertGreaterThanOrEqual(1, $auditoriasInicial, 'La bitácora de auditoría debe contener eventos registrados');

        // 4. A08: Mutación funcional real con auditoría transaccional sobre el esquema restaurado
        $proyecto = DB::connection('mysql')->table('seguimiento_gd_restaurada.proyecto')->first();
        $this->assertNotNull($proyecto, 'Debe existir al menos un proyecto en el esquema restaurado');
        $idProyecto = (int) $proyecto->id_proyecto;
        $fuenteOriginal = $proyecto->fuente ?? 'Cartera PGD 2023 a 2026';
        $nuevaFuente = 'Cartera PGD (G4 Verificado)';

        $motivoPrueba = 'Verificación funcional de auditoría en esquema restaurado G4';

        DB::connection('escritor')->statement("USE seguimiento_gd_restaurada;");
        DB::connection('escritor')->statement("SET @id_responsable = {$admin->id_responsable};");
        DB::connection('escritor')->statement("SET @motivo_cambio = '{$motivoPrueba}';");

        DB::connection('escritor')->table('seguimiento_gd_restaurada.proyecto')
            ->where('id_proyecto', $idProyecto)
            ->update([
                'fuente' => $nuevaFuente,
                'fecha_actualizacion' => now(),
            ]);

        DB::connection('escritor')->statement("SET @id_responsable = NULL;");
        DB::connection('escritor')->statement("SET @motivo_cambio = NULL;");

        // 5. Verificar que la mutación quedó auditada con el motivo y la revisión se incrementó
        $revisionFinal = (int) DB::connection('mysql')->table('seguimiento_gd_restaurada.control_revision')
            ->where('id', 1)
            ->value('version');

        $this->assertEquals($revisionInicial + 1, $revisionFinal, 'La versión de control_revision en el esquema restaurado debió incrementarse');

        $ultimoCambio = DB::connection('mysql')->table('seguimiento_gd_restaurada.bitacora_cambio as bc')
            ->join('seguimiento_gd_restaurada.bitacora_motivo as bm', 'bc.id_cambio', '=', 'bm.id_cambio')
            ->where('bc.tabla_afectada', 'proyecto')
            ->where('bc.id_registro', $idProyecto)
            ->orderByDesc('bc.id_cambio')
            ->first();

        $this->assertNotNull($ultimoCambio, 'Debe existir registro en bitacora_cambio para el cambio en esquema restaurado');
        $this->assertEquals($admin->id_responsable, $ultimoCambio->id_responsable);
        $this->assertEquals($motivoPrueba, $ultimoCambio->motivo);

        // Restaurar valor previo
        DB::connection('escritor')->statement("SET @id_responsable = {$admin->id_responsable};");
        DB::connection('escritor')->statement("SET @motivo_cambio = 'Reversión test funcional G4';");
        DB::connection('escritor')->table('seguimiento_gd_restaurada.proyecto')
            ->where('id_proyecto', $idProyecto)
            ->update([
                'fuente' => $fuenteOriginal,
                'fecha_actualizacion' => now(),
            ]);
        DB::connection('escritor')->statement("SET @id_responsable = NULL;");
        DB::connection('escritor')->statement("SET @motivo_cambio = NULL;");
    }
}
