<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE TABLE IF NOT EXISTS abastecimiento_compra (
                id_abastecimiento INT NOT NULL AUTO_INCREMENT,
                id_proyecto INT NOT NULL,
                id_externo_linea VARCHAR(120) NOT NULL,
                orden_compra VARCHAR(40) NOT NULL,
                fecha_orden DATE NULL,
                proveedor VARCHAR(150) NOT NULL,
                producto VARCHAR(200) NOT NULL,
                descripcion VARCHAR(255) NULL,
                cantidad_comprada DECIMAL(12,2) NOT NULL,
                cantidad_recibida DECIMAL(12,2) NOT NULL,
                saldo_pendiente DECIMAL(12,2) NOT NULL,
                importe_total DECIMAL(12,2) NULL,
                archivo_origen VARCHAR(150) NULL,
                id_responsable_carga INT NOT NULL,
                fecha_carga DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT pk_abastecimiento_compra PRIMARY KEY (id_abastecimiento),
                CONSTRAINT uq_abastecimiento_id_externo UNIQUE (id_externo_linea),
                CONSTRAINT ck_abastecimiento_cantidades CHECK (cantidad_comprada > 0 AND cantidad_recibida >= 0 AND cantidad_recibida <= cantidad_comprada),
                CONSTRAINT fk_abastecimiento_proyecto FOREIGN KEY (id_proyecto)
                    REFERENCES proyecto (id_proyecto) ON UPDATE CASCADE ON DELETE RESTRICT,
                CONSTRAINT fk_abastecimiento_responsable FOREIGN KEY (id_responsable_carga)
                    REFERENCES responsable (id_responsable) ON UPDATE CASCADE ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $schema = DB::getDatabaseName();
        foreach ([
            "GRANT SELECT ON `{$schema}`.`abastecimiento_compra` TO 'sig_lector'@'localhost'",
            "GRANT SELECT, INSERT, UPDATE ON `{$schema}`.`abastecimiento_compra` TO 'sig_escritor'@'localhost'",
        ] as $sql) {
            try {
                DB::statement($sql);
            } catch (\Throwable $e) {
                // Ignorar si los usuarios técnicos no existen en un entorno aislado.
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('abastecimiento_compra');
    }
};
