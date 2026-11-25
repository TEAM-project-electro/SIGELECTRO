<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Segundo grupo de mejoras de la auditoría (prioridad 2): gestión gerencial de la cartera.
 *
 * Tablas técnicas independientes (el núcleo de 6 tablas no cambia de columnas):
 * - criterio_priorizacion / evaluacion_priorizacion: priorización multicriterio configurable.
 * - riesgo_proyecto: riesgos e incidencias (probabilidad × impacto).
 * - hito_peso: ponderación de hitos para el avance físico y el valor ganado.
 * - proyecto_presupuesto_version / compromiso_presupuestal: presupuesto vigente y comprometido.
 * - parametro_alerta: umbrales configurables de las alertas preventivas.
 * - documento_proyecto / beneficio_proyecto: evidencias, actas y beneficios.
 *
 * Reglas en la base de datos (no solo en la interfaz): quién puede escribir cada tabla según el
 * perfil del actor de la transacción (@id_responsable), compromisos acotados al presupuesto y
 * auditoría de cada alta y modificación en bitacora_cambio.
 */
return new class extends Migration
{
    private const TABLAS_AUDITADAS = [
        'objetivo_gd', 'responsable', 'proyecto', 'hito', 'avance_presupuestal',
        'criterio_priorizacion', 'evaluacion_priorizacion', 'riesgo_proyecto', 'hito_peso',
        'compromiso_presupuestal', 'parametro_alerta', 'documento_proyecto', 'beneficio_proyecto',
    ];

    public const COLUMNAS_FIJADAS_POR_TRIGGER = [
        'evaluacion_priorizacion' => 'id_responsable',
        'compromiso_presupuestal' => 'id_responsable',
        'documento_proyecto' => 'id_responsable',
        'riesgo_proyecto' => 'fecha_cierre',
    ];

    public function up(): void
    {
        $db = DB::connection('mysql');

        $this->crearTablas($db);

        // La bitácora acepta las tablas nuevas (sigue siendo inmutable para la aplicación).
        $db->statement('ALTER TABLE bitacora_cambio DROP CONSTRAINT ck_bitacora_tabla');
        $db->statement("ALTER TABLE bitacora_cambio ADD CONSTRAINT ck_bitacora_tabla CHECK (tabla_afectada IN ('"
            .implode("', '", self::TABLAS_AUDITADAS)."'))");

        $this->crearProcedimientos($db);
        $this->crearReglas($db);
        $this->crearAuditoria($db);
        $this->versionarPresupuestoExistente($db);
        $this->otorgarPermisos($db);
    }

    private function crearTablas($db): void
    {
        $db->statement("
            CREATE TABLE IF NOT EXISTS criterio_priorizacion (
                id_criterio INT NOT NULL AUTO_INCREMENT,
                codigo VARCHAR(20) NOT NULL,
                nombre VARCHAR(100) NOT NULL,
                descripcion VARCHAR(250) NOT NULL,
                grupo VARCHAR(30) NOT NULL,
                peso DECIMAL(6,2) NOT NULL DEFAULT 1.00,
                activo TINYINT(1) NOT NULL DEFAULT 1,
                orden INT NOT NULL DEFAULT 0,
                CONSTRAINT pk_criterio_priorizacion PRIMARY KEY (id_criterio),
                CONSTRAINT uq_criterio_codigo UNIQUE (codigo),
                CONSTRAINT ck_criterio_peso CHECK (peso >= 0 AND peso <= 100),
                CONSTRAINT ck_criterio_activo CHECK (activo IN (0, 1))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->statement("
            CREATE TABLE IF NOT EXISTS evaluacion_priorizacion (
                id_evaluacion INT NOT NULL AUTO_INCREMENT,
                id_proyecto INT NOT NULL,
                id_criterio INT NOT NULL,
                puntaje TINYINT NOT NULL,
                sustento VARCHAR(300) NOT NULL,
                id_responsable INT NOT NULL,
                fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT pk_evaluacion_priorizacion PRIMARY KEY (id_evaluacion),
                CONSTRAINT uq_evaluacion_proyecto_criterio UNIQUE (id_proyecto, id_criterio),
                CONSTRAINT ck_evaluacion_puntaje CHECK (puntaje BETWEEN 1 AND 5),
                CONSTRAINT fk_evaluacion_proyecto FOREIGN KEY (id_proyecto) REFERENCES proyecto (id_proyecto) ON UPDATE CASCADE ON DELETE RESTRICT,
                CONSTRAINT fk_evaluacion_criterio FOREIGN KEY (id_criterio) REFERENCES criterio_priorizacion (id_criterio) ON UPDATE CASCADE ON DELETE RESTRICT,
                CONSTRAINT fk_evaluacion_responsable FOREIGN KEY (id_responsable) REFERENCES responsable (id_responsable) ON UPDATE CASCADE ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->statement("
            CREATE TABLE IF NOT EXISTS riesgo_proyecto (
                id_riesgo INT NOT NULL AUTO_INCREMENT,
                id_proyecto INT NOT NULL,
                tipo VARCHAR(12) NOT NULL,
                descripcion VARCHAR(250) NOT NULL,
                probabilidad TINYINT NOT NULL,
                impacto TINYINT NOT NULL,
                exposicion TINYINT AS (probabilidad * impacto) STORED,
                id_responsable INT NOT NULL,
                plan_respuesta VARCHAR(300) NOT NULL,
                estado VARCHAR(15) NOT NULL DEFAULT 'Abierto',
                fecha_identificacion DATE NOT NULL,
                fecha_cierre DATE NULL,
                CONSTRAINT pk_riesgo_proyecto PRIMARY KEY (id_riesgo),
                CONSTRAINT ck_riesgo_tipo CHECK (tipo IN ('Riesgo', 'Incidencia')),
                CONSTRAINT ck_riesgo_probabilidad CHECK (probabilidad BETWEEN 1 AND 5),
                CONSTRAINT ck_riesgo_impacto CHECK (impacto BETWEEN 1 AND 5),
                CONSTRAINT ck_riesgo_estado CHECK (estado IN ('Abierto', 'En tratamiento', 'Cerrado')),
                CONSTRAINT fk_riesgo_proyecto FOREIGN KEY (id_proyecto) REFERENCES proyecto (id_proyecto) ON UPDATE CASCADE ON DELETE RESTRICT,
                CONSTRAINT fk_riesgo_responsable FOREIGN KEY (id_responsable) REFERENCES responsable (id_responsable) ON UPDATE CASCADE ON DELETE RESTRICT,
                INDEX ix_riesgo_proyecto_estado (id_proyecto, estado)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->statement("
            CREATE TABLE IF NOT EXISTS hito_peso (
                id_hito INT NOT NULL,
                peso DECIMAL(6,2) NOT NULL,
                CONSTRAINT pk_hito_peso PRIMARY KEY (id_hito),
                CONSTRAINT ck_hito_peso CHECK (peso > 0 AND peso <= 100),
                CONSTRAINT fk_hito_peso_hito FOREIGN KEY (id_hito) REFERENCES hito (id_hito) ON UPDATE CASCADE ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->statement("
            CREATE TABLE IF NOT EXISTS proyecto_presupuesto_version (
                id_version INT NOT NULL AUTO_INCREMENT,
                id_proyecto INT NOT NULL,
                version INT NOT NULL,
                monto_anterior DECIMAL(12,2) NULL,
                monto DECIMAL(12,2) NOT NULL,
                motivo VARCHAR(200) NULL,
                id_responsable INT NOT NULL,
                fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT pk_proyecto_presupuesto_version PRIMARY KEY (id_version),
                CONSTRAINT uq_presupuesto_version UNIQUE (id_proyecto, version),
                CONSTRAINT fk_presupuesto_version_responsable FOREIGN KEY (id_responsable) REFERENCES responsable (id_responsable) ON UPDATE CASCADE ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->statement("
            CREATE TABLE IF NOT EXISTS compromiso_presupuestal (
                id_compromiso INT NOT NULL AUTO_INCREMENT,
                id_proyecto INT NOT NULL,
                documento VARCHAR(60) NOT NULL,
                descripcion VARCHAR(200) NOT NULL,
                monto DECIMAL(12,2) NOT NULL,
                fecha DATE NOT NULL,
                estado VARCHAR(10) NOT NULL DEFAULT 'Vigente',
                id_responsable INT NOT NULL,
                fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT pk_compromiso_presupuestal PRIMARY KEY (id_compromiso),
                CONSTRAINT uq_compromiso_documento UNIQUE (id_proyecto, documento),
                CONSTRAINT ck_compromiso_monto CHECK (monto > 0),
                CONSTRAINT ck_compromiso_estado CHECK (estado IN ('Vigente', 'Anulado')),
                CONSTRAINT fk_compromiso_proyecto FOREIGN KEY (id_proyecto) REFERENCES proyecto (id_proyecto) ON UPDATE CASCADE ON DELETE RESTRICT,
                CONSTRAINT fk_compromiso_responsable FOREIGN KEY (id_responsable) REFERENCES responsable (id_responsable) ON UPDATE CASCADE ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->statement("
            CREATE TABLE IF NOT EXISTS parametro_alerta (
                clave VARCHAR(40) NOT NULL,
                valor DECIMAL(10,2) NOT NULL,
                unidad VARCHAR(20) NOT NULL,
                descripcion VARCHAR(200) NOT NULL,
                CONSTRAINT pk_parametro_alerta PRIMARY KEY (clave)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->statement("
            CREATE TABLE IF NOT EXISTS documento_proyecto (
                id_documento INT NOT NULL AUTO_INCREMENT,
                id_proyecto INT NOT NULL,
                id_hito INT NULL,
                tipo VARCHAR(15) NOT NULL,
                titulo VARCHAR(150) NOT NULL,
                nombre_original VARCHAR(200) NOT NULL,
                ruta VARCHAR(255) NOT NULL,
                mime VARCHAR(100) NOT NULL,
                tamano INT NOT NULL,
                hash_sha256 CHAR(64) NOT NULL,
                id_responsable INT NOT NULL,
                fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT pk_documento_proyecto PRIMARY KEY (id_documento),
                CONSTRAINT uq_documento_ruta UNIQUE (ruta),
                CONSTRAINT ck_documento_tipo CHECK (tipo IN ('Acta', 'Informe', 'Evidencia', 'Contrato', 'Otro')),
                CONSTRAINT ck_documento_tamano CHECK (tamano > 0),
                CONSTRAINT fk_documento_proyecto FOREIGN KEY (id_proyecto) REFERENCES proyecto (id_proyecto) ON UPDATE CASCADE ON DELETE RESTRICT,
                CONSTRAINT fk_documento_hito FOREIGN KEY (id_hito) REFERENCES hito (id_hito) ON UPDATE CASCADE ON DELETE SET NULL,
                CONSTRAINT fk_documento_responsable FOREIGN KEY (id_responsable) REFERENCES responsable (id_responsable) ON UPDATE CASCADE ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $db->statement("
            CREATE TABLE IF NOT EXISTS beneficio_proyecto (
                id_beneficio INT NOT NULL AUTO_INCREMENT,
                id_proyecto INT NOT NULL,
                descripcion VARCHAR(200) NOT NULL,
                indicador VARCHAR(120) NOT NULL,
                unidad VARCHAR(30) NOT NULL,
                linea_base DECIMAL(14,2) NOT NULL,
                meta DECIMAL(14,2) NOT NULL,
                valor_logrado DECIMAL(14,2) NULL,
                fecha_medicion DATE NULL,
                observacion VARCHAR(250) NULL,
                id_responsable INT NOT NULL,
                CONSTRAINT pk_beneficio_proyecto PRIMARY KEY (id_beneficio),
                CONSTRAINT ck_beneficio_meta CHECK (meta <> linea_base),
                CONSTRAINT ck_beneficio_medicion CHECK ((valor_logrado IS NULL AND fecha_medicion IS NULL) OR (valor_logrado IS NOT NULL AND fecha_medicion IS NOT NULL)),
                CONSTRAINT fk_beneficio_proyecto FOREIGN KEY (id_proyecto) REFERENCES proyecto (id_proyecto) ON UPDATE CASCADE ON DELETE RESTRICT,
                CONSTRAINT fk_beneficio_responsable FOREIGN KEY (id_responsable) REFERENCES responsable (id_responsable) ON UPDATE CASCADE ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /** Validaciones del actor reutilizadas por los triggers. */
    private function crearProcedimientos($db): void
    {
        $db->unprepared('DROP PROCEDURE IF EXISTS sp_exigir_administrador;');
        $db->unprepared("
            CREATE DEFINER = 'sig_definer'@'localhost' PROCEDURE sp_exigir_administrador()
            BEGIN
                DECLARE v_perfil VARCHAR(20) DEFAULT NULL;
                SELECT perfil INTO v_perfil FROM responsable WHERE id_responsable = @id_responsable AND activo = 1 LIMIT 1;
                IF v_perfil IS NULL OR v_perfil <> 'Administrador' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Solo un Administrador puede realizar esta operacion.';
                END IF;
            END;
        ");

        $db->unprepared('DROP PROCEDURE IF EXISTS sp_exigir_actor_proyecto;');
        $db->unprepared("
            CREATE DEFINER = 'sig_definer'@'localhost' PROCEDURE sp_exigir_actor_proyecto(IN p_id_proyecto INT)
            BEGIN
                DECLARE v_perfil VARCHAR(20) DEFAULT NULL;
                DECLARE v_titular INT DEFAULT NULL;
                SELECT perfil INTO v_perfil FROM responsable WHERE id_responsable = @id_responsable AND activo = 1 LIMIT 1;
                SELECT id_responsable INTO v_titular FROM proyecto WHERE id_proyecto = p_id_proyecto LIMIT 1;
                IF v_perfil IS NULL OR NOT (v_perfil = 'Administrador' OR (v_perfil = 'Responsable' AND v_titular = @id_responsable)) THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Solo el Administrador o el Responsable titular pueden registrar datos de este proyecto.';
                END IF;
            END;
        ");
    }

    private function crearReglas($db): void
    {
        $trigger = function (string $nombre, string $cuerpo) use ($db): void {
            $db->unprepared("DROP TRIGGER IF EXISTS {$nombre};");
            $db->unprepared($cuerpo);
        };

        // Priorización: criterios, pesos y calificaciones los define un Administrador (rol PMO hasta que exista).
        foreach (['bi' => 'BEFORE INSERT', 'bu' => 'BEFORE UPDATE'] as $sufijo => $momento) {
            $trigger("trg_criterio_{$sufijo}", "CREATE DEFINER = 'sig_definer'@'localhost' TRIGGER trg_criterio_{$sufijo} {$momento} ON criterio_priorizacion FOR EACH ROW BEGIN CALL sp_exigir_administrador(); END;");
            $trigger("trg_evaluacion_{$sufijo}", "CREATE DEFINER = 'sig_definer'@'localhost' TRIGGER trg_evaluacion_{$sufijo} {$momento} ON evaluacion_priorizacion FOR EACH ROW BEGIN
                CALL sp_exigir_administrador();
                IF CHAR_LENGTH(TRIM(NEW.sustento)) < 10 THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cada calificacion requiere un sustento de al menos 10 caracteres.';
                END IF;
                SET NEW.id_responsable = @id_responsable;
            END;");
            $trigger("trg_parametro_{$sufijo}", "CREATE DEFINER = 'sig_definer'@'localhost' TRIGGER trg_parametro_{$sufijo} {$momento} ON parametro_alerta FOR EACH ROW BEGIN CALL sp_exigir_administrador(); END;");
            // La ponderación de hitos es parte de la línea base: solo el Administrador.
            $trigger("trg_hito_peso_{$sufijo}", "CREATE DEFINER = 'sig_definer'@'localhost' TRIGGER trg_hito_peso_{$sufijo} {$momento} ON hito_peso FOR EACH ROW BEGIN CALL sp_exigir_administrador(); END;");
        }

        // Riesgos, documentos y beneficios: Administrador o Responsable titular del proyecto.
        foreach (['riesgo_proyecto' => 'riesgo', 'documento_proyecto' => 'documento', 'beneficio_proyecto' => 'beneficio'] as $tabla => $corto) {
            $extraInsert = $tabla === 'documento_proyecto' ? 'SET NEW.id_responsable = @id_responsable;' : '';
            $trigger("trg_{$corto}_bi", "CREATE DEFINER = 'sig_definer'@'localhost' TRIGGER trg_{$corto}_bi BEFORE INSERT ON {$tabla} FOR EACH ROW BEGIN CALL sp_exigir_actor_proyecto(NEW.id_proyecto); {$extraInsert} END;");
        }
        $trigger('trg_riesgo_bu', "CREATE DEFINER = 'sig_definer'@'localhost' TRIGGER trg_riesgo_bu BEFORE UPDATE ON riesgo_proyecto FOR EACH ROW BEGIN
            CALL sp_exigir_actor_proyecto(OLD.id_proyecto);
            IF NEW.id_proyecto <> OLD.id_proyecto THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Un riesgo no puede trasladarse a otro proyecto.';
            END IF;
            IF NEW.estado = 'Cerrado' AND OLD.estado <> 'Cerrado' AND NEW.fecha_cierre IS NULL THEN
                SET NEW.fecha_cierre = CURDATE();
            ELSEIF NEW.estado <> 'Cerrado' THEN
                SET NEW.fecha_cierre = NULL;
            END IF;
        END;");
        $trigger('trg_beneficio_bu', "CREATE DEFINER = 'sig_definer'@'localhost' TRIGGER trg_beneficio_bu BEFORE UPDATE ON beneficio_proyecto FOR EACH ROW BEGIN
            CALL sp_exigir_actor_proyecto(OLD.id_proyecto);
            IF NEW.id_proyecto <> OLD.id_proyecto THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Un beneficio no puede trasladarse a otro proyecto.';
            END IF;
        END;");
        // Un documento registrado es evidencia: no se modifica.
        $trigger('trg_documento_bu', "CREATE DEFINER = 'sig_definer'@'localhost' TRIGGER trg_documento_bu BEFORE UPDATE ON documento_proyecto FOR EACH ROW BEGIN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Los documentos registrados no se modifican; registre una nueva version.';
        END;");

        // Compromisos: solo el Administrador; la suma vigente no puede superar el presupuesto vigente.
        $validarCompromiso = "
            DECLARE v_monto DECIMAL(12,2);
            DECLARE v_comprometido DECIMAL(12,2);
            CALL sp_exigir_administrador();
            SELECT monto INTO v_monto FROM proyecto WHERE id_proyecto = NEW.id_proyecto;
            SELECT COALESCE(SUM(monto), 0) INTO v_comprometido FROM compromiso_presupuestal
             WHERE id_proyecto = NEW.id_proyecto AND estado = 'Vigente' AND id_compromiso <> COALESCE(NEW.id_compromiso, 0);
            IF NEW.estado = 'Vigente' AND v_comprometido + NEW.monto > v_monto THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Los compromisos vigentes superarian el presupuesto vigente del proyecto.';
            END IF;";
        $trigger('trg_compromiso_bi', "CREATE DEFINER = 'sig_definer'@'localhost' TRIGGER trg_compromiso_bi BEFORE INSERT ON compromiso_presupuestal FOR EACH ROW BEGIN
            {$validarCompromiso}
            SET NEW.id_responsable = @id_responsable;
        END;");
        $trigger('trg_compromiso_bu', "CREATE DEFINER = 'sig_definer'@'localhost' TRIGGER trg_compromiso_bu BEFORE UPDATE ON compromiso_presupuestal FOR EACH ROW BEGIN
            {$validarCompromiso}
            IF NEW.id_proyecto <> OLD.id_proyecto OR NEW.documento <> OLD.documento OR NEW.monto <> OLD.monto OR NEW.fecha <> OLD.fecha THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Un compromiso solo puede anularse; registre uno nuevo para cambiar sus datos.';
            END IF;
            IF NEW.estado = 'Anulado' AND OLD.estado = 'Vigente' AND (@motivo_cambio IS NULL OR TRIM(@motivo_cambio) = '') THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Anular un compromiso requiere registrar un motivo.';
            END IF;
            IF NEW.estado = 'Vigente' AND OLD.estado = 'Anulado' THEN
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Un compromiso anulado no puede reactivarse.';
            END IF;
        END;");

        // El presupuesto vigente no puede quedar por debajo de lo comprometido.
        $trigger('trg_proyecto_comp_bu', "CREATE DEFINER = 'sig_definer'@'localhost' TRIGGER trg_proyecto_comp_bu BEFORE UPDATE ON proyecto FOR EACH ROW FOLLOWS trg_proyecto_bu BEGIN
            DECLARE v_comprometido DECIMAL(12,2);
            IF NEW.monto < OLD.monto THEN
                SELECT COALESCE(SUM(monto), 0) INTO v_comprometido FROM compromiso_presupuestal WHERE id_proyecto = NEW.id_proyecto AND estado = 'Vigente';
                IF NEW.monto < v_comprometido THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se puede reducir el monto por debajo de los compromisos vigentes.';
                END IF;
            END IF;
        END;");

        // Versiones del presupuesto: alta y cada cambio de monto.
        $versionar = fn (string $anterior) => "
            DECLARE v_version INT DEFAULT 1;
            SELECT COALESCE(MAX(version), 0) + 1 INTO v_version FROM proyecto_presupuesto_version WHERE id_proyecto = NEW.id_proyecto;
            INSERT INTO proyecto_presupuesto_version (id_proyecto, version, monto_anterior, monto, motivo, id_responsable)
            VALUES (NEW.id_proyecto, v_version, {$anterior}, NEW.monto, LEFT(@motivo_cambio, 200), @id_responsable);";
        $trigger('trg_proyecto_ppto_ai', "CREATE DEFINER = 'sig_definer'@'localhost' TRIGGER trg_proyecto_ppto_ai AFTER INSERT ON proyecto FOR EACH ROW FOLLOWS trg_proyecto_ai BEGIN {$versionar('NULL')} END;");
        $trigger('trg_proyecto_ppto_au', "CREATE DEFINER = 'sig_definer'@'localhost' TRIGGER trg_proyecto_ppto_au AFTER UPDATE ON proyecto FOR EACH ROW FOLLOWS trg_proyecto_au BEGIN
            IF NEW.monto <> OLD.monto THEN BEGIN {$versionar('OLD.monto')} END; END IF;
        END;");
    }

    /** Alta y cambios de las tablas nuevas quedan en bitacora_cambio (misma auditoría que el núcleo). */
    private function crearAuditoria($db): void
    {
        $auditar = [
            'criterio_priorizacion' => ['id_criterio', 'codigo', ['nombre', 'descripcion', 'peso', 'activo']],
            'evaluacion_priorizacion' => ['id_evaluacion', "CONCAT('P', NEW.id_proyecto, ' C', NEW.id_criterio, ' = ', NEW.puntaje)", ['puntaje', 'sustento']],
            'riesgo_proyecto' => ['id_riesgo', 'descripcion', ['descripcion', 'probabilidad', 'impacto', 'id_responsable', 'plan_respuesta', 'estado']],
            'hito_peso' => ['id_hito', 'peso', ['peso']],
            'compromiso_presupuestal' => ['id_compromiso', "CONCAT(NEW.documento, ' S/ ', NEW.monto)", ['estado']],
            'parametro_alerta' => [null, null, ['valor']],
            'documento_proyecto' => ['id_documento', "CONCAT(NEW.tipo, ': ', NEW.titulo)", []],
            'beneficio_proyecto' => ['id_beneficio', 'indicador', ['descripcion', 'indicador', 'linea_base', 'meta', 'valor_logrado', 'fecha_medicion']],
        ];

        foreach ($auditar as $tabla => [$pk, $resumen, $campos]) {
            $corto = substr(str_replace('_', '', $tabla), 0, 20);
            if ($pk !== null) {
                $valor = str_contains((string) $resumen, '(') ? $resumen : "NEW.{$resumen}";
                $db->unprepared("DROP TRIGGER IF EXISTS trg_aud_{$corto}_ai;");
                $db->unprepared("CREATE DEFINER = 'sig_definer'@'localhost' TRIGGER trg_aud_{$corto}_ai AFTER INSERT ON {$tabla} FOR EACH ROW BEGIN
                    CALL sp_registrar_cambio('{$tabla}', NEW.{$pk}, 'CREACION', NULL, LEFT(CAST({$valor} AS CHAR), 200));
                END;");
            }
            if ($campos !== []) {
                $id = $pk !== null ? "NEW.{$pk}" : '0';
                $cuerpo = '';
                foreach ($campos as $c) {
                    $nombreCampo = $pk === null ? "CONCAT(NEW.clave, '.{$c}')" : "'{$c}'";
                    $cuerpo .= "IF NOT (OLD.{$c} <=> NEW.{$c}) THEN CALL sp_registrar_cambio('{$tabla}', {$id}, LEFT({$nombreCampo}, 40), LEFT(CAST(OLD.{$c} AS CHAR), 200), LEFT(CAST(NEW.{$c} AS CHAR), 200)); END IF;\n";
                }
                $db->unprepared("DROP TRIGGER IF EXISTS trg_aud_{$corto}_au;");
                $db->unprepared("CREATE DEFINER = 'sig_definer'@'localhost' TRIGGER trg_aud_{$corto}_au AFTER UPDATE ON {$tabla} FOR EACH ROW BEGIN\n{$cuerpo}END;");
            }
        }
    }

    /** Versión 1 del presupuesto para proyectos ya existentes. */
    private function versionarPresupuestoExistente($db): void
    {
        $admin = $db->table('responsable')->where('perfil', 'Administrador')->orderBy('id_responsable')->value('id_responsable');
        if ($admin === null) {
            return;
        }
        $db->statement("
            INSERT INTO proyecto_presupuesto_version (id_proyecto, version, monto_anterior, monto, motivo, id_responsable)
            SELECT p.id_proyecto, 1, NULL, p.monto, 'Presupuesto vigente al activar el versionado', ?
            FROM proyecto p
            WHERE NOT EXISTS (SELECT 1 FROM proyecto_presupuesto_version v WHERE v.id_proyecto = p.id_proyecto)
        ", [$admin]);
    }

    private function otorgarPermisos($db): void
    {
        $schema = $db->getDatabaseName();
        $lectura = ['criterio_priorizacion', 'evaluacion_priorizacion', 'riesgo_proyecto', 'hito_peso', 'proyecto_presupuesto_version',
            'compromiso_presupuestal', 'parametro_alerta', 'documento_proyecto', 'beneficio_proyecto'];
        $escritura = [
            'criterio_priorizacion' => 'INSERT, UPDATE',
            'evaluacion_priorizacion' => 'INSERT, UPDATE',
            'riesgo_proyecto' => 'INSERT, UPDATE',
            'hito_peso' => 'INSERT, UPDATE',
            'compromiso_presupuestal' => 'INSERT, UPDATE',
            'parametro_alerta' => 'INSERT, UPDATE',
            'documento_proyecto' => 'INSERT',
            'beneficio_proyecto' => 'INSERT, UPDATE',
        ];
        foreach (['localhost', '127.0.0.1'] as $host) {
            $sentencias = [];
            foreach ($lectura as $tabla) {
                foreach (['sig_lector', 'sig_escritor', 'sig_etl', 'sig_definer'] as $cuenta) {
                    $sentencias[] = "GRANT SELECT ON `{$schema}`.`{$tabla}` TO '{$cuenta}'@'{$host}'";
                }
            }
            foreach ($escritura as $tabla => $privilegios) {
                $sentencias[] = "GRANT {$privilegios} ON `{$schema}`.`{$tabla}` TO 'sig_escritor'@'{$host}'";
            }
            $sentencias[] = "GRANT INSERT ON `{$schema}`.`proyecto_presupuesto_version` TO 'sig_definer'@'{$host}'";
            // Los triggers fijan estas columnas con SET NEW (actor real y fecha de cierre): MariaDB exige UPDATE al definer.
            foreach (self::COLUMNAS_FIJADAS_POR_TRIGGER as $tabla => $columna) {
                $sentencias[] = "GRANT UPDATE ({$columna}) ON `{$schema}`.`{$tabla}` TO 'sig_definer'@'{$host}'";
            }
            foreach ($sentencias as $sql) {
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
        $triggers = $db->select("SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE()
            AND (TRIGGER_NAME LIKE 'trg_aud_%' OR TRIGGER_NAME IN ('trg_criterio_bi','trg_criterio_bu','trg_evaluacion_bi','trg_evaluacion_bu',
            'trg_parametro_bi','trg_parametro_bu','trg_hito_peso_bi','trg_hito_peso_bu','trg_riesgo_bi','trg_riesgo_bu','trg_documento_bi',
            'trg_documento_bu','trg_beneficio_bi','trg_beneficio_bu','trg_compromiso_bi','trg_compromiso_bu','trg_proyecto_comp_bu',
            'trg_proyecto_ppto_ai','trg_proyecto_ppto_au'))");
        foreach ($triggers as $t) {
            $db->unprepared("DROP TRIGGER IF EXISTS {$t->TRIGGER_NAME};");
        }
        $db->unprepared('DROP PROCEDURE IF EXISTS sp_exigir_administrador;');
        $db->unprepared('DROP PROCEDURE IF EXISTS sp_exigir_actor_proyecto;');
        foreach (['beneficio_proyecto', 'documento_proyecto', 'parametro_alerta', 'compromiso_presupuestal', 'proyecto_presupuesto_version',
            'hito_peso', 'riesgo_proyecto', 'evaluacion_priorizacion', 'criterio_priorizacion'] as $tabla) {
            $db->statement("DROP TABLE IF EXISTS {$tabla}");
        }
        $nucleo = "('objetivo_gd', 'responsable', 'proyecto', 'hito', 'avance_presupuestal')";
        $db->statement("DELETE m FROM bitacora_motivo m JOIN bitacora_cambio b ON b.id_cambio = m.id_cambio WHERE b.tabla_afectada NOT IN {$nucleo}");
        $db->statement("DELETE FROM bitacora_cambio WHERE tabla_afectada NOT IN {$nucleo}");
        $db->statement('ALTER TABLE bitacora_cambio DROP CONSTRAINT ck_bitacora_tabla');
        $db->statement("ALTER TABLE bitacora_cambio ADD CONSTRAINT ck_bitacora_tabla CHECK (tabla_afectada IN ('objetivo_gd', 'responsable', 'proyecto', 'hito', 'avance_presupuestal'))");
    }
};
