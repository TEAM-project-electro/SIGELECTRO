<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Extensión técnica del asistente RAG (Laudon Cap. 11). No modifica las 6 tablas canónicas.
 * Decisión: docs/decisiones/2026-11-25_asistente_rag.md
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE TABLE IF NOT EXISTS rag_documento (
                id_documento INT NOT NULL AUTO_INCREMENT,
                ruta VARCHAR(255) NOT NULL,
                titulo VARCHAR(200) NOT NULL,
                hash_contenido CHAR(64) NOT NULL,
                fragmentos INT NOT NULL DEFAULT 0,
                fragmentos_bloqueados INT NOT NULL DEFAULT 0,
                fecha_indexacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT pk_rag_documento PRIMARY KEY (id_documento),
                CONSTRAINT uq_rag_documento_ruta UNIQUE (ruta)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        DB::statement("
            CREATE TABLE IF NOT EXISTS rag_fragmento (
                id_fragmento INT NOT NULL AUTO_INCREMENT,
                id_documento INT NOT NULL,
                orden INT NOT NULL,
                seccion VARCHAR(255) NOT NULL,
                texto MEDIUMTEXT NOT NULL,
                terminos MEDIUMTEXT NOT NULL,
                longitud INT NOT NULL,
                embedding MEDIUMTEXT NULL,
                bloqueado TINYINT(1) NOT NULL DEFAULT 0,
                CONSTRAINT pk_rag_fragmento PRIMARY KEY (id_fragmento),
                CONSTRAINT fk_rag_fragmento_documento FOREIGN KEY (id_documento)
                    REFERENCES rag_documento (id_documento) ON UPDATE CASCADE ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        DB::statement("
            CREATE TABLE IF NOT EXISTS rag_consulta (
                id_consulta INT NOT NULL AUTO_INCREMENT,
                id_responsable INT NOT NULL,
                pregunta VARCHAR(500) NOT NULL,
                modo VARCHAR(20) NOT NULL,
                fuentes VARCHAR(255) NULL,
                motivo_bloqueo VARCHAR(80) NULL,
                fecha_consulta DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT pk_rag_consulta PRIMARY KEY (id_consulta),
                CONSTRAINT ck_rag_consulta_modo CHECK (modo IN ('generativo','extractivo','bloqueado')),
                CONSTRAINT fk_rag_consulta_responsable FOREIGN KEY (id_responsable)
                    REFERENCES responsable (id_responsable) ON UPDATE CASCADE ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $schema = DB::getDatabaseName();
        foreach ([
            "GRANT SELECT ON `{$schema}`.`rag_documento` TO 'sig_lector'@'localhost'",
            "GRANT SELECT ON `{$schema}`.`rag_fragmento` TO 'sig_lector'@'localhost'",
            "GRANT INSERT ON `{$schema}`.`rag_consulta` TO 'sig_escritor'@'localhost'",
        ] as $sql) {
            try {
                DB::statement($sql);
            } catch (\Throwable $e) {
                // Cuentas técnicas ausentes en un entorno aislado.
            }
        }
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS rag_consulta');
        DB::statement('DROP TABLE IF EXISTS rag_fragmento');
        DB::statement('DROP TABLE IF EXISTS rag_documento');
    }
};
