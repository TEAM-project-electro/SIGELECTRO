<?php
// Administración local, nunca invocada por HTTP. Requiere una cuenta con GRANT OPTION.
require __DIR__.'/../sistema/vendor/autoload.php';
$app = require __DIR__.'/../sistema/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
try {
$cfg = config('database.connections.mysql');
$cfg['host'] = $argv[3] ?? $cfg['host'];
$cfg['port'] = $argv[4] ?? $cfg['port'];
if (!preg_match('/\A[a-zA-Z0-9.:-]+\z/D', $cfg['host']) || !ctype_digit((string) $cfg['port']) || (int) $cfg['port'] < 1 || (int) $cfg['port'] > 65535) {
    throw new RuntimeException('Host o puerto inválido.');
}
$pdo = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};charset=utf8mb4", getenv('SIG_ADMIN_USER') ?: $cfg['username'], getenv('SIG_ADMIN_PASSWORD') ?: $cfg['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$op = $argv[1] ?? $cfg['database'];
$dw = $argv[2] ?? config('database.connections.dw_migrador.database');
foreach ([$op,$dw] as $schema) if (!preg_match('/\A[a-z][a-z0-9_]*\z/D', $schema)) throw new RuntimeException('Esquema inválido.');
$tablas = ['proyecto','hito','avance_presupuestal','objetivo_gd','responsable','control_revision','bitacora_cambio','bitacora_motivo'];
$stmtExt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = 'abastecimiento_compra'");
$stmtExt->execute([$op]);
$tieneAbastecimiento = ((int) $stmtExt->fetchColumn()) > 0;
$tablasConExtensiones = $tieneAbastecimiento ? array_merge($tablas, ['abastecimiento_compra']) : $tablas;
$stmtRag = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = 'rag_consulta'");
$stmtRag->execute([$op]);
$tieneRag = ((int) $stmtRag->fetchColumn()) > 0;
if ($tieneRag) $tablasConExtensiones = array_merge($tablasConExtensiones, ['rag_documento','rag_fragmento','rag_consulta']);
$stmtLb = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = 'hito_linea_base_version'");
$stmtLb->execute([$op]);
$tieneVersionesLb = ((int) $stmtLb->fetchColumn()) > 0;
if ($tieneVersionesLb) $tablasConExtensiones = array_merge($tablasConExtensiones, ['hito_linea_base_version']);
// Gestión gerencial (prioridad 2): tablas técnicas y privilegios de escritura de la aplicación.
$escrituraGestion = [
    'criterio_priorizacion' => 'INSERT,UPDATE', 'evaluacion_priorizacion' => 'INSERT,UPDATE', 'riesgo_proyecto' => 'INSERT,UPDATE',
    'hito_peso' => 'INSERT,UPDATE', 'compromiso_presupuestal' => 'INSERT,UPDATE', 'parametro_alerta' => 'INSERT,UPDATE',
    'documento_proyecto' => 'INSERT', 'beneficio_proyecto' => 'INSERT,UPDATE', 'proyecto_presupuesto_version' => null,
];
$stmtGestion = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = 'riesgo_proyecto'");
$stmtGestion->execute([$op]);
$tieneGestion = ((int) $stmtGestion->fetchColumn()) > 0;
if ($tieneGestion) $tablasConExtensiones = array_merge($tablasConExtensiones, array_keys($escrituraGestion));
foreach (['localhost','127.0.0.1'] as $host) {
    foreach (['sig_auth','sig_lector','sig_escritor','sig_etl','bi_lector','sig_definer'] as $cuenta) {
        $user = "'{$cuenta}'@'{$host}'";
        foreach ([$op,$dw] as $schema) {
            // Quitar grants previos del destino; nunca tocar otros esquemas ni permisos globales.
            try { $pdo->exec("REVOKE ALL PRIVILEGES ON `{$schema}`.* FROM {$user}"); }
            catch (PDOException $e) { if (!in_array(($e->errorInfo[1] ?? 0), [1141,1147], true)) throw $e; }
        }
        foreach ($tablasConExtensiones as $tabla) {
            try { $pdo->exec("REVOKE ALL PRIVILEGES ON `{$op}`.`{$tabla}` FROM {$user}"); }
            catch (PDOException $e) { if (!in_array(($e->errorInfo[1] ?? 0), [1141,1147], true)) throw $e; }
        }
    }
    $grant = function ($priv, $obj, $user) use ($pdo,$host) { $pdo->exec("GRANT {$priv} ON {$obj} TO '{$user}'@'{$host}'"); };
    $grant('SELECT (id_responsable,usuario,clave_hash,activo,perfil,nombre)', "`$op`.responsable", 'sig_auth');
    $safe = 'SELECT (id_responsable,usuario,activo,perfil,nombre,area)';
    foreach (['sig_lector','sig_escritor','sig_etl'] as $user) {
        foreach ($tablasConExtensiones as $tabla) {
            $grant($tabla==='responsable' ? $safe : 'SELECT', "`$op`.`$tabla`", $user);
        }
        $grant('SELECT', "`$op`.v_indicadores_proyecto", $user);
    }
    foreach (['proyecto','hito','avance_presupuestal'] as $tabla) $grant('INSERT,UPDATE,DELETE', "`$op`.`$tabla`", 'sig_escritor');
    foreach (['responsable','objetivo_gd'] as $tabla) $grant('INSERT,UPDATE', "`$op`.`$tabla`", 'sig_escritor');
    if ($tieneAbastecimiento) $grant('INSERT,UPDATE', "`$op`.abastecimiento_compra", 'sig_escritor');
    if ($tieneRag) $grant('INSERT', "`$op`.rag_consulta", 'sig_escritor');
    $grant('UPDATE', "`$op`.control_revision", 'sig_escritor');
    // Sólo los triggers/rutinas del definer escriben auditoría y gestionan el DW.
    foreach ($tablasConExtensiones as $tabla) $grant($tabla==='responsable' ? 'SELECT (id_responsable,usuario,activo,perfil,nombre,area,clave_hash)' : 'SELECT', "`$op`.`$tabla`", 'sig_definer');
    foreach (['bitacora_cambio','bitacora_motivo'] as $tabla) $grant('INSERT', "`$op`.`$tabla`", 'sig_definer');
    // Versiones de línea base: inmutables, solo las escriben los triggers del definer.
    if ($tieneVersionesLb) $grant('INSERT', "`$op`.hito_linea_base_version", 'sig_definer');
    if ($tieneGestion) {
        foreach ($escrituraGestion as $tabla => $privilegios) {
            if ($privilegios !== null) $grant($privilegios, "`$op`.`$tabla`", 'sig_escritor');
        }
        $grant('INSERT', "`$op`.proyecto_presupuesto_version", 'sig_definer');
        // Columnas que los triggers fijan con SET NEW (actor real y fecha de cierre).
        foreach (['evaluacion_priorizacion' => 'id_responsable', 'compromiso_presupuestal' => 'id_responsable',
            'documento_proyecto' => 'id_responsable', 'riesgo_proyecto' => 'fecha_cierre'] as $tabla => $columna) {
            $grant("UPDATE ({$columna})", "`$op`.`$tabla`", 'sig_definer');
        }
    }
    $grant('UPDATE', "`$op`.control_revision", 'sig_definer');
    $grant('EXECUTE', "`$op`.*", 'sig_definer');
    $grant('TRIGGER', "`$op`.*", 'sig_definer');
    $grant('SELECT,INSERT,UPDATE,DELETE,EXECUTE', "`$dw`.*", 'sig_definer');
    $grant('SELECT,EXECUTE', "`$dw`.*", 'sig_etl');
    $grant('SELECT,SHOW VIEW', "`$dw`.*", 'bi_lector');
}
echo "Permisos reconciliados en {$op} y {$dw}.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'No se pudieron reconciliar los permisos (código '.($e instanceof PDOException ? ($e->errorInfo[1] ?? $e->getCode()) : $e->getCode()).").\n");
    exit(1);
}


