<#
.SYNOPSIS
    Script de arranque local del servidor web - SIG Electrocentro.
.DESCRIPTION
    1. Verifica que MariaDB 10.11 este activo en el puerto 3307 (lo inicia si es necesario).
    2. Inicia el servidor de desarrollo Laravel en http://localhost:8000.
.PARAMETER Port
    Puerto HTTP para la aplicacion (por defecto 8000).
#>
param(
    [int]$Port = 8000
)

$ErrorActionPreference = "Stop"

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir
$SistemaDir = Join-Path $ProjectRoot "sistema"

# Verificar MariaDB
$portOpen = Test-NetConnection 127.0.0.1 -Port 3307 -InformationLevel Quiet -WarningAction SilentlyContinue
if (-not $portOpen) {
    Write-Host "Iniciando MariaDB en 127.0.0.1:3307..." -ForegroundColor Yellow
    & (Join-Path $ScriptDir "iniciar_mariadb.ps1")
    Start-Sleep -Seconds 2
}

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host " INICIANDO SERVIDOR WEB - SIG ELECTROCENTRO              " -ForegroundColor Cyan
Write-Host " URL: http://localhost:$Port                              " -ForegroundColor Green
Write-Host " Salud: http://localhost:$Port/salud                     " -ForegroundColor Green
Write-Host " Presione Ctrl+C para detener el servidor                 " -ForegroundColor Gray
Write-Host "==========================================================" -ForegroundColor Cyan

Set-Location -LiteralPath $SistemaDir
php artisan serve --port=$Port
