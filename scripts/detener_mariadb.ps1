# Detener MariaDB 10.11 del puerto 3307
$admin = 'C:\mariadb-10.11\bin\mariadb-admin.exe'

if (Test-Path $admin) {
    Write-Host "Enviando senal de apagado a MariaDB 10.11 en puerto 3307..."
    & $admin -h 127.0.0.1 -P 3307 -u root shutdown 2>$null
    Start-Sleep -Seconds 1
}

$proc = Get-Process -Name 'mariadbd' -ErrorAction SilentlyContinue | Where-Object {
    $_.Path -like 'C:\mariadb-10.11*'
}

if ($proc) {
    Stop-Process -Id $proc.Id -Force
    Write-Host "Proceso MariaDB 10.11 terminado."
} else {
    Write-Host "MariaDB 10.11 ya no esta en ejecucion."
}
