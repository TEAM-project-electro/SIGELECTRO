<?php
// Comprobación de auditoría de solo lectura; no imprime contraseñas ni hashes.
require __DIR__.'/../sistema/vendor/autoload.php';
$app = require __DIR__.'/../sistema/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach (['mysql', 'auth', 'lector', 'escritor', 'dw_lector'] as $name) {
    try {
        $db = Illuminate\Support\Facades\DB::connection($name);
        $identity = $db->selectOne('SELECT CURRENT_USER() cuenta, DATABASE() esquema, VERSION() version');
        echo json_encode(['conexion'=>$name, 'identidad'=>$identity], JSON_UNESCAPED_UNICODE).PHP_EOL;
        $grants = $db->select('SHOW GRANTS');
        foreach ($grants as $row) {
            foreach ((array)$row as $grant) {
                $grant = preg_replace('/ IDENTIFIED .*/i', ' [autenticación omitida]', $grant);
                echo $grant.PHP_EOL;
            }
        }
        if ($name === 'mysql') {
            foreach ([
                'SELECT COUNT(*) proyectos, SUM(monto) monto FROM proyecto',
                'SELECT TRIGGER_NAME, DEFINER FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE()',
            ] as $sql) {
                echo json_encode($db->select($sql), JSON_UNESCAPED_UNICODE).PHP_EOL;
            }
        }
    } catch (Throwable $e) {
        echo json_encode(['conexion'=>$name, 'error_tipo'=>get_class($e), 'codigo'=>$e->getCode()], JSON_UNESCAPED_UNICODE).PHP_EOL;
    }
}
