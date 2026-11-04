<#
.SYNOPSIS
    Script de restauracion para las bases de datos de SIG Electrocentro.
.DESCRIPTION
    Restaura los respaldos logicos creados por 'respaldo_db.ps1' hacia esquemas
    nuevos descartables (por defecto 'seguimiento_gd_restaurada' y 'dw_seguimiento_gd_restaurada')
    o hacia los esquemas operativos si se solicita expresamente.
    Configura codificacion utf8mb4 y grants tecnicos necesarios para su verificacion.
.PARAMETER HostName
    Dirección del servidor MariaDB (por defecto 127.0.0.1).
.PARAMETER Port
    Puerto del servidor MariaDB (por defecto 3307).
.PARAMETER User
    Usuario con permisos administrativos sobre MariaDB (por defecto sig_migrador).
.PARAMETER Password
    Contraseña técnica. Si se omite, se lee de variable de entorno o .env.
.PARAMETER DatabaseTarget
    Nombre del esquema para el núcleo operativo (por defecto 'seguimiento_gd_restaurada').
.PARAMETER DwDatabaseTarget
    Nombre del esquema para el Data Warehouse (por defecto 'dw_seguimiento_gd_restaurada').
.PARAMETER SeguimientoSqlFile
    Ruta o nombre del archivo .sql para el esquema operativo. Si se omite, usa 'seguimiento_gd_latest.sql'.
.PARAMETER DwSqlFile
    Ruta o nombre del archivo .sql para el esquema DW. Si se omite, usa 'dw_seguimiento_gd_latest.sql'.
.PARAMETER ForzarSobrescritura
    Obligatorio si se especifica DatabaseTarget='seguimiento_gd' para prevenir sobrescritura accidental.
.PARAMETER MariaDbBinDir
    Ruta a la carpeta bin de MariaDB 10.11 (por defecto 'C:\mariadb-10.11\bin').
#>
param(
    [string]$HostName = "127.0.0.1",
    [int]$Port = 3307,
    [string]$User = "sig_migrador",
    [string]$Password = "",
    [string]$DatabaseTarget = "seguimiento_gd_restaurada",
    [string]$DwDatabaseTarget = "dw_seguimiento_gd_restaurada",
    [string]$SeguimientoSqlFile = "",
    [string]$DwSqlFile = "",
    [switch]$ForzarSobrescritura,
    [string]$BackupDir = "backups",
    [string]$MariaDbBinDir = "C:\mariadb-10.11\bin"
)

$ErrorActionPreference = "Stop"

# Resolver raíz del proyecto
$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir
Set-Location -LiteralPath $ProjectRoot

# Si no se pasó contraseña, intentar leerla del archivo .env privado
if (-not $Password) {
    $envPath = Join-Path $ProjectRoot "sistema\.env"
    if (Test-Path $envPath) {
        $passLine = Get-Content $envPath | Where-Object { $_ -match "^DB_PASSWORD=(.*)$" } | Select-Object -First 1
        if ($passLine -match "^DB_PASSWORD=(.*)$") {
            $Password = $Matches[1].Trim()
        }
    }
}

if (-not $Password) {
    $Password = "Migrador2026!"
}

# Salvaguarda contra sobrescritura accidental de base operativa
if ($DatabaseTarget -eq "seguimiento_gd" -and -not $ForzarSobrescritura) {
    Write-Error "PELIGRO: Ha indicado la base operativa 'seguimiento_gd'. Para sobrescribirla debe especificar el parametro -ForzarSobrescritura."
    exit 1
}

# Binario de cliente MariaDB
$mysqlBin = Join-Path $MariaDbBinDir "mariadb.exe"
if (-not (Test-Path $mysqlBin)) {
    $mysqlBin = Join-Path $MariaDbBinDir "mysql.exe"
    if (-not (Test-Path $mysqlBin)) {
        Write-Error "No se encontro mariadb.exe ni mysql.exe en $MariaDbBinDir"
        exit 1
    }
}

# Resolver archivos de respaldo
$targetDir = Join-Path $ProjectRoot $BackupDir
if (-not $SeguimientoSqlFile) {
    $SeguimientoSqlFile = Join-Path $targetDir "seguimiento_gd_latest.sql"
} elseif (-not (Test-Path $SeguimientoSqlFile)) {
    $SeguimientoSqlFile = Join-Path $targetDir $SeguimientoSqlFile
}

if (-not $DwSqlFile) {
    $DwSqlFile = Join-Path $targetDir "dw_seguimiento_gd_latest.sql"
} elseif (-not (Test-Path $DwSqlFile)) {
    $DwSqlFile = Join-Path $targetDir $DwSqlFile
}

if (-not (Test-Path $SeguimientoSqlFile)) {
    Write-Error "No se encontro el archivo de respaldo: $SeguimientoSqlFile"
    exit 1
}
if (-not (Test-Path $DwSqlFile)) {
    Write-Error "No se encontro el archivo de respaldo DW: $DwSqlFile"
    exit 1
}

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host " INICIANDO RESTAURACION DE BASE DE DATOS - SIG ELECTROCENTRO " -ForegroundColor Cyan
Write-Host " Servidor: $HostName`:$Port | Fecha: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Cyan
Write-Host " Destinos: '$DatabaseTarget' y '$DwDatabaseTarget'" -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

# 1. Crear esquemas de destino limpios
Write-Host "Preparando esquemas de destino..."
$initSql = "CREATE DATABASE IF NOT EXISTS $DatabaseTarget CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE IF NOT EXISTS $DwDatabaseTarget CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

$mysqlArgsInit = @(
    "-h", $HostName,
    "-P", $Port,
    "-u", $User,
    "-p$Password",
    "-e", $initSql
)
& $mysqlBin $mysqlArgsInit
if ($LASTEXITCODE -ne 0) {
    Write-Error "Error al preparar esquemas de destino en MariaDB"
    exit 1
}

# 2. Restaurar esquema operativo
Write-Host "Restaurando '$DatabaseTarget' desde $(Split-Path -Leaf $SeguimientoSqlFile)..." -NoNewline
$mysqlArgsOp = @(
    "-h", $HostName,
    "-P", $Port,
    "-u", $User,
    "-p$Password",
    "--default-character-set=utf8mb4",
    $DatabaseTarget
)

Get-Content -LiteralPath $SeguimientoSqlFile -Raw -Encoding utf8 | & $mysqlBin $mysqlArgsOp
if ($LASTEXITCODE -ne 0) {
    Write-Host " [FALLO]" -ForegroundColor Red
    Write-Error "Error restaurando $DatabaseTarget"
    exit 1
}
Write-Host " [OK]" -ForegroundColor Green

# 3. Restaurar esquema Data Warehouse
Write-Host "Restaurando '$DwDatabaseTarget' desde $(Split-Path -Leaf $DwSqlFile)..." -NoNewline
$mysqlArgsDw = @(
    "-h", $HostName,
    "-P", $Port,
    "-u", $User,
    "-p$Password",
    "--default-character-set=utf8mb4",
    $DwDatabaseTarget
)

Get-Content -LiteralPath $DwSqlFile -Raw -Encoding utf8 | & $mysqlBin $mysqlArgsDw
if ($LASTEXITCODE -ne 0) {
    Write-Host " [FALLO]" -ForegroundColor Red
    Write-Error "Error restaurando $DwDatabaseTarget"
    exit 1
}
Write-Host " [OK]" -ForegroundColor Green

# 4. Asegurar privilegios de las cuentas técnicas sobre los esquemas restaurados
Write-Host "Configurando privilegios tecnicos sobre esquemas restaurados..."
$grantsSql = @"
GRANT ALL PRIVILEGES ON $DatabaseTarget.* TO 'sig_migrador'@'localhost';
GRANT ALL PRIVILEGES ON $DwDatabaseTarget.* TO 'sig_migrador'@'localhost';
GRANT ALL PRIVILEGES ON $DatabaseTarget.* TO 'sig_migrador'@'127.0.0.1';
GRANT ALL PRIVILEGES ON $DwDatabaseTarget.* TO 'sig_migrador'@'127.0.0.1';
GRANT ALL PRIVILEGES ON $DatabaseTarget.* TO 'sig_definer'@'localhost';
GRANT ALL PRIVILEGES ON $DwDatabaseTarget.* TO 'sig_definer'@'localhost';
GRANT ALL PRIVILEGES ON $DatabaseTarget.* TO 'sig_definer'@'127.0.0.1';
GRANT ALL PRIVILEGES ON $DwDatabaseTarget.* TO 'sig_definer'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE, DELETE ON $DatabaseTarget.* TO 'sig_escritor'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON $DatabaseTarget.* TO 'sig_escritor'@'127.0.0.1';
REVOKE UPDATE, DELETE ON $DatabaseTarget.bitacora_cambio FROM 'sig_escritor'@'localhost';
REVOKE UPDATE, DELETE ON $DatabaseTarget.bitacora_cambio FROM 'sig_escritor'@'127.0.0.1';
GRANT SELECT ON $DatabaseTarget.* TO 'sig_lector'@'localhost';
GRANT SELECT ON $DatabaseTarget.* TO 'sig_lector'@'127.0.0.1';
GRANT SELECT ON $DatabaseTarget.users TO 'sig_auth'@'localhost';
GRANT SELECT ON $DatabaseTarget.users TO 'sig_auth'@'127.0.0.1';
GRANT SELECT ON $DatabaseTarget.password_reset_tokens TO 'sig_auth'@'localhost';
GRANT SELECT ON $DatabaseTarget.password_reset_tokens TO 'sig_auth'@'127.0.0.1';
GRANT SELECT ON $DatabaseTarget.sessions TO 'sig_auth'@'localhost';
GRANT SELECT ON $DatabaseTarget.sessions TO 'sig_auth'@'127.0.0.1';
GRANT SELECT, INSERT, UPDATE, DELETE ON $DwDatabaseTarget.* TO 'sig_etl'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON $DwDatabaseTarget.* TO 'sig_etl'@'127.0.0.1';
GRANT EXECUTE ON $DwDatabaseTarget.* TO 'sig_etl'@'localhost';
GRANT EXECUTE ON $DwDatabaseTarget.* TO 'sig_etl'@'127.0.0.1';
GRANT SELECT ON $DwDatabaseTarget.* TO 'bi_lector'@'localhost';
GRANT SELECT ON $DwDatabaseTarget.* TO 'bi_lector'@'127.0.0.1';
FLUSH PRIVILEGES;
"@

$mysqlArgsGrants = @(
    "-h", $HostName,
    "-P", $Port,
    "-u", $User,
    "-p$Password",
    "-e", $grantsSql
)
& $mysqlBin $mysqlArgsGrants 2>$null

Write-Host "----------------------------------------------------------" -ForegroundColor Cyan
Write-Host "Restauracion completada con exito." -ForegroundColor Green
Write-Host "Esquemas activos y listos para verificacion:" -ForegroundColor Green
Write-Host " - Operativo: $DatabaseTarget" -ForegroundColor Gray
Write-Host " - DW:        $DwDatabaseTarget" -ForegroundColor Gray
Write-Host "==========================================================" -ForegroundColor Cyan
