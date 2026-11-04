<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Triggers, procedimientos almacenados y vista de indicadores de MariaDB 10.11.
     */
    public function up(): void
    {
        // 1. PROCEDIMIENTO sp_validar_avance (Reglas R1 y R2)
        DB::unprepared("DROP PROCEDURE IF EXISTS sp_validar_avance;");
        DB::unprepared("
            CREATE PROCEDURE sp_validar_avance(
                IN p_id_avance INT,
                IN p_id_proyecto INT,
                IN p_fecha_reporte DATE,
                IN p_presupuesto DECIMAL(12,2),
                IN p_gasto DECIMAL(12,2)
            )
            BEGIN
                DECLARE v_monto DECIMAL(12,2);
                DECLARE v_gasto_anterior DECIMAL(12,2);
                DECLARE v_gasto_posterior DECIMAL(12,2);

                -- R1: Presupuesto y gasto no superan monto del proyecto
                SELECT monto INTO v_monto FROM proyecto WHERE id_proyecto = p_id_proyecto;
                IF v_monto IS NULL THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El proyecto indicado no existe.';
                END IF;

                IF p_presupuesto > v_monto THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El presupuesto planificado supera el monto del proyecto.';
                END IF;

                IF p_gasto > v_monto THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El gasto acumulado supera el monto del proyecto.';
                END IF;

                -- R2: El gasto acumulado no disminuye cronologicamente
                -- Gasto del corte inmediato anterior
                SELECT gasto_acumulado INTO v_gasto_anterior
                FROM avance_presupuestal
                WHERE id_proyecto = p_id_proyecto
                  AND fecha_reporte < p_fecha_reporte
                  AND (p_id_avance IS NULL OR id_avance <> p_id_avance)
                ORDER BY fecha_reporte DESC
                LIMIT 1;

                IF v_gasto_anterior IS NOT NULL AND p_gasto < v_gasto_anterior THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El gasto acumulado es menor que el de un reporte anterior.';
                END IF;

                -- Gasto del corte inmediato posterior
                SELECT gasto_acumulado INTO v_gasto_posterior
                FROM avance_presupuestal
                WHERE id_proyecto = p_id_proyecto
                  AND fecha_reporte > p_fecha_reporte
                  AND (p_id_avance IS NULL OR id_avance <> p_id_avance)
                ORDER BY fecha_reporte ASC
                LIMIT 1;

                IF v_gasto_posterior IS NOT NULL AND p_gasto > v_gasto_posterior THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El gasto acumulado supera al de un reporte posterior.';
                END IF;
            END;
        ");

        // 2. PROCEDIMIENTO sp_registrar_cambio (Regla R4, auditoria atómica, control_revision y bitacora_motivo)
        DB::unprepared("DROP PROCEDURE IF EXISTS sp_registrar_cambio;");
        DB::unprepared("
            CREATE PROCEDURE sp_registrar_cambio(
                IN p_tabla VARCHAR(30),
                IN p_id_registro INT,
                IN p_campo VARCHAR(40),
                IN p_anterior VARCHAR(200),
                IN p_nuevo VARCHAR(200)
            )
            BEGIN
                DECLARE v_cambio_id INT;

                IF @id_responsable IS NULL THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Defina @id_responsable antes de modificar datos.';
                END IF;

                INSERT INTO bitacora_cambio (tabla_afectada, id_registro, campo, valor_anterior, valor_nuevo, fecha_hora, id_responsable)
                VALUES (p_tabla, p_id_registro, p_campo, p_anterior, p_nuevo, NOW(), @id_responsable);

                SET v_cambio_id = LAST_INSERT_ID();

                -- Si existe motivo de cambio (Decisión 11), guardarlo en la extensión bitacora_motivo
                IF @motivo_cambio IS NOT NULL AND TRIM(@motivo_cambio) <> '' THEN
                    INSERT INTO bitacora_motivo (id_cambio, motivo)
                    VALUES (v_cambio_id, TRIM(@motivo_cambio));
                END IF;

                -- Actualizar revisión global centralizada
                UPDATE control_revision
                SET version = version + 1, fecha_hora = NOW()
                WHERE id = 1;
            END;
        ");

        // 3. TRIGGERS DE AVANCE_PRESUPUESTAL
        DB::unprepared("DROP TRIGGER IF EXISTS trg_avance_bi;");
        DB::unprepared("
            CREATE TRIGGER trg_avance_bi BEFORE INSERT ON avance_presupuestal FOR EACH ROW
            BEGIN
                CALL sp_validar_avance(NULL, NEW.id_proyecto, NEW.fecha_reporte, NEW.presupuesto_planificado, NEW.gasto_acumulado);
            END;
        ");

        DB::unprepared("DROP TRIGGER IF EXISTS trg_avance_bu;");
        DB::unprepared("
            CREATE TRIGGER trg_avance_bu BEFORE UPDATE ON avance_presupuestal FOR EACH ROW
            BEGIN
                CALL sp_validar_avance(OLD.id_avance, NEW.id_proyecto, NEW.fecha_reporte, NEW.presupuesto_planificado, NEW.gasto_acumulado);
            END;
        ");

        DB::unprepared("DROP TRIGGER IF EXISTS trg_avance_au;");
        DB::unprepared("
            CREATE TRIGGER trg_avance_au AFTER UPDATE ON avance_presupuestal FOR EACH ROW
            BEGIN
                IF NEW.presupuesto_planificado <> OLD.presupuesto_planificado THEN
                    CALL sp_registrar_cambio('avance_presupuestal', NEW.id_avance, 'presupuesto_planificado', CAST(OLD.presupuesto_planificado AS CHAR), CAST(NEW.presupuesto_planificado AS CHAR));
                END IF;
                IF NEW.gasto_acumulado <> OLD.gasto_acumulado THEN
                    CALL sp_registrar_cambio('avance_presupuestal', NEW.id_avance, 'gasto_acumulado', CAST(OLD.gasto_acumulado AS CHAR), CAST(NEW.gasto_acumulado AS CHAR));
                END IF;
            END;
        ");

        // 4. TRIGGERS DE PROYECTO (Reglas R1 al reducir monto, R3 cierre/reapertura y R4 auditoria)
        DB::unprepared("DROP TRIGGER IF EXISTS trg_proyecto_bu;");
        DB::unprepared("
            CREATE TRIGGER trg_proyecto_bu BEFORE UPDATE ON proyecto FOR EACH ROW
            BEGIN
                DECLARE v_max_gasto DECIMAL(12,2);
                DECLARE v_max_plan DECIMAL(12,2);
                DECLARE v_hitos_lb INT;
                DECLARE v_hitos_abiertos INT;

                -- R1 reforzada: Reducir monto no puede invalidar cortes existentes
                IF NEW.monto < OLD.monto THEN
                    SELECT COALESCE(MAX(gasto_acumulado), 0), COALESCE(MAX(presupuesto_planificado), 0)
                    INTO v_max_gasto, v_max_plan
                    FROM avance_presupuestal
                    WHERE id_proyecto = NEW.id_proyecto;

                    IF NEW.monto < v_max_gasto THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede reducir el monto: existen cortes con gasto acumulado superior al nuevo monto.';
                    END IF;
                    IF NEW.monto < v_max_plan THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede reducir el monto: existen cortes con presupuesto planificado superior al nuevo monto.';
                    END IF;

                    -- Reducción de monto exige motivo obligatorio (Decisión 11)
                    IF @motivo_cambio IS NULL OR TRIM(@motivo_cambio) = '' THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Reducir el monto del proyecto requiere registrar un motivo.';
                    END IF;
                END IF;

                -- R3 reforzada: Cierre de proyecto
                IF NEW.estado = 'Cerrado' AND OLD.estado <> 'Cerrado' THEN
                    -- Debe tener al menos un hito en linea base
                    SELECT COUNT(*) INTO v_hitos_lb
                    FROM hito
                    WHERE id_proyecto = NEW.id_proyecto AND en_linea_base = 1;

                    IF v_hitos_lb = 0 THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede cerrar el proyecto: requiere al menos un hito en linea base.';
                    END IF;

                    -- Todos los hitos de linea base deben estar cerrados
                    SELECT COUNT(*) INTO v_hitos_abiertos
                    FROM hito
                    WHERE id_proyecto = NEW.id_proyecto
                      AND en_linea_base = 1
                      AND fecha_cierre IS NULL;

                    IF v_hitos_abiertos > 0 THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede cerrar el proyecto: hay hitos de la linea base sin cierre.';
                    END IF;
                END IF;

                -- Reapertura de proyecto: exige motivo obligatorio (Decisión 11)
                IF OLD.estado = 'Cerrado' AND NEW.estado <> 'Cerrado' THEN
                    IF @motivo_cambio IS NULL OR TRIM(@motivo_cambio) = '' THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Reabrir un proyecto cerrado requiere registrar un motivo justificado.';
                    END IF;
                END IF;
            END;
        ");

        DB::unprepared("DROP TRIGGER IF EXISTS trg_proyecto_au;");
        DB::unprepared("
            CREATE TRIGGER trg_proyecto_au AFTER UPDATE ON proyecto FOR EACH ROW
            BEGIN
                IF NEW.estado <> OLD.estado THEN
                    CALL sp_registrar_cambio('proyecto', NEW.id_proyecto, 'estado', OLD.estado, NEW.estado);
                END IF;
                IF NEW.monto <> OLD.monto THEN
                    CALL sp_registrar_cambio('proyecto', NEW.id_proyecto, 'monto', CAST(OLD.monto AS CHAR), CAST(NEW.monto AS CHAR));
                END IF;
                IF NEW.id_responsable <> OLD.id_responsable THEN
                    CALL sp_registrar_cambio('proyecto', NEW.id_proyecto, 'id_responsable', CAST(OLD.id_responsable AS CHAR), CAST(NEW.id_responsable AS CHAR));
                END IF;
            END;
        ");

        // 5. TRIGGERS DE HITO (Inmutabilidad en cierre, modificación de línea base con motivo y auditoría)
        DB::unprepared("DROP TRIGGER IF EXISTS trg_hito_bi;");
        DB::unprepared("
            CREATE TRIGGER trg_hito_bi BEFORE INSERT ON hito FOR EACH ROW
            BEGIN
                DECLARE v_estado_proy VARCHAR(20);
                SELECT estado INTO v_estado_proy FROM proyecto WHERE id_proyecto = NEW.id_proyecto;
                IF v_estado_proy = 'Cerrado' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se pueden agregar hitos a un proyecto cerrado. Reabra el proyecto primero.';
                END IF;
            END;
        ");

        DB::unprepared("DROP TRIGGER IF EXISTS trg_hito_bu;");
        DB::unprepared("
            CREATE TRIGGER trg_hito_bu BEFORE UPDATE ON hito FOR EACH ROW
            BEGIN
                DECLARE v_estado_proy VARCHAR(20);
                SELECT estado INTO v_estado_proy FROM proyecto WHERE id_proyecto = NEW.id_proyecto;
                IF v_estado_proy = 'Cerrado' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se pueden modificar hitos de un proyecto cerrado. Reabra el proyecto primero.';
                END IF;

                -- Modificación de línea base exige motivo (Decisión 11)
                IF NEW.en_linea_base <> OLD.en_linea_base THEN
                    IF @motivo_cambio IS NULL OR TRIM(@motivo_cambio) = '' THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Modificar la linea base de un hito requiere registrar un motivo.';
                    END IF;
                END IF;
            END;
        ");

        DB::unprepared("DROP TRIGGER IF EXISTS trg_hito_au;");
        DB::unprepared("
            CREATE TRIGGER trg_hito_au AFTER UPDATE ON hito FOR EACH ROW
            BEGIN
                IF NEW.descripcion <> OLD.descripcion THEN
                    CALL sp_registrar_cambio('hito', NEW.id_hito, 'descripcion', OLD.descripcion, NEW.descripcion);
                END IF;
                IF NEW.fecha_prevista <> OLD.fecha_prevista THEN
                    CALL sp_registrar_cambio('hito', NEW.id_hito, 'fecha_prevista', CAST(OLD.fecha_prevista AS CHAR), CAST(NEW.fecha_prevista AS CHAR));
                END IF;
                IF (NEW.fecha_cierre IS NULL AND OLD.fecha_cierre IS NOT NULL) OR (NEW.fecha_cierre IS NOT NULL AND OLD.fecha_cierre IS NULL) OR (NEW.fecha_cierre <> OLD.fecha_cierre) THEN
                    CALL sp_registrar_cambio('hito', NEW.id_hito, 'fecha_cierre', CAST(OLD.fecha_cierre AS CHAR), CAST(NEW.fecha_cierre AS CHAR));
                END IF;
                IF NEW.en_linea_base <> OLD.en_linea_base THEN
                    CALL sp_registrar_cambio('hito', NEW.id_hito, 'en_linea_base', CAST(OLD.en_linea_base AS CHAR), CAST(NEW.en_linea_base AS CHAR));
                END IF;
            END;
        ");

        // 6. VISTA v_indicadores_proyecto (Tabla 26 del informe)
        DB::statement("DROP VIEW IF EXISTS v_indicadores_proyecto;");
        DB::statement("
            CREATE VIEW v_indicadores_proyecto AS
            WITH ult_corte AS (
                SELECT ap.id_proyecto, MAX(ap.fecha_reporte) as max_fecha
                FROM avance_presupuestal ap
                GROUP BY ap.id_proyecto
            ),
            hitos_calc AS (
                SELECT 
                    h.id_proyecto,
                    COUNT(CASE WHEN h.en_linea_base = 1 THEN 1 END) as hitos_lb_total,
                    COUNT(CASE WHEN h.en_linea_base = 1 AND h.fecha_cierre IS NOT NULL AND h.fecha_cierre <= uc.max_fecha THEN 1 END) as hitos_lb_concluidos,
                    COUNT(CASE WHEN h.en_linea_base = 1 AND h.fecha_prevista < uc.max_fecha AND (h.fecha_cierre IS NULL OR h.fecha_cierre > uc.max_fecha) THEN 1 END) as hitos_lb_vencidos
                FROM hito h
                JOIN ult_corte uc ON h.id_proyecto = uc.id_proyecto
                GROUP BY h.id_proyecto
            )
            SELECT 
                p.id_proyecto,
                p.codigo_pgd,
                p.nombre,
                p.estado,
                uc.max_fecha as ultimo_reporte,
                ap.presupuesto_planificado,
                ap.gasto_acumulado,
                hc.hitos_lb_concluidos,
                hc.hitos_lb_total,
                hc.hitos_lb_vencidos,
                ROUND(CASE WHEN hc.hitos_lb_total > 0 THEN (hc.hitos_lb_concluidos * 100.0 / hc.hitos_lb_total) ELSE NULL END, 1) as avance_hitos_pct,
                ROUND(CASE WHEN ap.presupuesto_planificado > 0 THEN ((ap.gasto_acumulado - ap.presupuesto_planificado) * 100.0 / ap.presupuesto_planificado) ELSE NULL END, 1) as desviacion_presupuesto_pct,
                CASE WHEN p.estado IN ('Pendiente', 'En curso') AND hc.hitos_lb_vencidos > 0 THEN 1 ELSE 0 END as tiene_retraso
            FROM proyecto p
            LEFT JOIN ult_corte uc ON p.id_proyecto = uc.id_proyecto
            LEFT JOIN avance_presupuestal ap ON p.id_proyecto = ap.id_proyecto AND ap.fecha_reporte = uc.max_fecha
            LEFT JOIN hitos_calc hc ON p.id_proyecto = hc.id_proyecto;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS v_indicadores_proyecto;");
        DB::unprepared("DROP TRIGGER IF EXISTS trg_hito_au;");
        DB::unprepared("DROP TRIGGER IF EXISTS trg_hito_bu;");
        DB::unprepared("DROP TRIGGER IF EXISTS trg_hito_bi;");
        DB::unprepared("DROP TRIGGER IF EXISTS trg_proyecto_au;");
        DB::unprepared("DROP TRIGGER IF EXISTS trg_proyecto_bu;");
        DB::unprepared("DROP TRIGGER IF EXISTS trg_avance_au;");
        DB::unprepared("DROP TRIGGER IF EXISTS trg_avance_bu;");
        DB::unprepared("DROP TRIGGER IF EXISTS trg_avance_bi;");
        DB::unprepared("DROP PROCEDURE IF EXISTS sp_registrar_cambio;");
        DB::unprepared("DROP PROCEDURE IF EXISTS sp_validar_avance;");
    }
};
