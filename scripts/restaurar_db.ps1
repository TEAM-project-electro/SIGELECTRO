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
foreach ($schema in @($DatabaseTarget, $DwDatabaseTarget)) {
    if ($schema -notmatch '^[a-z][a-z0-9_]*$') { throw 'Nombre de esquema inválido.' }
}
if ($DatabaseTarget -eq $DwDatabaseTarget) { throw 'Los dos esquemas deben ser distintos.' }

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
    throw "Configure DB_PASSWORD en el entorno privado o indique -Password."
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

# 4. Reconciliar permisos, retirando también los grants amplios heredados del destino.
$prevAdminUser = $env:SIG_ADMIN_USER
$prevAdminPassword = $env:SIG_ADMIN_PASSWORD
try {
    $env:SIG_ADMIN_USER = $User
    $env:SIG_ADMIN_PASSWORD = $Password
    & php (Join-Path $ScriptDir 'ajustar_permisos.php') $DatabaseTarget $DwDatabaseTarget $HostName $Port
    if ($LASTEXITCODE -ne 0) { throw 'Falló la reconciliación de privilegios de restauración.' }
} finally {
    $env:SIG_ADMIN_USER = $prevAdminUser
    $env:SIG_ADMIN_PASSWORD = $prevAdminPassword
}
Write-Host "----------------------------------------------------------" -ForegroundColor Cyan
Write-Host "Restauracion completada con exito." -ForegroundColor Green
Write-Host "Esquemas activos y listos para verificacion:" -ForegroundColor Green
Write-Host " - Operativo: $DatabaseTarget" -ForegroundColor Gray
Write-Host " - DW:        $DwDatabaseTarget" -ForegroundColor Gray
Write-Host "==========================================================" -ForegroundColor Cyan
