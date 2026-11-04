<?php
use Illuminate\Database\Migrations\Migration;
return new class extends Migration {
    public function up(): void { (require __DIR__.'/2026_11_03_000002_create_triggers_y_procedimientos.php')->up(); }
    public function down(): void { throw new RuntimeException('No degradar controles de auditoría automáticamente.'); }
};
