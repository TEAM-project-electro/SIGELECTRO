<#
.SYNOPSIS
    Script de respaldo para las bases de datos de SIG Electrocentro.
.DESCRIPTION
    Realiza un volcado lógico completo (tablas, datos, vistas, triggers y rutinas)
    de 'seguimiento_gd' y 'dw_seguimiento_gd' utilizando mariadb-dump.
    Los respaldos se almacenan en el directorio 'backups/' y se excluyen del repositorio Git.
.PARAMETER HostName
    Dirección del servidor MariaDB (por defecto 127.0.0.1).
.PARAMETER Port
    Puerto del servidor MariaDB (por defecto 3307).
.PARAMETER User
    Usuario con permisos de lectura y volcado de rutinas (por defecto sig_migrador).
.PARAMETER Password
    Contraseña del usuario técnico. Si se omite, se lee de variable de entorno o .env.
.PARAMETER BackupDir
    Directorio de destino para los archivos .sql (por defecto 'backups').
.PARAMETER MariaDbBinDir
    Ruta a la carpeta bin de MariaDB 10.11 (por defecto 'C:\mariadb-10.11\bin').
#>
param(
    [string]$HostName = "127.0.0.1",
    [int]$Port = 3307,
    [string]$User = "sig_migrador",
    [string]$Password = "",
    [string]$BackupDir = "backups",
    [string]$MariaDbBinDir = "C:\mariadb-10.11\bin"
)

$ErrorActionPreference = "Stop"

# Resolver raíz del proyecto (un nivel arriba del script)
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

# Verificar binario mariadb-dump
$dumpBin = Join-Path $MariaDbBinDir "mariadb-dump.exe"
if (-not (Test-Path $dumpBin)) {
    $dumpBin = Join-Path $MariaDbBinDir "mysqldump.exe"
    if (-not (Test-Path $dumpBin)) {
        Write-Error "No se encontro mariadb-dump.exe ni mysqldump.exe en $MariaDbBinDir"
        exit 1
    }
}

# Asegurar existencia del directorio de respaldo
$targetDir = Join-Path $ProjectRoot $BackupDir
if (-not (Test-Path $targetDir)) {
    New-Item -ItemType Directory -Path $targetDir -Force | Out-Null
}

$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"

$databases = @("seguimiento_gd", "dw_seguimiento_gd")

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host " INICIANDO RESPALDO DE BASE DE DATOS - SIG ELECTROCENTRO " -ForegroundColor Cyan
Write-Host " Servidor: $HostName`:$Port | Fecha: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

$archivosGenerados = @()

foreach ($db in $databases) {
    $backupFile = Join-Path $targetDir "${db}_${timestamp}.sql"
    $latestFile = Join-Path $targetDir "${db}_latest.sql"

    Write-Host "Respaldando base de datos '$db'..." -NoNewline

    $dumpArgs = @(
        "-h", $HostName,
        "-P", $Port,
        "-u", $User,
        "-p$Password",
        "--routines",
        "--triggers",
        "--events",
        "--single-transaction",
        "--quick",
        "--default-character-set=utf8mb4",
        $db
    )

    try {
        & $dumpBin $dumpArgs | Set-Content -Path $backupFile -Encoding utf8
        if (Test-Path $backupFile) {
            $fileInfo = Get-Item $backupFile
            if ($fileInfo.Length -gt 0) {
                # Copia a latest para pruebas automatizadas
                Copy-Item -Path $backupFile -Destination $latestFile -Force
                Write-Host " [OK] ($([math]::Round($fileInfo.Length / 1KB, 2)) KB)" -ForegroundColor Green
                $archivosGenerados += $backupFile
            } else {
                Write-Host " [ERROR: Archivo vacio]" -ForegroundColor Red
                exit 1
            }
        }
    } catch {
        Write-Host " [FALLO]" -ForegroundColor Red
        Write-Error $_.Exception.Message
        exit 1
    }
}

Write-Host "----------------------------------------------------------" -ForegroundColor Cyan
Write-Host "Respaldos completados exitosamente en: $targetDir" -ForegroundColor Green
Write-Host "Archivos generados:" -ForegroundColor Green
foreach ($f in $archivosGenerados) {
    Write-Host " - $(Split-Path -Leaf $f)" -ForegroundColor Gray
}
Write-Host "Archivos 'latest' actualizados para verificacion automatica." -ForegroundColor Gray
Write-Host "==========================================================" -ForegroundColor Cyan
