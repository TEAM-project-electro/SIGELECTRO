# SIG Electrocentro S. A. — Sistema de Información Gerencial

**Prototipo Académico Local para la Supervisión y Seguimiento Gerencial de la Cartera del Plan de Gobierno Digital (PGD 2023–2026)**  
**Empresa de Referencia:** Electrocentro S. A. (10 proyectos estratégicos por un monto adjudicado de S/ 8,623,550.00)  
**Institución:** Universidad Nacional de Huancavelica — Escuela Profesional de Ingeniería de Sistemas  
**Asignatura:** Sistema de Información Gerencial (8vo Ciclo)  
**Estado Oficial:** **Compuerta Gate G4 Superada — Núcleo Académico Verificado al 100%; Power BI en Estado PREPARADO**

---

## 1. Requisitos del Entorno de Ejecución

El sistema ha sido probado y certificado en entorno Windows 10/11 con las siguientes versiones de software:

- **PHP:** 8.2 o superior (PHP 8.3.28 comprobado) con extensiones `pdo_mysql`, `mbstring`, `openssl`, `intl`, `bcmath`, `fileinfo`.
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

| Perfil | Usuario | Contraseña Demo | Área | Alcance de Permisos |
| :--- | :--- | :--- | :--- | :--- |
| **Administrador** | `msalas` | `Admin2026!` | Planeamiento | Gestión total de cartera, reapertura de proyectos, auditoría y control ETL. |
| **Responsable Titular** | `aquispe` | `Resp2026!` | TIC | Registro de avances presupuestales y cierre de hitos en sus proyectos. |
| **Responsable Delegado** | `lrojas` | `Resp2026!` | TIC | Gestión de proyectos asignados bajo su responsabilidad. |
| **Consulta** | `cgarcia` | `Consulta2026!` | Comité GD | Acceso de solo lectura a cartera, tablero analítico y exportación de reportes. |

---

## 4. Scripts de Operación y Mantenimiento

Todos los comandos rutinarios están empaquetados en scripts de PowerShell ubicados en `scripts/`:

| Script | Propósito y Acción Ejecutada |
| :--- | :--- |
| `scripts/iniciar_mariadb.ps1` | Inicia MariaDB 10.11 en segundo plano en `127.0.0.1:3307` sin colisionar con XAMPP/Laragon. |
| `scripts/detener_mariadb.ps1` | Detiene limpiamente el proceso de MariaDB 10.11 en el puerto 3307. |
| `scripts/instalar.ps1` | Instalación desde cero: dependencias, base de datos, siembra, Vite y ETL. |
| `scripts/arrancar.ps1` | Inicia MariaDB y arranca `php artisan serve --port=8000`. |
| `scripts/probar.ps1` | Ejecuta la batería completa de pruebas automatizadas (116 tests pasando). |
| `scripts/respaldo_db.ps1` | Genera volcados lógicos completos (`seguimiento_gd` y `dw_seguimiento_gd`) en `backups/`. |
| `scripts/restaurar_db.ps1` | Restaura los volcados sobre esquemas nuevos descartables para verificación de recuperación. |

---

## 5. Arquitectura y Cuentas Técnicas de Base de Datos

Cumpliendo con la **Decisión 5** del contrato canónico, el sistema aplica segregación estricta de privilegios. **El uso de `root` en la aplicación web está terminantemente prohibido**:

- **`sig_migrador`:** Migraciones de Laravel, DDL y tareas administrativas de respaldo.
- **`sig_definer`:** Propietario de rutinas almacenadas y triggers de integridad R1 a R4.
- **`sig_auth`:** Proveedor de autenticación con `SELECT` limitado sobre la tabla de usuarios.
- **`sig_lector`:** Conexión de solo lectura para listados y fichas gerenciales.
- **`sig_escritor`:** Conexión transaccional para operaciones de negocio (`bitacora_cambio` tiene revocado `UPDATE` y `DELETE`).
- **`sig_etl`:** Usuario exclusivo para ejecutar la carga transaccional del Data Warehouse (`sp_etl_cargar`).
- **`bi_lector`:** Conexión de solo lectura sobre las dimensiones y vistas del DW para Power BI y Excel.

---

## 6. Estado de las Puertas de Control (Gates) y Batería de Pruebas

El sistema ha superado con éxito el 100% de las compuertas de aceptación exigidas:

- **Gate G1 (Acceso y Seguridad):** Autenticación de tres perfiles, control por políticas de servidor, protección contra fuerza bruta y sesiones aisladas. *(Superada en Etapa 05)*.
- **Gate G2 (Operación y Auditoría):** Reglas R1 (límites presupuestales), R2 (monotonicidad devengada), R3 (cierre de proyecto con hitos LB cerrados) y R4 (auditoría forzosa e inmutabilidad). *(Superada en Etapa 09)*.
- **Gate G3 (Analítica y Reportes):** Motor de 4 KPIs evaluado contra el oráculo independiente al 30/06/2024 ($\le 0.01$ pp), paridad relacional vs DW, exportación CSV anti-injection y vista A4 imprimible. *(Superada en Etapa 13)*.
- **Gate G4 (Entrega y Respaldo):** Restauración comprobada sobre bases nuevas descartables, conciliación del 100% de conteos y montos, y guion de sustentación preparado. *(Superada en Etapa 18)*.

```text
Tests:    116 passed (755 assertions)
Duration: 10.28s (100% de cobertura funcional)
```

---

## 7. Componente de Business Intelligence (Power BI)

De acuerdo con la **Decisión 7** del contrato maestro, el componente de BI se encuentra en estado **PREPARADO**:
- Se generaron los 8 archivos CSV con marca BOM UTF-8 en `docs/bi/datos/` con su correspondiente `manifiesto_extraccion.json`.
- Se dispone de la especificación técnica en `docs/bi/modelo_power_bi.md` y del catálogo de fórmulas DAX en `docs/bi/medidas_DAX.txt`.
- La guía de importación y diseño está documentada en `docs/bi/guia_power_bi.md`, incluyendo una tabla de conciliación cruzada de medidas DAX contra consultas SQL en el Data Warehouse.

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
    manual_usuario.md                     # Manual operativo detallado por perfil
    manual_tecnico.md                     # Arquitectura, esquema, triggers y seguridad
    informe_cierre.md                     # Informe final de cierre y guion de sustentación
    informe_validacion.md                 # Evaluación de los 8 escenarios canónicos
    contrato_indicadores.md               # Definición matemática de los 4 KPIs
    especificacion.md                     # Especificación funcional y reglas R1-R4
    matriz_trazabilidad.md                # Trazabilidad de requisitos funcionales RF01-RF08
    entorno.md                            # Comandos comprobados de desarrollo
    estado_desarrollo.md                  # Matriz de avance y certificación de etapas
    pruebas_ejecutadas.md                 # Registro histórico de pruebas y evidencias
    bi/                                   # Artefactos para Microsoft Power BI
  sistema/                                # Aplicación Laravel 11 (monolito modular)
```
