<#
.SYNOPSIS
    Script de ejecucion de pruebas automatizadas - SIG Electrocentro.
.DESCRIPTION
    Ejecuta la suite completa de pruebas de Laravel (119 pruebas, 769 aserciones)
    certificando todas las compuertas Gate G1, Gate G2, Gate G3 y Gate G4.
.PARAMETER Filter
    Filtro opcional para ejecutar una suite o metodo especifico (ej: IndicadoresMotorTest).
#>
param(
    [string]$Filter = ""
)

$ErrorActionPreference = "Stop"

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir
$SistemaDir = Join-Path $ProjectRoot "sistema"

Set-Location -LiteralPath $SistemaDir

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host " EJECUTANDO SUITE DE PRUEBAS - SIG ELECTROCENTRO         " -ForegroundColor Cyan
Write-Host " Fecha: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')        " -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

if ($Filter) {
    php artisan test --filter=$Filter
} else {
    php artisan test
}
exit $LASTEXITCODE
