<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Línea base protegida y versionada (prioridad 1 de la auditoría del 25/11/2026).
 *
 * - Solo un Administrador activo puede sacar un hito de la línea base, reincorporarlo,
 *   reprogramar su fecha prevista o eliminarlo mientras pertenece a ella. Se valida en la
 *   base de datos con el actor de la transacción (@id_responsable), no solo en la interfaz.
 * - Cada alta, incorporación, reprogramación, retiro o eliminación de un hito de línea base
 *   queda como una versión inmutable en hito_linea_base_version.
 * - Crear hitos de línea base sigue permitido al Responsable titular (queda versionado como ALTA).
 */
return new class extends Migration
{
    public function up(): void
    {
        $db = DB::connection('mysql');

        $db->statement("
            CREATE TABLE IF NOT EXISTS hito_linea_base_version (
                id_version INT NOT NULL AUTO_INCREMENT,
                id_hito INT NOT NULL,
                id_proyecto INT NOT NULL,
                version INT NOT NULL,
                accion VARCHAR(15) NOT NULL,
                fecha_prevista_anterior DATE NULL,
                fecha_prevista DATE NULL,
                motivo VARCHAR(200) NULL,
                id_responsable INT NOT NULL,
                fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT pk_hito_linea_base_version PRIMARY KEY (id_version),
                CONSTRAINT uq_hito_linea_base_version UNIQUE (id_hito, version),
                CONSTRAINT ck_hito_lb_version_accion CHECK (accion IN ('ALTA', 'INCORPORACION', 'REPROGRAMACION', 'RETIRO', 'ELIMINACION')),
                CONSTRAINT fk_hito_lb_version_responsable FOREIGN KEY (id_responsable)
                    REFERENCES responsable (id_responsable) ON UPDATE CASCADE ON DELETE RESTRICT,
                INDEX ix_hito_lb_version_proyecto (id_proyecto)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Versión inicial de la línea base vigente en instalaciones existentes.
        $admin = $db->table('responsable')->where('perfil', 'Administrador')->orderBy('id_responsable')->value('id_responsable');
        if ($admin !== null) {
            $db->statement("
                INSERT INTO hito_linea_base_version (id_hito, id_proyecto, version, accion, fecha_prevista_anterior, fecha_prevista, motivo, id_responsable)
                SELECT h.id_hito, h.id_proyecto, 1, 'ALTA', NULL, h.fecha_prevista,
                       'Línea base vigente al activar el versionado (cambios previos en la bitácora)', ?
                FROM hito h
                WHERE h.en_linea_base = 1
                  AND NOT EXISTS (SELECT 1 FROM hito_linea_base_version v WHERE v.id_hito = h.id_hito)
            ", [$admin]);
        }

        // Antes de modificar: la línea base solo la cambia un Administrador activo, con motivo.
        $db->unprepared("DROP TRIGGER IF EXISTS trg_hito_bu;");
        $db->unprepared("
            CREATE DEFINER = 'sig_definer'@'localhost' TRIGGER trg_hito_bu BEFORE UPDATE ON hito FOR EACH ROW
            BEGIN
                DECLARE v_estado_proy VARCHAR(20);
                DECLARE v_perfil VARCHAR(20) DEFAULT NULL;
                SELECT estado INTO v_estado_proy FROM proyecto WHERE id_proyecto = NEW.id_proyecto;
                IF v_estado_proy = 'Cerrado' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se pueden modificar hitos de un proyecto cerrado. Reabra el proyecto primero.';
                END IF;

                IF NEW.en_linea_base <> OLD.en_linea_base OR (OLD.en_linea_base = 1 AND NEW.fecha_prevista <> OLD.fecha_prevista) THEN
                    SELECT perfil INTO v_perfil FROM responsable WHERE id_responsable = @id_responsable AND activo = 1 LIMIT 1;
                    IF v_perfil IS NULL OR v_perfil <> 'Administrador' THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Solo un Administrador puede modificar la linea base de un hito.';
                    END IF;
                    IF @motivo_cambio IS NULL OR TRIM(@motivo_cambio) = '' THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Modificar la linea base o reprogramar su fecha prevista requiere registrar un motivo.';
                    END IF;
                END IF;
            END;
        ");

        // Antes de eliminar: un hito de línea base solo lo elimina un Administrador activo.
        $db->unprepared("DROP TRIGGER IF EXISTS trg_hito_lb_bd;");
        $db->unprepared("
            CREATE DEFINER = 'sig_definer'@'localhost' TRIGGER trg_hito_lb_bd BEFORE DELETE ON hito FOR EACH ROW
            BEGIN
                DECLARE v_perfil VARCHAR(20) DEFAULT NULL;
                IF OLD.en_linea_base = 1 THEN
                    SELECT perfil INTO v_perfil FROM responsable WHERE id_responsable = @id_responsable AND activo = 1 LIMIT 1;
                    IF v_perfil IS NULL OR v_perfil <> 'Administrador' THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Solo un Administrador puede eliminar un hito de la linea base.';
                    END IF;
                END IF;
            END;
        ");

        // Después de cada cambio: una versión inmutable por alta, incorporación, reprogramación, retiro o eliminación.
        $db->unprepared("DROP TRIGGER IF EXISTS trg_hito_lb_ai;");
        $db->unprepared("
            CREATE DEFINER = 'sig_definer'@'localhost' TRIGGER trg_hito_lb_ai AFTER INSERT ON hito FOR EACH ROW FOLLOWS trg_hito_ai
            BEGIN
                DECLARE v_version INT DEFAULT 1;
                IF NEW.en_linea_base = 1 THEN
                    SELECT COALESCE(MAX(version), 0) + 1 INTO v_version FROM hito_linea_base_version WHERE id_hito = NEW.id_hito;
                    INSERT INTO hito_linea_base_version (id_hito, id_proyecto, version, accion, fecha_prevista_anterior, fecha_prevista, motivo, id_responsable)
                    VALUES (NEW.id_hito, NEW.id_proyecto, v_version, 'ALTA', NULL, NEW.fecha_prevista, LEFT(@motivo_cambio, 200), @id_responsable);
                END IF;
            END;
        ");

        $db->unprepared("DROP TRIGGER IF EXISTS trg_hito_lb_au;");
        $db->unprepared("
            CREATE DEFINER = 'sig_definer'@'localhost' TRIGGER trg_hito_lb_au AFTER UPDATE ON hito FOR EACH ROW FOLLOWS trg_hito_au
            BEGIN
                DECLARE v_accion VARCHAR(15) DEFAULT NULL;
                DECLARE v_version INT DEFAULT 1;
                IF OLD.en_linea_base = 0 AND NEW.en_linea_base = 1 THEN
                    SET v_accion = 'INCORPORACION';
                ELSEIF OLD.en_linea_base = 1 AND NEW.en_linea_base = 0 THEN
                    SET v_accion = 'RETIRO';
                ELSEIF OLD.en_linea_base = 1 AND NEW.fecha_prevista <> OLD.fecha_prevista THEN
                    SET v_accion = 'REPROGRAMACION';
                END IF;
                IF v_accion IS NOT NULL THEN
                    SELECT COALESCE(MAX(version), 0) + 1 INTO v_version FROM hito_linea_base_version WHERE id_hito = NEW.id_hito;
                    INSERT INTO hito_linea_base_version (id_hito, id_proyecto, version, accion, fecha_prevista_anterior, fecha_prevista, motivo, id_responsable)
                    VALUES (NEW.id_hito, NEW.id_proyecto, v_version, v_accion,
                            OLD.fecha_prevista, IF(v_accion = 'RETIRO', NULL, NEW.fecha_prevista), LEFT(@motivo_cambio, 200), @id_responsable);
                END IF;
            END;
        ");

        $db->unprepared("DROP TRIGGER IF EXISTS trg_hito_lb_ad;");
        $db->unprepared("
            CREATE DEFINER = 'sig_definer'@'localhost' TRIGGER trg_hito_lb_ad AFTER DELETE ON hito FOR EACH ROW FOLLOWS trg_hito_ad
            BEGIN
                DECLARE v_version INT DEFAULT 1;
                IF OLD.en_linea_base = 1 THEN
                    SELECT COALESCE(MAX(version), 0) + 1 INTO v_version FROM hito_linea_base_version WHERE id_hito = OLD.id_hito;
                    INSERT INTO hito_linea_base_version (id_hito, id_proyecto, version, accion, fecha_prevista_anterior, fecha_prevista, motivo, id_responsable)
                    VALUES (OLD.id_hito, OLD.id_proyecto, v_version, 'ELIMINACION', OLD.fecha_prevista, NULL, LEFT(@motivo_cambio, 200), @id_responsable);
                END IF;
            END;
        ");

        // Privilegios mínimos: lectura para la aplicación y el ETL; solo los triggers (definer) escriben.
        $schema = $db->getDatabaseName();
        foreach (['localhost', '127.0.0.1'] as $host) {
            foreach ([
                "GRANT SELECT ON `{$schema}`.`hito_linea_base_version` TO 'sig_lector'@'{$host}'",
                "GRANT SELECT ON `{$schema}`.`hito_linea_base_version` TO 'sig_escritor'@'{$host}'",
                "GRANT SELECT ON `{$schema}`.`hito_linea_base_version` TO 'sig_etl'@'{$host}'",
                "GRANT SELECT, INSERT ON `{$schema}`.`hito_linea_base_version` TO 'sig_definer'@'{$host}'",
            ] as $sql) {
                try {
                    $db->statement($sql);
                } catch (\Throwable $e) {
                    // Cuenta técnica ausente en un entorno aislado.
                }
            }
        }
    }

    public function down(): void
    {
        $db = DB::connection('mysql');
        foreach (['trg_hito_lb_ai', 'trg_hito_lb_au', 'trg_hito_lb_ad', 'trg_hito_lb_bd'] as $trigger) {
            $db->unprepared("DROP TRIGGER IF EXISTS {$trigger};");
        }

        // Restaura el trg_hito_bu de 2026_11_25_000002 (motivo obligatorio, sin control de perfil).
        $db->unprepared("DROP TRIGGER IF EXISTS trg_hito_bu;");
        $db->unprepared("
            CREATE DEFINER = 'sig_definer'@'localhost' TRIGGER trg_hito_bu BEFORE UPDATE ON hito FOR EACH ROW
            BEGIN
                DECLARE v_estado_proy VARCHAR(20);
                SELECT estado INTO v_estado_proy FROM proyecto WHERE id_proyecto = NEW.id_proyecto;
                IF v_estado_proy = 'Cerrado' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se pueden modificar hitos de un proyecto cerrado. Reabra el proyecto primero.';
                END IF;

                IF NEW.en_linea_base <> OLD.en_linea_base OR (OLD.en_linea_base = 1 AND NEW.fecha_prevista <> OLD.fecha_prevista) THEN
                    IF @motivo_cambio IS NULL OR TRIM(@motivo_cambio) = '' THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Modificar la linea base o reprogramar su fecha prevista requiere registrar un motivo.';
                    END IF;
                END IF;
            END;
        ");

        $db->statement('DROP TABLE IF EXISTS hito_linea_base_version');
    }
};
