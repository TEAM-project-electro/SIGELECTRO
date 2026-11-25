<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Refuerza el trigger trg_hito_bu (hallazgo P0-1 de auditoría):
 * reprogramar la fecha prevista de un hito en línea base exige motivo de cambio obligatorio.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::connection('mysql')->unprepared("DROP TRIGGER IF EXISTS trg_hito_bu;");
        DB::connection('mysql')->unprepared("
            CREATE DEFINER = 'sig_definer'@'localhost' TRIGGER trg_hito_bu BEFORE UPDATE ON hito FOR EACH ROW
            BEGIN
                DECLARE v_estado_proy VARCHAR(20);
                SELECT estado INTO v_estado_proy FROM proyecto WHERE id_proyecto = NEW.id_proyecto;
                IF v_estado_proy = 'Cerrado' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se pueden modificar hitos de un proyecto cerrado. Reabra el proyecto primero.';
                END IF;

                -- Modificación de pertenencia o fecha prevista de línea base exige motivo (Decisión 11 / P0-1)
                IF NEW.en_linea_base <> OLD.en_linea_base OR (OLD.en_linea_base = 1 AND NEW.fecha_prevista <> OLD.fecha_prevista) THEN
                    IF @motivo_cambio IS NULL OR TRIM(@motivo_cambio) = '' THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Modificar la linea base o reprogramar su fecha prevista requiere registrar un motivo.';
                    END IF;
                END IF;
            END;
        ");
    }

    public function down(): void
    {
        DB::connection('mysql')->unprepared("DROP TRIGGER IF EXISTS trg_hito_bu;");
        DB::connection('mysql')->unprepared("
            CREATE DEFINER = 'sig_definer'@'localhost' TRIGGER trg_hito_bu BEFORE UPDATE ON hito FOR EACH ROW
            BEGIN
                DECLARE v_estado_proy VARCHAR(20);
                SELECT estado INTO v_estado_proy FROM proyecto WHERE id_proyecto = NEW.id_proyecto;
                IF v_estado_proy = 'Cerrado' THEN
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'No se pueden modificar hitos de un proyecto cerrado. Reabra el proyecto primero.';
                END IF;

                IF NEW.en_linea_base <> OLD.en_linea_base THEN
                    IF @motivo_cambio IS NULL OR TRIM(@motivo_cambio) = '' THEN
                        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Modificar la linea base de un hito requiere registrar un motivo.';
                    END IF;
                END IF;
            END;
        ");
    }
};
