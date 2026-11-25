# SIG Electrocentro S. A. - Sistema de Información Gerencial

**Prototipo Académico Local para la Supervisión y Seguimiento Gerencial de la Cartera del Plan de Gobierno Digital (PGD 2023–2026)**  
**Empresa de Referencia:** Electrocentro S. A. (10 proyectos estratégicos por un monto adjudicado de S/ 8,623,550.00)  
**Estado actual:** Proyecto cerrado y verificado con **174 pruebas automatizadas aprobadas (1,729 aserciones)** (Capítulos 1 al 11 de Laudon, Laudon y Traver, 18.ª ed.). Incluye núcleo OLTP/DW/ETL, cambio de contraseña en autoservicio (`/perfil/clave`), programación semanal del ETL, procesos BPMN 2.0 (`docs/procesos_bpmn/`), tablero Power BI (`docs/bi/Tablero_Seguimiento_GD_FINAL.pbix`), extensión controlada por lotes CSV con Odoo 16 (`docs/odoo/`), **Asistente RAG con carga documental Solo Admin** y **Agente de Decisión Gerencial (DSS)** en `/asistente`. Ver [alineamiento Laudon Cap. 1–11](docs/alineamiento_laudon_cap01_10.md) y [estado de desarrollo](docs/estado_desarrollo.md).

---

## 1. Requisitos del Entorno de Ejecución

El sistema ha sido probado y certificado en entorno Windows 10/11 con las siguientes versiones de software:

- **PHP:** 8.2 o superior (PHP 8.3.28 comprobado) con extensiones `pdo_mysql`, `mbstring`, `openssl`, `intl`, `bcmath`, `fileinfo`.
- **Framework Web:** Laravel 12.71.0, actualizado y probado con PHP 8.3.28; PHPUnit 11.5.57 y lockfile actualizado.
- **Composer:** 2.8+ (Composer 2.9.2 comprobado).
- **Node.js:** v18+ (v24.19.0 comprobado) con `npm`.
- **Motor de Base de Datos:** MariaDB 10.11 portable ejecutándose en `127.0.0.1:3307` (esquemas `seguimiento_gd` y `dw_seguimiento_gd`).
- **PowerShell:** PowerShell 7.4+ nativo (`pwsh`).

---

## 2. Puesta en Marcha Rápida (Instalación y Despliegue)

Para preparar el sistema en una instalación limpia, ejecute los scripts automatizados desde la raíz del proyecto:

### Opción A: Instalación Automatizada Completa

Ejecuta la verificación de prerrequisitos, configuración privada de `.env`, dependencias de Composer y NPM, migraciones, siembra canónica, compilación de activos de frontend, carga inicial de Data Warehouse y ejecución de pruebas:

```powershell
pwsh -File .\scripts\instalar.ps1
```

### Opción B: Arranque Rápido (Entorno Ya Configurado)

Si el entorno ya fue instalado previamente y solo desea iniciar el servidor sin re-sembrar datos ni alterar claves:

```powershell
# 1. Iniciar MariaDB 10.11 en puerto 3307 (si no está activo):
pwsh -File .\scripts\iniciar_mariadb.ps1

# 2. Iniciar el servidor web de desarrollo Laravel:
pwsh -File .\scripts\arrancar.ps1
```

El servidor web quedará accesible de inmediato en:

- **Página de Bienvenida y Login:** [http://localhost:8000](http://localhost:8000)
- **Diagnóstico y Salud del Sistema:** [http://localhost:8000/salud](http://localhost:8000/salud)
- **Tablero Gerencial de Control:** [http://localhost:8000/tablero](http://localhost:8000/tablero)

---

## 3. Cuentas de Acceso de Demostración por Perfil

El acceso al sistema está controlado por roles y políticas estrictas a nivel de servidor (Laravel Policies):

| Perfil                   | Usuario   | Acceso                              | Área         | Alcance de Permisos                                                            |
| :----------------------- | :-------- | :---------------------------------- | :----------- | :----------------------------------------------------------------------------- |
| **Administrador**        | `msalas`  | `Clave privada asignada localmente` | Planeamiento | Gestión total de cartera, reapertura de proyectos, auditoría y control ETL.    |
| **Responsable Titular**  | `aquispe` | `Clave privada asignada localmente` | TIC          | Registro de avances presupuestales y cierre de hitos en sus proyectos.         |
| **Responsable Delegado** | `lrojas`  | `Clave privada asignada localmente` | TIC          | Gestión de proyectos asignados bajo su responsabilidad.                        |
| **Consulta**             | `cgarcia` | `Clave privada asignada localmente` | Comité GD    | Acceso de solo lectura a cartera, tablero analítico y exportación de reportes. |

---

## 4. Scripts de Operación y Mantenimiento

Todos los comandos rutinarios están empaquetados en scripts de PowerShell ubicados en `scripts/`:

| Script                        | Propósito y Acción Ejecutada                                                                          |
| :---------------------------- | :---------------------------------------------------------------------------------------------------- |
| `scripts/iniciar_mariadb.ps1` | Inicia MariaDB 10.11 en segundo plano en `127.0.0.1:3307` sin colisionar con XAMPP/Laragon.           |
| `scripts/detener_mariadb.ps1` | Detiene limpiamente el proceso de MariaDB 10.11 en el puerto 3307.                                    |
| `scripts/instalar.ps1`        | Instalación desde cero: dependencias, base de datos, siembra, Vite y ETL.                             |
| `scripts/arrancar.ps1`        | Inicia MariaDB y arranca `php artisan serve --port=8000`.                                             |
| `scripts/probar.ps1`          | Ejecuta la batería completa de pruebas automatizadas (174 pruebas aprobadas, 1,729 aserciones sobre esquemas aislados). |
| `scripts/respaldo_db.ps1`     | Genera volcados lógicos completos (`seguimiento_gd` y `dw_seguimiento_gd`) en `backups/`.             |
| `scripts/restaurar_db.ps1`    | Restaura los volcados sobre esquemas nuevos descartables para verificación de recuperación.           |

---

## 5. Arquitectura y Cuentas Técnicas de Base de Datos

Cumpliendo con la **Decisión 5** del contrato canónico, el sistema aplica segregación estricta de privilegios. **El uso de `root` en la aplicación web está terminantemente prohibido**:

- **`sig_migrador`:** Migraciones de Laravel, DDL y tareas administrativas de respaldo.
- **`sig_definer`:** Propietario de rutinas almacenadas y triggers de integridad R1 a R4.
- **`sig_auth`:** Proveedor de autenticación con `SELECT` limitado sobre la tabla de usuarios.
- **`sig_lector`:** Conexión de solo lectura para listados y fichas gerenciales (sin acceso a `clave_hash`).
- **`sig_escritor`:** Conexión transaccional para operaciones de negocio (`bitacora_cambio` y `bitacora_motivo` tienen revocado `UPDATE` y `DELETE`).
- **`sig_etl`:** Usuario exclusivo para ejecutar la carga transaccional del Data Warehouse (`sp_etl_cargar`).
- **`bi_lector`:** Conexión de solo lectura sobre las dimensiones y vistas del DW para Power BI y Excel.

---

## 6. Estado de las Puertas de Control (Gates) y Batería de Pruebas

Las comprobaciones automatizadas del núcleo se detallan en el [informe de corrección](docs/correcciones_auditoria_2026-11-04.md). No equivalen a aceptación institucional ni a ejecución de Power BI:

- **Gate G1 (Acceso y Seguridad):** Autenticación de tres perfiles, control por políticas de servidor con bloqueo estricto de campos de presupuesto para el Responsable (A01), middleware de usuario activo en cada petición (A09), protección contra fuerza bruta y sesiones aisladas. _(Ratificada)_.
- **Gate G2 (Operación y Auditoría):** Reglas R1 (límites presupuestales y protección ante reducciones), R2 (monotonicidad devengada), R3 (cierre con hitos LB cerrados, reapertura con motivo obligatorio), R4 (auditoría forzosa en inserción, modificación y eliminación de todas las entidades operativas, protegiendo contraseñas y con inmutabilidad estricta) y pruebas de concurrencia pesimistas/optimistas deterministas (A03, A04, A05). _(Ratificada)_.
- **Gate G3 (Analítica y Reportes):** Motor de 4 KPIs evaluado contra el oráculo independiente al 30/06/2024 ($\le 0.01$ pp), paridad relacional vs DW respetando filtros dinámicos (A06), semántica estricta «Sin datos» vs «No aplica» (A11), exportación CSV anti-injection y vista A4 imprimible. _(Ratificada)_.
- **Gate G4 (Entrega y Respaldo):** Restauración comprobada sobre bases nuevas descartables con remapeo de origen en DW y privilegios mínimos reales (A08), conciliación del 100% de conteos y montos, y guion de sustentación preparado. _(Ratificada)_.

```text
Tests:    151 passed
```

---

## 7. Business Intelligence (Power BI) y Extensión de Intercambio de Datos (Cap. 6 y Cap. 9)

- **Power BI Desktop (`docs/bi/`):** Incluye el tablero interactivo [`docs/bi/Tablero_Seguimiento_GD_FINAL.pbix`](docs/bi/Tablero_Seguimiento_GD_FINAL.pbix), los 8 archivos CSV con marca BOM UTF-8 en `docs/bi/datos/` con su `manifiesto_extraccion.json`, la especificación del modelo en estrella (`docs/bi/modelo_power_bi.md`), el catálogo dinámico de medidas DAX (`docs/bi/medidas_DAX.txt`) y la guía de conciliación (`docs/bi/guia_power_bi.md`).
- **Extensión controlada de intercambio por lotes con Odoo 16 (`docs/odoo/`):** Conforme a la [decisión de alcance del 24/11/2026](docs/decisiones/2026-11-24_extension_intercambio_odoo.md), el repositorio incorpora una tabla técnica independiente (`abastecimiento_compra`) y un importador CSV transaccional (`/abastecimiento/importar`, `/abastecimiento/plantilla`) que demuestra el intercambio de datos de órdenes y recepciones desde Odoo 16 (`sigelectro_odoo`) hacia un proyecto supervisado (`005`), sin alterar las 6 tablas canónicas ni convertir al SIG en un ERP o sistema en tiempo real.
- **Modelado de procesos y marco teórico:** Diagramas BPMN 2.0 en `docs/procesos_bpmn/` y trazabilidad calibrada contra la 18.ª edición de Laudon, Laudon y Traver en [`docs/alineamiento_laudon_cap01_10.md`](docs/alineamiento_laudon_cap01_10.md).

---

## 8. Estructura de Directorios del Proyecto

```text
proyecto team/
  PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md  # Contrato canónico maestro del proyecto
  AGENTS.md                               # Reglas de desarrollo del agente
  README.md                               # Guía principal de instalación y operación
  scripts/                                # Scripts de gestión, respaldo y arranque
  backups/                                # Dumps lógicos de base de datos (excluidos de Git)
  docs/                                   # Documentación técnica y de gestión
    alineamiento_laudon_cap01_10.md       # Alineamiento y delimitación Laudon 18.ª ed. (Cap. 1–10)
    manual_usuario.md                     # Manual operativo detallado por perfil
    manual_tecnico.md                     # Arquitectura, esquema, triggers y seguridad
    informe_cierre.md                     # Informe de cierre técnico y estado de entrega
    informe_validacion.md                 # Evaluación de los 8 escenarios canónicos
    contrato_indicadores.md               # Definición matemática de los 4 KPIs
    especificacion.md                     # Especificación funcional y reglas R1-R4
    matriz_trazabilidad.md                # Trazabilidad de requisitos funcionales RF01-RF08
    entorno.md                            # Comandos comprobados de desarrollo
    estado_desarrollo.md                  # Matriz de avance y certificación de etapas
    pruebas_ejecutadas.md                 # Registro histórico de pruebas y evidencias
    decisiones/                           # Registro formal de decisiones de arquitectura y alcance
    procesos_bpmn/                        # Modelos de procesos AS-IS y TO-BE en BPMN 2.0
    odoo/                                 # Exportaciones reales de intercambio por lotes (Odoo 16)
    bi/                                   # Tablero .pbix, CSVs, modelo estrella y catálogo DAX
  sistema/                                # Aplicación Laravel 12 (monolito modular)
```

### Acceso privado y renovación de contraseña

En una instalación nueva, `scripts/instalar.ps1` ejecuta `php artisan sig:inicializar` después de migrar. El comando solicita dos veces una contraseña privada para `msalas`, sin mostrarla ni guardarla en archivos o argumentos. Debe tener entre 12 y 72 bytes. No hay contraseñas predeterminadas públicas. El administrador asigna contraseñas privadas a las demás cuentas desde **Usuarios** antes de entregarlas, y cada usuario autenticado puede renovar su propia contraseña privada desde **Mi clave** (`/perfil/clave`).

Repetir la inicialización o la siembra conserva las cuentas existentes, incluidas claves, perfil, estado y datos editados. Las claves demostrativas anteriores quedan rechazadas en el login y no pueden volver a asignarse desde Usuarios. En una instalación anterior, el operador debe ejecutar desde `sistema/`: `php artisan sig:cambiar-clave msalas` (y repetir con los demás usuarios que necesiten renovación). El comando pide la contraseña sin mostrarla; conserva perfil/estado y audita el cambio. No escriba contraseñas en argumentos o en el repositorio. Ejecutar `db:seed` directamente en una base nueva genera claves aleatorias no entregadas; utilice el flujo de inicialización interactiva para disponer del acceso administrador.

La sonda pública `/salud` devuelve únicamente `estado` y HTTP 200/503. Los detalles de errores quedan en el registro del servidor.
