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
        // 1. DIM_TIEMPO
        DB::connection('dw_migrador')->statement("
            CREATE TABLE IF NOT EXISTS dim_tiempo (
                sk_tiempo INT NOT NULL,
                fecha DATE NOT NULL,
                anio INT NOT NULL,
                semestre INT NOT NULL,
                trimestre INT NOT NULL,
                mes INT NOT NULL,
                nombre_mes VARCHAR(20) NOT NULL,
                dia_semana INT NOT NULL,
                nombre_dia VARCHAR(20) NOT NULL,
                es_fin_mes TINYINT(1) NOT NULL DEFAULT 0,
                CONSTRAINT pk_dim_tiempo PRIMARY KEY (sk_tiempo),
                CONSTRAINT uq_dim_tiempo_fecha UNIQUE (fecha)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 2. DIM_PROYECTO (Tipo 1)
        DB::connection('dw_migrador')->statement("
            CREATE TABLE IF NOT EXISTS dim_proyecto (
                sk_proyecto INT NOT NULL AUTO_INCREMENT,
                id_proyecto INT NOT NULL,
                id_objetivo INT NULL,
                id_responsable INT NULL,
                codigo_pgd VARCHAR(10) NOT NULL,
                nombre VARCHAR(200) NOT NULL,
                monto DECIMAL(12,2) NOT NULL,
                rango_monto VARCHAR(50) NOT NULL,
                anio_inicio INT NOT NULL,
                anio_fin INT NOT NULL,
                estado_actual VARCHAR(20) NOT NULL,
                es_activo TINYINT(1) NOT NULL DEFAULT 1,
                fuente VARCHAR(100) NOT NULL,
                CONSTRAINT pk_dim_proyecto PRIMARY KEY (sk_proyecto),
                CONSTRAINT uq_dim_proyecto_id UNIQUE (id_proyecto)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 3. DIM_OBJETIVO (Tipo 1)
        DB::connection('dw_migrador')->statement("
            CREATE TABLE IF NOT EXISTS dim_objetivo (
                sk_objetivo INT NOT NULL AUTO_INCREMENT,
                id_objetivo INT NOT NULL,
                codigo VARCHAR(10) NOT NULL,
                descripcion VARCHAR(255) NOT NULL,
                CONSTRAINT pk_dim_objetivo PRIMARY KEY (sk_objetivo),
                CONSTRAINT uq_dim_objetivo_id UNIQUE (id_objetivo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 4. DIM_RESPONSABLE (Tipo 1, sin usuario ni clave)
        DB::connection('dw_migrador')->statement("
            CREATE TABLE IF NOT EXISTS dim_responsable (
                sk_responsable INT NOT NULL AUTO_INCREMENT,
                id_responsable INT NOT NULL,
                nombre VARCHAR(100) NOT NULL,
                area VARCHAR(100) NOT NULL,
                perfil VARCHAR(50) NOT NULL,
                es_activo TINYINT(1) NOT NULL DEFAULT 1,
                CONSTRAINT pk_dim_responsable PRIMARY KEY (sk_responsable),
                CONSTRAINT uq_dim_responsable_id UNIQUE (id_responsable)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 5. FACT_AVANCE_PROYECTO (Granularidad: proyecto y fecha de reporte)
        DB::connection('dw_migrador')->statement("
            CREATE TABLE IF NOT EXISTS fact_avance_proyecto (
                id_hecho_avance INT NOT NULL AUTO_INCREMENT,
                sk_proyecto INT NOT NULL,
                sk_objetivo INT NOT NULL,
                sk_responsable INT NOT NULL,
                sk_tiempo INT NOT NULL,
                id_proyecto INT NOT NULL,
                fecha_reporte DATE NOT NULL,
                presupuesto_planificado DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                gasto_acumulado DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                diferencia_gasto DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                monto_proyecto DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                hitos_linea_base INT NOT NULL DEFAULT 0,
                hitos_concluidos INT NOT NULL DEFAULT 0,
                hitos_vencidos INT NOT NULL DEFAULT 0,
                con_retraso TINYINT(1) NOT NULL DEFAULT 0,
                es_ultimo_corte TINYINT(1) NOT NULL DEFAULT 0,
                CONSTRAINT pk_fact_avance_proyecto PRIMARY KEY (id_hecho_avance),
                CONSTRAINT uq_fact_avance_proyecto_corte UNIQUE (id_proyecto, fecha_reporte),
                CONSTRAINT fk_fact_avance_proyecto FOREIGN KEY (sk_proyecto)
                    REFERENCES dim_proyecto (sk_proyecto) ON UPDATE CASCADE ON DELETE RESTRICT,
                CONSTRAINT fk_fact_avance_objetivo FOREIGN KEY (sk_objetivo)
                    REFERENCES dim_objetivo (sk_objetivo) ON UPDATE CASCADE ON DELETE RESTRICT,
                CONSTRAINT fk_fact_avance_responsable FOREIGN KEY (sk_responsable)
                    REFERENCES dim_responsable (sk_responsable) ON UPDATE CASCADE ON DELETE RESTRICT,
                CONSTRAINT fk_fact_avance_tiempo FOREIGN KEY (sk_tiempo)
                    REFERENCES dim_tiempo (sk_tiempo) ON UPDATE CASCADE ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 6. FACT_HITO (Granularidad: hito individual)
        DB::connection('dw_migrador')->statement("
            CREATE TABLE IF NOT EXISTS fact_hito (
                id_hecho_hito INT NOT NULL AUTO_INCREMENT,
                sk_proyecto INT NOT NULL,
                sk_objetivo INT NOT NULL,
                sk_responsable INT NOT NULL,
                sk_tiempo_previsto INT NOT NULL,
                sk_tiempo_cierre INT NULL,
                id_hito INT NOT NULL,
                id_proyecto INT NOT NULL,
                descripcion VARCHAR(150) NOT NULL,
                en_linea_base TINYINT(1) NOT NULL DEFAULT 1,
                concluido TINYINT(1) NOT NULL DEFAULT 0,
                vencido TINYINT(1) NOT NULL DEFAULT 0,
                dias_desfase INT NULL,
                concluido_a_tiempo TINYINT(1) NULL,
                tiene_evidencia TINYINT(1) NOT NULL DEFAULT 0,
                estado_hito VARCHAR(20) NOT NULL,
                CONSTRAINT pk_fact_hito PRIMARY KEY (id_hecho_hito),
                CONSTRAINT uq_fact_hito_id UNIQUE (id_hito),
                CONSTRAINT fk_fact_hito_proyecto FOREIGN KEY (sk_proyecto)
                    REFERENCES dim_proyecto (sk_proyecto) ON UPDATE CASCADE ON DELETE RESTRICT,
                CONSTRAINT fk_fact_hito_objetivo FOREIGN KEY (sk_objetivo)
                    REFERENCES dim_objetivo (sk_objetivo) ON UPDATE CASCADE ON DELETE RESTRICT,
                CONSTRAINT fk_fact_hito_responsable FOREIGN KEY (sk_responsable)
                    REFERENCES dim_responsable (sk_responsable) ON UPDATE CASCADE ON DELETE RESTRICT,
                CONSTRAINT fk_fact_hito_tiempo_previsto FOREIGN KEY (sk_tiempo_previsto)
                    REFERENCES dim_tiempo (sk_tiempo) ON UPDATE CASCADE ON DELETE RESTRICT,
                CONSTRAINT fk_fact_hito_tiempo_cierre FOREIGN KEY (sk_tiempo_cierre)
                    REFERENCES dim_tiempo (sk_tiempo) ON UPDATE CASCADE ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 7. ETL_CONTROL (Registro auditable de cargas ETL)
        DB::connection('dw_migrador')->statement("
            CREATE TABLE IF NOT EXISTS etl_control (
                id_carga INT NOT NULL AUTO_INCREMENT,
                fecha_inicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                fecha_fin DATETIME NULL,
                estado VARCHAR(20) NOT NULL DEFAULT 'En ejecucion',
                filas_avance INT NOT NULL DEFAULT 0,
                filas_hito INT NOT NULL DEFAULT 0,
                revision_origen BIGINT NOT NULL DEFAULT 1,
                mensaje VARCHAR(255) NULL,
                CONSTRAINT pk_etl_control PRIMARY KEY (id_carga)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::connection('dw_migrador')->statement("DROP TABLE IF EXISTS etl_control;");
        DB::connection('dw_migrador')->statement("DROP TABLE IF EXISTS fact_hito;");
        DB::connection('dw_migrador')->statement("DROP TABLE IF EXISTS fact_avance_proyecto;");
        DB::connection('dw_migrador')->statement("DROP TABLE IF EXISTS dim_responsable;");
        DB::connection('dw_migrador')->statement("DROP TABLE IF EXISTS dim_objetivo;");
        DB::connection('dw_migrador')->statement("DROP TABLE IF EXISTS dim_proyecto;");
        DB::connection('dw_migrador')->statement("DROP TABLE IF EXISTS dim_tiempo;");
    }
};
