<?php
use Illuminate\Database\Migrations\Migration;
return new class extends Migration {
    public function up(): void { (require __DIR__.'/2026_11_03_000004_create_dw_procedimientos_y_vistas.php')->up(); }
    public function down(): void { throw new RuntimeException('No degradar el snapshot ETL automáticamente.'); }
};
