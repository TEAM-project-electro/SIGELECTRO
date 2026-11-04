<#
.SYNOPSIS
    Script de restauracion para las bases de datos de SIG Electrocentro.
.DESCRIPTION
    Restaura los respaldos logicos creados por 'respaldo_db.ps1' hacia esquemas
    nuevos descartables (por defecto 'seguimiento_gd_restaurada' y 'dw_seguimiento_gd_restaurada')
    o hacia los esquemas operativos si se solicita expresamente.
    Configura codificacion utf8mb4, remapea referencias entre esquemas y establece
    los privilegios minimos granulares por tabla y columna sin secretos (A08).
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
    Obligatorio si se especifica DatabaseTarget='seguimiento_gd' o DwDatabaseTarget='dw_seguimiento_gd'.
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

# A08: Salvaguarda contra sobrescritura accidental de cualquier base operativa
if (($DatabaseTarget -eq "seguimiento_gd" -or $DwDatabaseTarget -eq "dw_seguimiento_gd") -and -not $ForzarSobrescritura) {
    Write-Error "PELIGRO: Ha indicado un esquema operativo ('seguimiento_gd' o 'dw_seguimiento_gd'). Para sobrescribirlo debe especificar el parámetro -ForzarSobrescritura."
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

# 3. Restaurar esquema Data Warehouse remapeando referencias cruzadas (A08)
Write-Host "Restaurando '$DwDatabaseTarget' desde $(Split-Path -Leaf $DwSqlFile)..." -NoNewline
$mysqlArgsDw = @(
    "-h", $HostName,
    "-P", $Port,
    "-u", $User,
    "-p$Password",
    "--default-character-set=utf8mb4",
    $DwDatabaseTarget
)

$dwSqlContent = Get-Content -LiteralPath $DwSqlFile -Raw -Encoding utf8
if ($DatabaseTarget -ne "seguimiento_gd") {
    $dwSqlContent = $dwSqlContent -replace "\bseguimiento_gd\b", $DatabaseTarget
}
$dwSqlContent | & $mysqlBin $mysqlArgsDw
if ($LASTEXITCODE -ne 0) {
    Write-Host " [FALLO]" -ForegroundColor Red
    Write-Error "Error restaurando $DwDatabaseTarget"
    exit 1
}
Write-Host " [OK]" -ForegroundColor Green

# 4. Asegurar privilegios minimos granulares por tabla y columna (A08)
Write-Host "Configurando privilegios tecnicos minimos sobre esquemas restaurados..."
$hosts = @("localhost", "127.0.0.1")
$grantsSql = ""

foreach ($h in $hosts) {
    # 4.1 sig_escritor: acceso de escritura en tablas de negocio, solo SELECT/INSERT en auditoría, sin UPDATE/DELETE
    $grantsSql += "GRANT SELECT, INSERT, UPDATE, DELETE ON $DatabaseTarget.proyecto TO 'sig_escritor'@'$h';`n"
    $grantsSql += "GRANT SELECT, INSERT, UPDATE, DELETE ON $DatabaseTarget.hito TO 'sig_escritor'@'$h';`n"
    $grantsSql += "GRANT SELECT, INSERT, UPDATE, DELETE ON $DatabaseTarget.avance_presupuestal TO 'sig_escritor'@'$h';`n"
    $grantsSql += "GRANT SELECT, UPDATE ON $DatabaseTarget.control_revision TO 'sig_escritor'@'$h';`n"
    $grantsSql += "GRANT SELECT, INSERT, UPDATE ON $DatabaseTarget.responsable TO 'sig_escritor'@'$h';`n"
    $grantsSql += "GRANT SELECT, INSERT, UPDATE ON $DatabaseTarget.objetivo_gd TO 'sig_escritor'@'$h';`n"
    $grantsSql += "GRANT SELECT, INSERT ON $DatabaseTarget.bitacora_cambio TO 'sig_escritor'@'$h';`n"
    $grantsSql += "GRANT SELECT, INSERT ON $DatabaseTarget.bitacora_motivo TO 'sig_escritor'@'$h';`n"

    # 4.2 sig_lector: lectura de tablas de negocio y auditoria; lectura de columnas en responsable SIN clave_hash
    $grantsSql += "GRANT SELECT ON $DatabaseTarget.proyecto TO 'sig_lector'@'$h';`n"
    $grantsSql += "GRANT SELECT ON $DatabaseTarget.hito TO 'sig_lector'@'$h';`n"
    $grantsSql += "GRANT SELECT ON $DatabaseTarget.avance_presupuestal TO 'sig_lector'@'$h';`n"
    $grantsSql += "GRANT SELECT ON $DatabaseTarget.objetivo_gd TO 'sig_lector'@'$h';`n"
    $grantsSql += "GRANT SELECT ON $DatabaseTarget.control_revision TO 'sig_lector'@'$h';`n"
    $grantsSql += "GRANT SELECT ON $DatabaseTarget.bitacora_cambio TO 'sig_lector'@'$h';`n"
    $grantsSql += "GRANT SELECT ON $DatabaseTarget.bitacora_motivo TO 'sig_lector'@'$h';`n"
    $grantsSql += "GRANT SELECT ON $DatabaseTarget.v_indicadores_proyecto TO 'sig_lector'@'$h';`n"
    $grantsSql += "GRANT SELECT (id_responsable, nombre, area, perfil, usuario, activo) ON $DatabaseTarget.responsable TO 'sig_lector'@'$h';`n"

    # 4.3 sig_auth: columnas de autenticacion estrictas
    $grantsSql += "GRANT SELECT (id_responsable, usuario, clave_hash, activo, perfil, nombre, area) ON $DatabaseTarget.responsable TO 'sig_auth'@'$h';`n"

    # 4.4 sig_etl: manipulacion de hechos y ejecucion de rutinas en DW
    $grantsSql += "GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE ON $DwDatabaseTarget.* TO 'sig_etl'@'$h';`n"

    # 4.5 bi_lector: solo lectura de vistas y hechos en DW
    $grantsSql += "GRANT SELECT ON $DwDatabaseTarget.* TO 'bi_lector'@'$h';`n"
}

$mysqlArgsGrants = @(
    "-h", $HostName,
    "-P", $Port,
    "-u", $User,
    "-p$Password",
    "-e", $grantsSql
)
& $mysqlBin $mysqlArgsGrants
if ($LASTEXITCODE -ne 0) {
    Write-Error "Error configurando privilegios tecnicos en MariaDB"
    exit 1
}

Write-Host "----------------------------------------------------------" -ForegroundColor Cyan
Write-Host "Restauracion completada con exito." -ForegroundColor Green
Write-Host "Esquemas activos y listos para verificacion:" -ForegroundColor Green
Write-Host " - Operativo: $DatabaseTarget" -ForegroundColor Gray
Write-Host " - DW:        $DwDatabaseTarget" -ForegroundColor Gray
Write-Host "==========================================================" -ForegroundColor Cyan
