<#
.SYNOPSIS
    Script de instalacion y preparacion en limpio - SIG Electrocentro.
.DESCRIPTION
    Realiza la preparacion del sistema en un entorno Windows:
    1. Verifica prerrequisitos (PHP 8.2+, Composer, Node.js, MariaDB 10.11 en puerto 3307).
    2. Configura el archivo .env a partir de .env.example (sin sobrescribir si ya existe).
    3. Instala dependencias con Composer y NPM usando archivos de bloqueo.
    4. Genera APP_KEY si no esta definida.
    5. Ejecuta migraciones operativas y de Data Warehouse con siembra de datos canonica.
    6. Compila los activos de frontend (Tailwind, Alpine, Chart.js) para produccion.
    7. Ejecuta la carga inicial del Data Warehouse (ETL).
    8. Ejecuta la suite de pruebas automatizadas para certificar la instalacion.
#>

$ErrorActionPreference = "Stop"

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$ProjectRoot = Split-Path -Parent $ScriptDir
$SistemaDir = Join-Path $ProjectRoot "sistema"

Write-Host "==========================================================" -ForegroundColor Cyan
Write-Host " INSTALACION Y DESPLIEGUE LOCAL - SIG ELECTROCENTRO      " -ForegroundColor Cyan
Write-Host " Fecha: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')        " -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Cyan

# 1. Comprobacion de binarios
Write-Host "1. Verificando prerrequisitos del entorno..." -ForegroundColor Yellow

$phpBin = Get-Command php -ErrorAction SilentlyContinue
if (-not $phpBin) {
    Write-Error "No se encontro PHP en el PATH del sistema."
    exit 1
}
$phpVer = php -r "echo PHP_VERSION;"
Write-Host " - PHP: $phpVer ($($phpBin.Source))" -ForegroundColor Gray

$composerBin = Get-Command composer -ErrorAction SilentlyContinue
if (-not $composerBin) {
    Write-Error "No se encontro Composer en el PATH del sistema."
    exit 1
}
Write-Host " - Composer detectado" -ForegroundColor Gray

$npmBin = Get-Command npm.cmd -ErrorAction SilentlyContinue
if (-not $npmBin) {
    $npmBin = Get-Command npm -ErrorAction SilentlyContinue
}
if (-not $npmBin) {
    Write-Error "No se encontro Node.js / npm en el PATH del sistema."
    exit 1
}
Write-Host " - NPM detectado" -ForegroundColor Gray

# Verificar MariaDB 10.11 en puerto 3307
$portOpen = Test-NetConnection 127.0.0.1 -Port 3307 -InformationLevel Quiet -WarningAction SilentlyContinue
if (-not $portOpen) {
    Write-Host " MariaDB no responde en puerto 3307. Intentando iniciar..." -ForegroundColor Yellow
    & (Join-Path $ScriptDir "iniciar_mariadb.ps1")
    Start-Sleep -Seconds 3
    $portOpen = Test-NetConnection 127.0.0.1 -Port 3307 -InformationLevel Quiet -WarningAction SilentlyContinue
    if (-not $portOpen) {
        Write-Error "No se pudo conectar a MariaDB en 127.0.0.1:3307. Inicie el servidor antes de continuar."
        exit 1
    }
}
Write-Host " - MariaDB activo en 127.0.0.1:3307" -ForegroundColor Green

# 2. Archivo .env
Write-Host "2. Configurando entorno privado .env..." -ForegroundColor Yellow
Set-Location -LiteralPath $SistemaDir

if (-not (Test-Path ".env")) {
    if (Test-Path ".env.example") {
        Copy-Item -Path ".env.example" -Destination ".env"
        Write-Host " - Archivo .env creado desde .env.example" -ForegroundColor Green
    } else {
        Write-Error "No se encontro archivo .env.example en $SistemaDir"
        exit 1
    }
} else {
    Write-Host " - Archivo .env existente conservado (sin sobrescribir secretos)" -ForegroundColor Gray
}

# 3. Dependencias PHP y JS
Write-Host "3. Instalando dependencias..." -ForegroundColor Yellow
Write-Host " - Instalando paquetes de Composer..."
& composer install --no-interaction --prefer-dist --optimize-autoloader
if ($LASTEXITCODE -ne 0) {
    Write-Error "Fallo la instalacion de dependencias de Composer."
    exit 1
}

Write-Host " - Instalando paquetes de NPM..."
& npm.cmd install --no-audit --no-fund
if ($LASTEXITCODE -ne 0) {
    Write-Error "Fallo la instalacion de dependencias de NPM."
    exit 1
}

# 4. APP_KEY
$envContent = Get-Content ".env" -Raw
if ($envContent -notmatch "APP_KEY=base64:") {
    Write-Host "4. Generando clave de aplicacion (APP_KEY)..." -ForegroundColor Yellow
    & php artisan key:generate
} else {
    Write-Host "4. APP_KEY ya se encuentra configurada." -ForegroundColor Gray
}

# 5. Migraciones y Seeders
Write-Host "5. Ejecutando migraciones y siembra canonica..." -ForegroundColor Yellow
& php artisan migrate --seed --force
if ($LASTEXITCODE -ne 0) {
    Write-Error "Fallo la ejecucion de migraciones o siembra."
    exit 1
}

# 6. Compilacion de Activos Vite
Write-Host "6. Compilando activos frontend con Vite..." -ForegroundColor Yellow
& npm.cmd run build
if ($LASTEXITCODE -ne 0) {
    Write-Error "Fallo la compilacion de activos frontend."
    exit 1
}

# 7. Carga de Data Warehouse
Write-Host "7. Ejecutando carga inicial de Data Warehouse (ETL)..." -ForegroundColor Yellow
& php artisan etl:cargar
if ($LASTEXITCODE -ne 0) {
    Write-Error "Fallo la ejecucion inicial de sp_etl_cargar."
    exit 1
}

# 8. Certificacion mediante suite de pruebas
Write-Host "8. Ejecutando suite de pruebas automatizadas..." -ForegroundColor Yellow
& php artisan test
if ($LASTEXITCODE -ne 0) {
    Write-Error "Una o mas pruebas fallaron durante la validacion final."
    exit 1
}

Write-Host "==========================================================" -ForegroundColor Green
Write-Host " INSTALACION COMPLETADA CON EXITO (GATE G4 CERTIFICADA)   " -ForegroundColor Green
Write-Host " Para iniciar el servidor ejecute:                       " -ForegroundColor Green
Write-Host "   pwsh -File .\scripts\arrancar.ps1                      " -ForegroundColor Cyan
Write-Host "==========================================================" -ForegroundColor Green
