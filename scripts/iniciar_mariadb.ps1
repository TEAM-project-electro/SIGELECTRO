# Iniciar MariaDB 10.11 en puerto 3307 sin servicio de Windows
$bin = 'C:\mariadb-10.11\bin\mariadbd.exe'
$ini = 'C:\mariadb-10.11\my.ini'

if (-not (Test-Path $bin)) {
    Write-Error "No se encontro el binario de MariaDB en $bin"
    exit 1
}

$conn = Test-NetConnection 127.0.0.1 -Port 3307 -InformationLevel Quiet -WarningAction SilentlyContinue
if ($conn) {
    Write-Host "MariaDB 10.11 ya se encuentra escuchando en el puerto 3307."
} else {
    Write-Host "Iniciando MariaDB 10.11 en puerto 3307..."
    Start-Process -FilePath $bin -ArgumentList "--defaults-file=$ini" -WindowStyle Hidden
    Start-Sleep -Seconds 2
    $conn = Test-NetConnection 127.0.0.1 -Port 3307 -InformationLevel Quiet -WarningAction SilentlyContinue
    if ($conn) {
        Write-Host "MariaDB 10.11 iniciado exitosamente en 127.0.0.1:3307."
    } else {
        Write-Warning "El proceso se inicio pero el puerto 3307 aun no responde. Verifica C:\mariadb-10.11\data\*.err"
    }
}
