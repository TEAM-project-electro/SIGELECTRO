<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['id_objetivo', 'id_responsable'] as $column) {
            if (!Schema::connection('dw_migrador')->hasColumn('dim_proyecto', $column)) {
                DB::connection('dw_migrador')->statement("ALTER TABLE dim_proyecto ADD COLUMN {$column} INT NULL");
            }
        }
        // Reemplaza únicamente rutinas/vistas/triggers: conserva las seis tablas y sus datos.
        (require __DIR__.'/2026_11_03_000002_create_triggers_y_procedimientos.php')->up();
        (require __DIR__.'/2026_11_03_000004_create_dw_procedimientos_y_vistas.php')->up();
    }

    public function down(): void
    {
        throw new RuntimeException('Restaurar un respaldo verificado para revertir las reglas; no degradar auditoría automáticamente.');
    }
};
