# Plan de Ejecución del SIG de Electrocentro

> Documento vivo derivado del capítulo «Plan inicial» del contrato [PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md](../PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md) (Decisión 10).

## 1. Orden de Etapas y Puertas de Control (Gates)

| Etapa | Descripción del resultado | Dependencia | Control asociado | Estado |
| --- | --- | --- | --- | --- |
| 00 | Inventario de fuentes, derivados contractuales y diagnóstico de entorno | Documentos fuente | Pre-condición | En curso |
| 01 | Especificación, permisos, indicadores y matriz RF | 00 | Plan aprobado | Pendiente |
| 02 | Arquitectura, entorno MariaDB 10.11 aislado y app Laravel mínima | 01 | Entorno listo | Pendiente |
| 03 | Esquema operativo, transacciones, restricciones R1-R4 y auditoría | 02 | Base funcional | Pendiente |
| 04 | Muestra reproducible y cuentas de ensayo | 03 | Datos cargados | Pendiente |
| 05 | Autenticación y permisos por perfil (Policies) | 04 | **G1 (Acceso y Seguridad)** | Pendiente |
| 06 | Cartera y catálogos | G1 | Módulo Cartera | Pendiente |
| 07 | Hitos, línea base y cierre | 06 | Módulo Hitos | Pendiente |
| 08 | Cortes presupuestales | 07 | Módulo Finanzas | Pendiente |
| 09 | Auditoría completa, consistencia y `control_revision` | 08 | **G2 (Operación y Auditoría)** | Pendiente |
| 10 | Motor de indicadores y oráculo independiente | G2 | Motor KPI | Pendiente |
| 11 | Data Warehouse (dw_seguimiento_gd) y proceso ETL | 10 | Almacén y ETL | Pendiente |
| 12 | Tablero e interfaz gerencial | 11 | Frontend y Vistas | Pendiente |
| 13 | Exportación coherente y reportes | 12 | **G3 (KPIs, DW y Reportes)** | Pendiente |
| 14 | Preparación y estado de Power BI (docs/bi/) | G3 | Artefactos BI | Pendiente |
| 15 | Importación CSV (opcional) | Núcleo verificado | Complementario RF07 | Pendiente |
| 16 | Alertas de actualización (opcional) | Núcleo verificado | Complementario RF08 | Pendiente |
| 17 | Validación integral y batería de pruebas | 14 (+15, +16 si aplican) | Batería completa | Pendiente |
| 18 | Respaldo, restauración, documentación final y entrega | 17 | **G4 (Entrega Final)** | Pendiente |

## 2. Entorno diagnosticado y fijado

- **Sistema Operativo:** Windows 11 (NT 10.0.26300.0) x64.
- **Shell de trabajo:** PowerShell 7.4.5 nativo (rutas entre comillas, `Set-Location -LiteralPath`).
- **PHP:** PHP 8.3.28 CLI (ZTS VC 2019 x64) ubicado en `C:\laragon\bin\php\php-8.3.28-Win32-vs16-x64\php.exe`. Extensiones requeridas activas: `pdo_mysql`, `mbstring`, `openssl`, `intl`, `fileinfo`, `zip`, `bcmath`, `sodium`, `curl`.
- **Composer:** Composer 2.9.2 (`C:\composer\composer.bat`).
- **Node / npm:** Node v24.19.0, npm 11.17.0 (`C:\Program Files\nodejs\npm.cmd`).
- **Servidor de base de datos de referencia (MariaDB 10.11):**
  - Actualmente el equipo cuenta con XAMPP (MariaDB 10.4.32), Laragon (MySQL 8.4.3) y MySQL Server 8.0/9.7 en Program Files.
  - Para cumplir con la referencia de MariaDB 10.11 requerida por las pruebas de integridad sin alterar los servicios existentes, se acordó preparar en la **Etapa 02** el ZIP portable oficial de MariaDB 10.11 en `C:\mariadb-10.11`, ejecutándose en el puerto alternativo `3307` con directorio de datos propio, sin registrar servicio en Windows.
- **Ubicación de la aplicación:**
  - Raíz del repositorio: `E:\8VO CICLO\SISTEMA DE INFORMACIÓN GERENCIAL\proyecto team`.
  - Destino de Laravel: `sistema/` (se inicializará en la Etapa 02).

## 3. Próximo paso exacto

Completar la aceptación de la **Etapa 00** (estado de desarrollo, pruebas ejecutadas, decisiones iniciales y `AGENTS.md`) y luego pasar a la **Etapa 01: especificación y aceptación**.
