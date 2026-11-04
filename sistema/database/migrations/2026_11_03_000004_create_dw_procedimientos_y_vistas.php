<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. PROCEDIMIENTO sp_cargar_dim_tiempo
        DB::connection('dw_migrador')->statement("DROP PROCEDURE IF EXISTS sp_cargar_dim_tiempo;");
        DB::connection('dw_migrador')->statement("
            CREATE PROCEDURE sp_cargar_dim_tiempo(IN p_anio_inicio INT, IN p_anio_fin INT)
            BEGIN
                DECLARE v_fecha DATE;
                DECLARE v_fin DATE;
                DECLARE v_mes INT;
                DECLARE v_nombre_mes VARCHAR(20);
                DECLARE v_dia_sem INT;
                DECLARE v_nombre_dia VARCHAR(20);

                SET v_fecha = STR_TO_DATE(CONCAT(p_anio_inicio, '-01-01'), '%Y-%m-%d');
                SET v_fin = STR_TO_DATE(CONCAT(p_anio_fin, '-12-31'), '%Y-%m-%d');

                WHILE v_fecha <= v_fin DO
                    SET v_mes = MONTH(v_fecha);
                    SET v_nombre_mes = CASE v_mes
                        WHEN 1 THEN 'Enero' WHEN 2 THEN 'Febrero' WHEN 3 THEN 'Marzo'
                        WHEN 4 THEN 'Abril' WHEN 5 THEN 'Mayo' WHEN 6 THEN 'Junio'
                        WHEN 7 THEN 'Julio' WHEN 8 THEN 'Agosto' WHEN 9 THEN 'Setiembre'
                        WHEN 10 THEN 'Octubre' WHEN 11 THEN 'Noviembre' WHEN 12 THEN 'Diciembre'
                    END;

                    SET v_dia_sem = DAYOFWEEK(v_fecha);
                    SET v_nombre_dia = CASE v_dia_sem
                        WHEN 1 THEN 'Domingo' WHEN 2 THEN 'Lunes' WHEN 3 THEN 'Martes'
                        WHEN 4 THEN 'Miércoles' WHEN 5 THEN 'Jueves' WHEN 6 THEN 'Viernes'
                        WHEN 7 THEN 'Sábado'
                    END;

                    INSERT INTO dim_tiempo (
                        sk_tiempo, fecha, anio, semestre, trimestre, mes, nombre_mes, dia_semana, nombre_dia, es_fin_mes
                    ) VALUES (
                        CAST(DATE_FORMAT(v_fecha, '%Y%m%d') AS SIGNED),
                        v_fecha,
                        YEAR(v_fecha),
                        IF(v_mes <= 6, 1, 2),
                        QUARTER(v_fecha),
                        v_mes,
                        v_nombre_mes,
                        v_dia_sem,
                        v_nombre_dia,
                        IF(v_fecha = LAST_DAY(v_fecha), 1, 0)
                    ) ON DUPLICATE KEY UPDATE es_fin_mes = VALUES(es_fin_mes);

                    SET v_fecha = DATE_ADD(v_fecha, INTERVAL 1 DAY);
                END WHILE;
            END;
        ");

        // 2. PROCEDIMIENTO sp_etl_cargar
        DB::connection('dw_migrador')->statement("DROP PROCEDURE IF EXISTS sp_etl_cargar;");
        DB::connection('dw_migrador')->statement("
            CREATE PROCEDURE sp_etl_cargar()
            BEGIN
                DECLARE v_id_carga INT;
                DECLARE v_rev BIGINT DEFAULT 1;
                DECLARE v_filas_avance INT DEFAULT 0;
                DECLARE v_filas_hito INT DEFAULT 0;
                DECLARE v_min_anio INT DEFAULT 2023;
                DECLARE v_max_anio INT DEFAULT 2027;

                DECLARE EXIT HANDLER FOR SQLEXCEPTION
                BEGIN
                    ROLLBACK;
                    IF v_id_carga IS NOT NULL THEN
                        UPDATE etl_control
                        SET estado = 'Fallido', fecha_fin = NOW(), mensaje = 'Error durante la ejecución de sp_etl_cargar'
                        WHERE id_carga = v_id_carga;
                    END IF;
                    RESIGNAL;
                END;

                -- 1. Registro de inicio en etl_control
                INSERT INTO etl_control (fecha_inicio, estado, revision_origen)
                VALUES (NOW(), 'En ejecucion', 1);
                SET v_id_carga = LAST_INSERT_ID();

                -- 2. Captura de revisión técnica de origen
                SELECT IFNULL(version, 1) INTO v_rev
                FROM seguimiento_gd.control_revision
                WHERE id = 1 LIMIT 1;

                UPDATE etl_control SET revision_origen = v_rev WHERE id_carga = v_id_carga;

                -- 3. Asegurar calendario dim_tiempo (2023 a 2027 inicial)
                SELECT
                    LEAST(2023, IFNULL(MIN(YEAR(fecha_reporte)), 2023)),
                    GREATEST(2027, IFNULL(MAX(YEAR(fecha_reporte)), 2027))
                INTO v_min_anio, v_max_anio
                FROM seguimiento_gd.avance_presupuestal;

                CALL sp_cargar_dim_tiempo(v_min_anio, v_max_anio);

                -- 4. Sincronización de Dimensiones Tipo 1
                -- 4.1 dim_objetivo
                INSERT INTO dim_objetivo (id_objetivo, codigo, descripcion)
                SELECT id_objetivo, codigo, descripcion
                FROM seguimiento_gd.objetivo_gd
                ON DUPLICATE KEY UPDATE
                    codigo = VALUES(codigo),
                    descripcion = VALUES(descripcion);

                -- 4.2 dim_responsable (sin usuario ni credenciales)
                INSERT INTO dim_responsable (id_responsable, nombre, area, perfil, es_activo)
                SELECT id_responsable, nombre, area, perfil, activo
                FROM seguimiento_gd.responsable
                ON DUPLICATE KEY UPDATE
                    nombre = VALUES(nombre),
                    area = VALUES(area),
                    perfil = VALUES(perfil),
                    es_activo = VALUES(es_activo);

                -- 4.3 dim_proyecto
                INSERT INTO dim_proyecto (
                    id_proyecto, codigo_pgd, nombre, monto, rango_monto, anio_inicio, anio_fin, estado_actual, es_activo, fuente
                )
                SELECT
                    id_proyecto,
                    codigo_pgd,
                    nombre,
                    monto,
                    CASE
                        WHEN monto < 500000.00 THEN 'Menor a 500k'
                        WHEN monto <= 1000000.00 THEN '500k a 1M'
                        ELSE 'Mayor a 1M'
                    END,
                    anio_inicio,
                    anio_fin,
                    estado,
                    IF(estado IN ('Pendiente', 'En curso'), 1, 0),
                    fuente
                FROM seguimiento_gd.proyecto
                ON DUPLICATE KEY UPDATE
                    codigo_pgd = VALUES(codigo_pgd),
                    nombre = VALUES(nombre),
                    monto = VALUES(monto),
                    rango_monto = VALUES(rango_monto),
                    anio_inicio = VALUES(anio_inicio),
                    anio_fin = VALUES(anio_fin),
                    estado_actual = VALUES(estado_actual),
                    es_activo = VALUES(es_activo),
                    fuente = VALUES(fuente);

                -- 5. Carga de Hechos en Transacción Atómica
                START TRANSACTION;

                -- Vaciado reversible de hechos dentro de la transacción
                DELETE FROM fact_hito;
                DELETE FROM fact_avance_proyecto;

                -- 5.1 Carga fact_avance_proyecto
                INSERT INTO fact_avance_proyecto (
                    sk_proyecto,
                    sk_objetivo,
                    sk_responsable,
                    sk_tiempo,
                    id_proyecto,
                    fecha_reporte,
                    presupuesto_planificado,
                    gasto_acumulado,
                    diferencia_gasto,
                    monto_proyecto,
                    hitos_linea_base,
                    hitos_concluidos,
                    hitos_vencidos,
                    con_retraso,
                    es_ultimo_corte
                )
                SELECT
                    dp.sk_proyecto,
                    dob.sk_objetivo,
                    dr.sk_responsable,
                    dt.sk_tiempo,
                    p.id_proyecto,
                    ap.fecha_reporte,
                    ap.presupuesto_planificado,
                    ap.gasto_acumulado,
                    (ap.gasto_acumulado - ap.presupuesto_planificado) AS diferencia_gasto,
                    p.monto AS monto_proyecto,
                    -- Hitos LB totales del proyecto
                    (SELECT COUNT(*) FROM seguimiento_gd.hito h WHERE h.id_proyecto = p.id_proyecto AND h.en_linea_base = 1) AS hitos_linea_base,
                    -- Hitos LB concluidos a la fecha de reporte
                    (SELECT COUNT(*) FROM seguimiento_gd.hito h WHERE h.id_proyecto = p.id_proyecto AND h.en_linea_base = 1 AND h.fecha_cierre IS NOT NULL AND h.fecha_cierre <= ap.fecha_reporte) AS hitos_concluidos,
                    -- Hitos LB vencidos a la fecha de reporte
                    (SELECT COUNT(*) FROM seguimiento_gd.hito h WHERE h.id_proyecto = p.id_proyecto AND h.en_linea_base = 1 AND h.fecha_prevista < ap.fecha_reporte AND (h.fecha_cierre IS NULL OR h.fecha_cierre > ap.fecha_reporte)) AS hitos_vencidos,
                    -- Con retraso si activo con LB y tiene vencidos
                    IF(p.estado IN ('Pendiente', 'En curso')
                       AND (SELECT COUNT(*) FROM seguimiento_gd.hito h WHERE h.id_proyecto = p.id_proyecto AND h.en_linea_base = 1) > 0
                       AND (SELECT COUNT(*) FROM seguimiento_gd.hito h WHERE h.id_proyecto = p.id_proyecto AND h.en_linea_base = 1 AND h.fecha_prevista < ap.fecha_reporte AND (h.fecha_cierre IS NULL OR h.fecha_cierre > ap.fecha_reporte)) > 0, 1, 0) AS con_retraso,
                    -- Es último corte
                    IF(ap.fecha_reporte = (SELECT MAX(ap2.fecha_reporte) FROM seguimiento_gd.avance_presupuestal ap2 WHERE ap2.id_proyecto = p.id_proyecto), 1, 0) AS es_ultimo_corte
                FROM seguimiento_gd.avance_presupuestal ap
                INNER JOIN seguimiento_gd.proyecto p ON ap.id_proyecto = p.id_proyecto
                INNER JOIN dim_proyecto dp ON p.id_proyecto = dp.id_proyecto
                INNER JOIN dim_objetivo dob ON p.id_objetivo = dob.id_objetivo
                INNER JOIN dim_responsable dr ON p.id_responsable = dr.id_responsable
                INNER JOIN dim_tiempo dt ON ap.fecha_reporte = dt.fecha;

                SET v_filas_avance = ROW_COUNT();

                -- 5.2 Carga fact_hito
                INSERT INTO fact_hito (
                    sk_proyecto,
                    sk_objetivo,
                    sk_responsable,
                    sk_tiempo_previsto,
                    sk_tiempo_cierre,
                    id_hito,
                    id_proyecto,
                    descripcion,
                    en_linea_base,
                    concluido,
                    vencido,
                    dias_desfase,
                    concluido_a_tiempo,
                    tiene_evidencia,
                    estado_hito
                )
                SELECT
                    dp.sk_proyecto,
                    dob.sk_objetivo,
                    dr.sk_responsable,
                    dt_prev.sk_tiempo,
                    dt_cier.sk_tiempo,
                    h.id_hito,
                    h.id_proyecto,
                    h.descripcion,
                    h.en_linea_base,
                    IF(h.fecha_cierre IS NOT NULL, 1, 0) AS concluido,
                    -- Vencido evaluado al último corte o fecha actual
                    IF(h.en_linea_base = 1 AND (h.fecha_cierre IS NULL OR h.fecha_cierre > IFNULL((SELECT MAX(ap.fecha_reporte) FROM seguimiento_gd.avance_presupuestal ap WHERE ap.id_proyecto = h.id_proyecto), CURRENT_DATE()))
                       AND h.fecha_prevista < IFNULL((SELECT MAX(ap.fecha_reporte) FROM seguimiento_gd.avance_presupuestal ap WHERE ap.id_proyecto = h.id_proyecto), CURRENT_DATE()), 1, 0) AS vencido,
                    IF(h.fecha_cierre IS NOT NULL, DATEDIFF(h.fecha_cierre, h.fecha_prevista), NULL) AS dias_desfase,
                    IF(h.fecha_cierre IS NOT NULL AND h.fecha_cierre <= h.fecha_prevista, 1, 0) AS concluido_a_tiempo,
                    IF(h.evidencia IS NOT NULL AND LENGTH(TRIM(h.evidencia)) > 0, 1, 0) AS tiene_evidencia,
                    CASE
                        WHEN h.fecha_cierre IS NOT NULL THEN 'Concluido'
                        WHEN (SELECT MAX(ap.fecha_reporte) FROM seguimiento_gd.avance_presupuestal ap WHERE ap.id_proyecto = h.id_proyecto) IS NOT NULL
                             AND h.fecha_prevista < (SELECT MAX(ap.fecha_reporte) FROM seguimiento_gd.avance_presupuestal ap WHERE ap.id_proyecto = h.id_proyecto) THEN 'Vencido'
                        WHEN (SELECT MAX(ap.fecha_reporte) FROM seguimiento_gd.avance_presupuestal ap WHERE ap.id_proyecto = h.id_proyecto) IS NOT NULL THEN 'En plazo'
                        ELSE 'Sin corte'
                    END AS estado_hito
                FROM seguimiento_gd.hito h
                INNER JOIN seguimiento_gd.proyecto p ON h.id_proyecto = p.id_proyecto
                INNER JOIN dim_proyecto dp ON p.id_proyecto = dp.id_proyecto
                INNER JOIN dim_objetivo dob ON p.id_objetivo = dob.id_objetivo
                INNER JOIN dim_responsable dr ON p.id_responsable = dr.id_responsable
                INNER JOIN dim_tiempo dt_prev ON h.fecha_prevista = dt_prev.fecha
                LEFT JOIN dim_tiempo dt_cier ON h.fecha_cierre = dt_cier.fecha;

                SET v_filas_hito = ROW_COUNT();

                COMMIT;

                -- 6. Cierre exitoso en etl_control
                UPDATE etl_control
                SET estado = 'Completado',
                    fecha_fin = NOW(),
                    filas_avance = v_filas_avance,
                    filas_hito = v_filas_hito,
                    mensaje = 'Carga ETL completada exitosamente'
                WHERE id_carga = v_id_carga;

            END;
        ");

        // 3. SEIS VISTAS ANALÍTICAS DEL DATA MART
        // 3.1 v_kpi_cartera
        DB::connection('dw_migrador')->statement("DROP VIEW IF EXISTS v_kpi_cartera;");
        DB::connection('dw_migrador')->statement("
            CREATE VIEW v_kpi_cartera AS
            SELECT
                COUNT(DISTINCT fa.id_proyecto) AS total_proyectos,
                SUM(fa.monto_proyecto) AS monto_total_cartera,
                SUM(fa.presupuesto_planificado) AS total_planificado,
                SUM(fa.gasto_acumulado) AS total_gasto,
                SUM(fa.diferencia_gasto) AS diferencia_total,
                ROUND(IFNULL((SUM(fa.gasto_acumulado) - SUM(fa.presupuesto_planificado)) / NULLIF(SUM(fa.presupuesto_planificado), 0) * 100, 0), 2) AS desviacion_presupuestal_pct,
                SUM(fa.hitos_linea_base) AS total_hitos_lb,
                SUM(fa.hitos_concluidos) AS total_hitos_concluidos_lb,
                ROUND(IFNULL(SUM(fa.hitos_concluidos) / NULLIF(SUM(fa.hitos_linea_base), 0) * 100, 0), 2) AS avance_hitos_pct,
                SUM(fa.con_retraso) AS proyectos_con_retraso,
                SUM(CASE WHEN dp.es_activo = 1 AND fa.hitos_linea_base > 0 THEN 1 ELSE 0 END) AS proyectos_activos_evaluables,
                ROUND(IFNULL(SUM(fa.con_retraso) / NULLIF(SUM(CASE WHEN dp.es_activo = 1 AND fa.hitos_linea_base > 0 THEN 1 ELSE 0 END), 0) * 100, 0), 2) AS porcentaje_retraso
            FROM fact_avance_proyecto fa
            INNER JOIN dim_proyecto dp ON fa.sk_proyecto = dp.sk_proyecto
            WHERE fa.es_ultimo_corte = 1;
        ");

        // 3.2 v_kpi_proyecto
        DB::connection('dw_migrador')->statement("DROP VIEW IF EXISTS v_kpi_proyecto;");
        DB::connection('dw_migrador')->statement("
            CREATE VIEW v_kpi_proyecto AS
            SELECT
                dp.id_proyecto,
                dp.codigo_pgd,
                dp.nombre AS proyecto,
                dp.estado_actual,
                dp.es_activo,
                dr.nombre AS responsable,
                dob.codigo AS objetivo_codigo,
                dob.descripcion AS objetivo_descripcion,
                fa.monto_proyecto,
                fa.fecha_reporte AS ultimo_corte,
                fa.presupuesto_planificado,
                fa.gasto_acumulado,
                fa.diferencia_gasto,
                ROUND(IFNULL((fa.gasto_acumulado - fa.presupuesto_planificado) / NULLIF(fa.presupuesto_planificado, 0) * 100, 0), 2) AS desviacion_pct,
                fa.hitos_linea_base,
                fa.hitos_concluidos,
                fa.hitos_vencidos,
                ROUND(IFNULL(fa.hitos_concluidos / NULLIF(fa.hitos_linea_base, 0) * 100, 0), 2) AS avance_pct,
                fa.con_retraso
            FROM fact_avance_proyecto fa
            INNER JOIN dim_proyecto dp ON fa.sk_proyecto = dp.sk_proyecto
            INNER JOIN dim_objetivo dob ON fa.sk_objetivo = dob.sk_objetivo
            INNER JOIN dim_responsable dr ON fa.sk_responsable = dr.sk_responsable
            WHERE fa.es_ultimo_corte = 1;
        ");

        // 3.3 v_proyectos_por_estado
        DB::connection('dw_migrador')->statement("DROP VIEW IF EXISTS v_proyectos_por_estado;");
        DB::connection('dw_migrador')->statement("
            CREATE VIEW v_proyectos_por_estado AS
            SELECT
                dp.estado_actual,
                COUNT(*) AS cantidad_proyectos,
                SUM(dp.monto) AS monto_total,
                ROUND(COUNT(*) / (SELECT COUNT(*) FROM dim_proyecto) * 100, 2) AS porcentaje_proyectos,
                ROUND(SUM(dp.monto) / (SELECT SUM(monto) FROM dim_proyecto) * 100, 2) AS porcentaje_monto
            FROM dim_proyecto dp
            GROUP BY dp.estado_actual;
        ");

        // 3.4 v_evolucion_por_corte
        DB::connection('dw_migrador')->statement("DROP VIEW IF EXISTS v_evolucion_por_corte;");
        DB::connection('dw_migrador')->statement("
            CREATE VIEW v_evolucion_por_corte AS
            SELECT
                fa.fecha_reporte,
                COUNT(DISTINCT fa.id_proyecto) AS proyectos_reportados,
                SUM(fa.presupuesto_planificado) AS total_planificado,
                SUM(fa.gasto_acumulado) AS total_gasto,
                SUM(fa.diferencia_gasto) AS diferencia,
                ROUND(IFNULL((SUM(fa.gasto_acumulado) - SUM(fa.presupuesto_planificado)) / NULLIF(SUM(fa.presupuesto_planificado), 0) * 100, 0), 2) AS desviacion_pct,
                SUM(fa.hitos_vencidos) AS total_hitos_vencidos,
                SUM(fa.con_retraso) AS proyectos_con_retraso
            FROM fact_avance_proyecto fa
            GROUP BY fa.fecha_reporte
            ORDER BY fa.fecha_reporte ASC;
        ");

        // 3.5 v_alertas
        DB::connection('dw_migrador')->statement("DROP VIEW IF EXISTS v_alertas;");
        DB::connection('dw_migrador')->statement("
            CREATE VIEW v_alertas AS
            -- Alerta 1: Hito de línea base vencido
            SELECT
                'Hito vencido' AS tipo_alerta,
                'ALTA' AS severidad,
                CONCAT('Hito \"', fh.descripcion, '\" venció el ', fh.id_hito, ' y sigue sin cierre') AS detalle,
                dp.codigo_pgd,
                dp.nombre AS proyecto,
                dr.nombre AS responsable
            FROM fact_hito fh
            INNER JOIN dim_proyecto dp ON fh.sk_proyecto = dp.sk_proyecto
            INNER JOIN dim_responsable dr ON fh.sk_responsable = dr.sk_responsable
            WHERE fh.vencido = 1
            UNION ALL
            -- Alerta 2: Hito concluido sin evidencia registrada
            SELECT
                'Sin evidencia' AS tipo_alerta,
                'MEDIA' AS severidad,
                CONCAT('Hito \"', fh.descripcion, '\" cerrado sin referencia de evidencia') AS detalle,
                dp.codigo_pgd,
                dp.nombre AS proyecto,
                dr.nombre AS responsable
            FROM fact_hito fh
            INNER JOIN dim_proyecto dp ON fh.sk_proyecto = dp.sk_proyecto
            INNER JOIN dim_responsable dr ON fh.sk_responsable = dr.sk_responsable
            WHERE fh.concluido = 1 AND fh.tiene_evidencia = 0
            UNION ALL
            -- Alerta 3: Proyecto activo con retraso presupuestal (gasto > plan)
            SELECT
                'Sobregasto' AS tipo_alerta,
                'MEDIA' AS severidad,
                CONCAT('Gasto supera al presupuesto planificado en S/ ', FORMAT(fa.diferencia_gasto, 2)) AS detalle,
                dp.codigo_pgd,
                dp.nombre AS proyecto,
                dr.nombre AS responsable
            FROM fact_avance_proyecto fa
            INNER JOIN dim_proyecto dp ON fa.sk_proyecto = dp.sk_proyecto
            INNER JOIN dim_responsable dr ON fa.sk_responsable = dr.sk_responsable
            WHERE fa.es_ultimo_corte = 1 AND fa.diferencia_gasto > 0;
        ");

        // 3.6 v_calidad_datos
        DB::connection('dw_migrador')->statement("DROP VIEW IF EXISTS v_calidad_datos;");
        DB::connection('dw_migrador')->statement("
            CREATE VIEW v_calidad_datos AS
            SELECT
                'Proyectos sin hitos en línea base' AS regla_calidad,
                COUNT(*) AS casos_detectados,
                'Proyectos que no definieron hitos de línea base evaluables' AS impacto
            FROM dim_proyecto dp
            WHERE (SELECT COUNT(*) FROM fact_hito fh WHERE fh.sk_proyecto = dp.sk_proyecto AND fh.en_linea_base = 1) = 0
            UNION ALL
            SELECT
                'Proyectos sin ningún corte presupuestal' AS regla_calidad,
                COUNT(*) AS casos_detectados,
                'Proyectos sin historial de avance en fact_avance_proyecto' AS impacto
            FROM dim_proyecto dp
            WHERE (SELECT COUNT(*) FROM fact_avance_proyecto fa WHERE fa.sk_proyecto = dp.sk_proyecto) = 0
            UNION ALL
            SELECT
                'Hitos cerrados sin evidencia' AS regla_calidad,
                COUNT(*) AS casos_detectados,
                'Hitos con fecha de cierre pero sin comprobante de evidencia' AS impacto
            FROM fact_hito fh
            WHERE fh.concluido = 1 AND fh.tiene_evidencia = 0;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::connection('dw_migrador')->statement("DROP VIEW IF EXISTS v_calidad_datos;");
        DB::connection('dw_migrador')->statement("DROP VIEW IF EXISTS v_alertas;");
        DB::connection('dw_migrador')->statement("DROP VIEW IF EXISTS v_evolucion_por_corte;");
        DB::connection('dw_migrador')->statement("DROP VIEW IF EXISTS v_proyectos_por_estado;");
        DB::connection('dw_migrador')->statement("DROP VIEW IF EXISTS v_kpi_proyecto;");
        DB::connection('dw_migrador')->statement("DROP VIEW IF EXISTS v_kpi_cartera;");
        DB::connection('dw_migrador')->statement("DROP PROCEDURE IF EXISTS sp_etl_cargar;");
        DB::connection('dw_migrador')->statement("DROP PROCEDURE IF EXISTS sp_cargar_dim_tiempo;");
    }
};
