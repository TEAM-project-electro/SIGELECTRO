# Configuración del Entorno y Comandos Comprobados

Fecha de verificación: 3 de noviembre de 2026.
Actualización comprobada 04/11/2026: Laravel 12.71.0, PHP 8.3.28, PHPUnit 11.5.57 y MariaDB 10.11.11 en puerto 3307. Suite: 133 pruebas y 1,441 aserciones; compilación Vite aprobada. Véase el informe de corrección vigente.
Referencia canónica: [PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md](../PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md).

---

## 1. Directorios de Trabajo y Rutas de Ejecución

- **Raíz del proyecto:** `E:\8VO CICLO\SISTEMA DE INFORMACIÓN GERENCIAL\proyecto team`
  - Contiene: `PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md`, `AGENTS.md`, `docs/`, `scripts/`, `.git/`.
- **Raíz de la aplicación Laravel:** `E:\8VO CICLO\SISTEMA DE INFORMACIÓN GERENCIAL\proyecto team\sistema`
  - Todos los comandos de Composer, PHP Artisan, PHPUnit y npm se ejecutan **dentro de `sistema/`**.

---

## 2. Servidor MariaDB 10.11 Aislado (Puerto 3307)

Para no entrar en conflicto con instalaciones existentes en el equipo (XAMPP MariaDB 10.4 en puerto 3306, MySQL 8.4 en Laragon, MySQL 8.0/9.7), se preparó el servidor portátil oficial:
- **Ubicación:** `C:\mariadb-10.11`
- **Configuración:** `C:\mariadb-10.11\my.ini`
- **Puerto:** `3307`
- **Host:** `127.0.0.1`
- **Esquemas creados:** `seguimiento_gd`, `dw_seguimiento_gd`, `seguimiento_gd_test`, `dw_seguimiento_gd_test`.
- **Cuentas técnicas (Decisión 5):** `sig_migrador`, `sig_definer`, `sig_auth`, `sig_lector`, `sig_escritor`, `sig_etl`, `bi_lector`.

### Scripts de Gestión y Operación
Desde la raíz del proyecto:
- **Iniciar servidor MariaDB:**
  ```powershell
  pwsh -File .\scripts\iniciar_mariadb.ps1
  ```
- **Detener servidor MariaDB:**
  ```powershell
  pwsh -File .\scripts\detener_mariadb.ps1
  ```
- **Respaldo lógico completo (esquema operativo y DW en `backups/`):**
  ```powershell
  pwsh -File .\scripts\respaldo_db.ps1
  ```
- **Restauración y conciliación a esquemas de verificación:**
  ```powershell
  pwsh -File .\scripts\restaurar_db.ps1
  ```
- **Instalación y despliegue limpio (prerrequisitos, dependencias, siembra, compilación, DW y tests):**
  ```powershell
  pwsh -File .\scripts\instalar.ps1
  ```
- **Arranque del servidor web local (`http://localhost:8000`):**
  ```powershell
  pwsh -File .\scripts\arrancar.ps1
  ```
- **Ejecución de la suite completa de pruebas (133 tests):**
  ```powershell
  pwsh -File .\scripts\probar.ps1
  ```

---

## 3. Comandos Comprobados de Desarrollo

### 3.1 Comandos de Base de Datos y Verificación
Desde `sistema/`:
```powershell
# Inspección de conexión activa
php artisan db:show

# Monitor de tablas y estado
php artisan db:table
```

### 3.2 Comandos de Frontend (Vite, Tailwind, Alpine, Chart.js)
Desde `sistema/`:
```powershell
# Compilación para producción (activos locales sin CDN)
npm.cmd run build

# Servidor de desarrollo con recarga en vivo
npm.cmd run dev
```

### 3.3 Comandos de Pruebas Automatizadas
Desde `sistema/`:
```powershell
# Ejecución de la prueba de humo y salud
php artisan test --filter=SmokeTest

# Ejecución completa de la suite de pruebas
php artisan test
```

### 3.4 Servidor Web de Desarrollo
Desde `sistema/`:
```powershell
php artisan serve --port=8000
```
La aplicación queda accesible en: `http://localhost:8000`
Ruta de salud del sistema: `http://localhost:8000/salud`
