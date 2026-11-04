<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Crea las 6 tablas operativas canónicas y las 2 extensiones técnicas.
     */
    public function up(): void
    {
        // 1. OBJETIVO_GD
        DB::statement("
            CREATE TABLE IF NOT EXISTS objetivo_gd (
                id_objetivo INT NOT NULL AUTO_INCREMENT,
                codigo VARCHAR(10) NOT NULL,
                descripcion VARCHAR(200) NOT NULL,
                CONSTRAINT pk_objetivo_gd PRIMARY KEY (id_objetivo),
                CONSTRAINT uq_objetivo_codigo UNIQUE (codigo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 2. RESPONSABLE
        DB::statement("
            CREATE TABLE IF NOT EXISTS responsable (
                id_responsable INT NOT NULL AUTO_INCREMENT,
                nombre VARCHAR(100) NOT NULL,
                area VARCHAR(80) NOT NULL,
                perfil VARCHAR(20) NOT NULL,
                usuario VARCHAR(30) NOT NULL,
                clave_hash VARCHAR(255) NOT NULL,
                activo TINYINT(1) NOT NULL DEFAULT 1,
                CONSTRAINT pk_responsable PRIMARY KEY (id_responsable),
                CONSTRAINT uq_responsable_usuario UNIQUE (usuario),
                CONSTRAINT ck_responsable_perfil CHECK (perfil IN ('Administrador', 'Responsable', 'Consulta')),
                CONSTRAINT ck_responsable_activo CHECK (activo IN (0, 1))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 3. PROYECTO
        DB::statement("
            CREATE TABLE IF NOT EXISTS proyecto (
                id_proyecto INT NOT NULL AUTO_INCREMENT,
                codigo_pgd VARCHAR(10) NOT NULL,
                nombre VARCHAR(150) NOT NULL,
                monto DECIMAL(12,2) NOT NULL,
                anio_inicio INT NOT NULL,
                anio_fin INT NOT NULL,
                estado VARCHAR(20) NOT NULL DEFAULT 'Pendiente',
                id_objetivo INT NOT NULL,
                id_responsable INT NOT NULL,
                fuente VARCHAR(100) NOT NULL DEFAULT 'Cartera PGD 2023 a 2026',
                fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT pk_proyecto PRIMARY KEY (id_proyecto),
                CONSTRAINT uq_proyecto_codigo UNIQUE (codigo_pgd),
                CONSTRAINT ck_proyecto_monto CHECK (monto >= 0),
                CONSTRAINT ck_proyecto_anios CHECK (anio_fin >= anio_inicio),
                CONSTRAINT ck_proyecto_estado CHECK (estado IN ('Pendiente', 'En curso', 'Cerrado')),
                CONSTRAINT fk_proyecto_objetivo FOREIGN KEY (id_objetivo)
                    REFERENCES objetivo_gd (id_objetivo) ON UPDATE CASCADE ON DELETE RESTRICT,
                CONSTRAINT fk_proyecto_responsable FOREIGN KEY (id_responsable)
                    REFERENCES responsable (id_responsable) ON UPDATE CASCADE ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 4. HITO
        DB::statement("
            CREATE TABLE IF NOT EXISTS hito (
                id_hito INT NOT NULL AUTO_INCREMENT,
                id_proyecto INT NOT NULL,
                descripcion VARCHAR(150) NOT NULL,
                fecha_prevista DATE NOT NULL,
                fecha_cierre DATE NULL,
                evidencia VARCHAR(200) NULL,
                en_linea_base TINYINT(1) NOT NULL DEFAULT 1,
                CONSTRAINT pk_hito PRIMARY KEY (id_hito),
                CONSTRAINT ck_hito_linea_base CHECK (en_linea_base IN (0, 1)),
                CONSTRAINT fk_hito_proyecto FOREIGN KEY (id_proyecto)
                    REFERENCES proyecto (id_proyecto) ON UPDATE CASCADE ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 5. AVANCE_PRESUPUESTAL
        DB::statement("
            CREATE TABLE IF NOT EXISTS avance_presupuestal (
                id_avance INT NOT NULL AUTO_INCREMENT,
                id_proyecto INT NOT NULL,
                fecha_reporte DATE NOT NULL,
                presupuesto_planificado DECIMAL(12,2) NOT NULL,
                gasto_acumulado DECIMAL(12,2) NOT NULL,
                CONSTRAINT pk_avance_presupuestal PRIMARY KEY (id_avance),
                CONSTRAINT uq_avance_proyecto_fecha UNIQUE (id_proyecto, fecha_reporte),
                CONSTRAINT ck_avance_presupuesto CHECK (presupuesto_planificado >= 0),
                CONSTRAINT ck_avance_gasto CHECK (gasto_acumulado >= 0),
                CONSTRAINT fk_avance_proyecto FOREIGN KEY (id_proyecto)
                    REFERENCES proyecto (id_proyecto) ON UPDATE CASCADE ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 6. BITACORA_CAMBIO
        DB::statement("
            CREATE TABLE IF NOT EXISTS bitacora_cambio (
                id_cambio INT NOT NULL AUTO_INCREMENT,
                tabla_afectada VARCHAR(30) NOT NULL,
                id_registro INT NOT NULL,
                campo VARCHAR(40) NOT NULL,
                valor_anterior VARCHAR(200) NULL,
                valor_nuevo VARCHAR(200) NULL,
                fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                id_responsable INT NOT NULL,
                CONSTRAINT pk_bitacora_cambio PRIMARY KEY (id_cambio),
                CONSTRAINT ck_bitacora_tabla CHECK (tabla_afectada IN ('objetivo_gd', 'responsable', 'proyecto', 'hito', 'avance_presupuestal')),
                CONSTRAINT fk_bitacora_responsable FOREIGN KEY (id_responsable)
                    REFERENCES responsable (id_responsable) ON UPDATE CASCADE ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // EXTENSIÓN TÉCNICA 1: CONTROL_REVISION
        DB::statement("
            CREATE TABLE IF NOT EXISTS control_revision (
                id INT NOT NULL DEFAULT 1,
                version BIGINT NOT NULL DEFAULT 1,
                fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT pk_control_revision PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Fila única inicial de control_revision
        DB::statement("
            INSERT INTO control_revision (id, version, fecha_hora)
            VALUES (1, 1, NOW())
            ON DUPLICATE KEY UPDATE id=id;
        ");

        // EXTENSIÓN TÉCNICA 2: BITACORA_MOTIVO (Decisión 11)
        DB::statement("
            CREATE TABLE IF NOT EXISTS bitacora_motivo (
                id_cambio INT NOT NULL,
                motivo VARCHAR(200) NOT NULL,
                CONSTRAINT pk_bitacora_motivo PRIMARY KEY (id_cambio),
                CONSTRAINT fk_bitacora_motivo_cambio FOREIGN KEY (id_cambio)
                    REFERENCES bitacora_cambio (id_cambio) ON UPDATE CASCADE ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // ÍNDICES DE APOYO DEL INFORME (Tabla 23)
        DB::statement("CREATE INDEX IF NOT EXISTS ix_bitacora_tabla_registro ON bitacora_cambio (tabla_afectada, id_registro);");
        DB::statement("CREATE INDEX IF NOT EXISTS ix_hito_proyecto_fecha ON hito (id_proyecto, fecha_prevista);");
        DB::statement("CREATE INDEX IF NOT EXISTS ix_proyecto_estado ON proyecto (estado);");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP TABLE IF EXISTS bitacora_motivo;");
        DB::statement("DROP TABLE IF EXISTS control_revision;");
        DB::statement("DROP TABLE IF EXISTS bitacora_cambio;");
        DB::statement("DROP TABLE IF EXISTS avance_presupuestal;");
        DB::statement("DROP TABLE IF EXISTS hito;");
        DB::statement("DROP TABLE IF EXISTS proyecto;");
        DB::statement("DROP TABLE IF EXISTS responsable;");
        DB::statement("DROP TABLE IF EXISTS objetivo_gd;");
    }
};
