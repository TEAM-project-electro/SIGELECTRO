# Configuración del Entorno y Comandos Comprobados

Fecha de verificación: 3 de noviembre de 2026.
Referencia canónica: [PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md](../PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md).

## 1. Directorios de Trabajo

- **Raíz del proyecto:** `E:\8VO CICLO\SISTEMA DE INFORMACIÓN GERENCIAL\proyecto team`
  - Contiene: `PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md`, `AGENTS.md`, `docs/`, `.git/`.
- **Raíz de la aplicación web:** `E:\8VO CICLO\SISTEMA DE INFORMACIÓN GERENCIAL\proyecto team\sistema`
  - Destino para: `composer.json`, `artisan`, `package.json`, tests, controladores y modelos.

## 2. Herramientas y Binarios Detectados

| Herramienta | Ruta comprobada | Versión detectada | Estado |
| --- | --- | --- | --- |
| PowerShell | `pwsh` | 7.4.5 (Core) | Operativo |
| PHP CLI | `C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe` | PHP 8.3.28 | Operativo (extensiones OK) |
| Composer | `C:\composer\composer.bat` | 2.9.2 | Operativo |
| Node.js | `C:\Program Files\nodejs\node.exe` | v24.19.0 | Operativo |
| npm | `C:\Program Files\nodejs\npm.cmd` | 11.17.0 | Operativo |
| Git | `C:\Program Files\Git\cmd\git.exe` | 2.52.0.windows.1 | Operativo |
| MariaDB 10.11 (Objetivo) | `C:\mariadb-10.11\bin\mariadbd.exe` (a preparar en Etapa 02) | 10.11.x (puerto 3307) | Pendiente Etapa 02 |

## 3. Comandos Comprobados por Tarea

### En la Raíz (`proyecto team`)
- **Estado del repositorio:**
  ```powershell
  Set-Location -LiteralPath 'E:\8VO CICLO\SISTEMA DE INFORMACIÓN GERENCIAL\proyecto team'
  git status
  ```
- **Verificación de archivos de fuentes:**
  ```powershell
  Get-FileHash docs\fuentes\* -Algorithm SHA256
  ```

### En el Destino de la Aplicación (`sistema/`, a partir de Etapa 02)
- **Instalación de Laravel 11:**
  ```powershell
  Set-Location -LiteralPath 'E:\8VO CICLO\SISTEMA DE INFORMACIÓN GERENCIAL\proyecto team'
  composer create-project laravel/laravel:^11.0 sistema --prefer-dist
  ```
- **Ejecución de migraciones y tests:**
  ```powershell
  Set-Location -LiteralPath 'E:\8VO CICLO\SISTEMA DE INFORMACIÓN GERENCIAL\proyecto team\sistema'
  php artisan test
  ```
- **Compilación de assets:**
  ```powershell
  Set-Location -LiteralPath 'E:\8VO CICLO\SISTEMA DE INFORMACIÓN GERENCIAL\proyecto team\sistema'
  npm.cmd run build
  ```
- **Servidor local de desarrollo:**
  ```powershell
  php artisan serve --port=8000
  ```
