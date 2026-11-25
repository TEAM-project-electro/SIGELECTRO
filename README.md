# SIGELECTRO — Sistema de Información Gerencial (Caso Electrocentro S. A., Huancayo)

> **La inteligencia artificial en la era digital: innovación en la gestión y seguimiento de proyectos mediante un sistema de información gerencial. Caso Electrocentro S. A., Huancayo.**

![Laravel 12](https://img.shields.io/badge/Laravel-12.71-FF2D20?style=flat-square&logo=laravel&logoColor=white)
![PHP 8.3](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php&logoColor=white)
![MariaDB 10.11](https://img.shields.io/badge/MariaDB-10.11-003545?style=flat-square&logo=mariadb&logoColor=white)
![Power BI](https://img.shields.io/badge/Power%20BI-Desktop-F2C811?style=flat-square&logo=powerbi&logoColor=black)
![Tests](https://img.shields.io/badge/Pruebas-177%20aprobadas-2E7D32?style=flat-square)

Prototipo funcional de **Sistema de Información Gerencial (SIG)** desarrollado en la **Universidad Nacional de Huancavelica (UNH) — E. P. Ingeniería de Sistemas** para el seguimiento de la cartera de proyectos del área de **Gestión de Proyectos del Gobierno Digital** de **Electrocentro S. A.** (10 proyectos estratégicos del PGD 2023–2026 por **S/ 8,623,550.00**, alineados a 6 Objetivos de Gobierno Digital `OGD 01`–`OGD 06`).

---

## 1. Capacidades Principales

| Módulo | Qué resuelve | Destacado técnico |
| :--- | :--- | :--- |
| **Cartera, Hitos y Presupuesto (OLTP)** | Registro centralizado de proyectos PGD, hitos de línea base y cortes trimestrales de avance financiero. | Reglas **R1–R4** en triggers y procedimientos MariaDB; línea base protegida y versionada (solo el Administrador, con motivo, incorpora, retira o reprograma hitos; cada cambio queda en `hito_linea_base_version`). |
| **Tablero Gerencial y 4 KPIs** | Supervisión en tiempo real en dos modalidades (*Corte Común* y *Último Reporte*) y alertas filtrables. | Paridad matemática **0.00 pp** entre base operativa y Data Warehouse; gráfico con cobertura de proyectos por corte y explicación de desviación presupuestal. |
| **Gestión Gerencial de la Cartera** | Priorización multicriterio, riesgos e incidencias, avance físico y valor ganado, presupuesto vigente y comprometido, alertas preventivas, documentos y beneficios. | Pesos y umbrales configurables con motivo; reglas de actor y de presupuesto en triggers; SPI/CPI solo con línea base ponderada; documentos inmutables con huella SHA-256 (`docs/decisiones/2026-11-26_gestion_gerencial.md`). |
| **Data Warehouse, ETL y Power BI** | Analítica histórica para el Comité de Gobierno Digital. | Esquema estrella (`dw_seguimiento_gd`: 4 dimensiones, 2 hechos, 6 vistas), ETL atómico con *mutex* y programación semanal, y tablero nativo `Tablero_Seguimiento_GD_FINAL.pbix`. |
| **Asistente RAG y Agente de Decisión (DSS)** | Consulta documental con citas `[F#]` y priorización gerencial paso a paso (0–100) con opciones comparadas. | Recuperación híbrida **BM25 + embeddings Gemini (RRF)**, umbral anti-temas irrelevantes, carga documental controlada (Solo Admin), degradación offline automática y **8 capas OWASP LLM01/02/06** (*Human-in-the-Loop*). |
| **Reportes y Extensión Odoo 16** | Emisión de reportes A4 imprimibles, CSV seguro e intercambio por lotes de órdenes/recepciones (`PROYGD-005`). | Mitigación contra *CSV Injection* y tabla técnica aislada `abastecimiento_compra` (sin alterar el núcleo de 6 tablas). |
| **Seguridad y Continuidad (Gate G1–G4)** | Control de acceso por roles, auditoría inmutable y recuperación comprobada. | 3 perfiles con Policies en servidor, **7 cuentas técnicas en BD sin `root`**, auditoría inmutable (error 1142), autoservicio `/perfil/clave`, cabeceras HTTP de seguridad y restauración validada en esquemas aislados. |

---

## 2. Requisitos del Entorno

- **PHP:** 8.2+ (probado en `8.3.28`) con `pdo_mysql`, `mbstring`, `openssl`, `intl`, `bcmath`, `fileinfo`.
- **Composer:** 2.8+ y **Node.js:** 18+ (`npm`).
- **Base de datos:** MariaDB 10.11 portable en `127.0.0.1:3307` (esquemas `seguimiento_gd` y `dw_seguimiento_gd`).
- **PowerShell:** 7.4+ (`pwsh`) en Windows 10/11.

---

## 3. Instalación y Arranque Rápido

### Instalación completa desde cero
Configura dependencias, migraciones, contraseña privada inicial de `msalas`, activos Vite (100 % locales, sin CDNs), carga inicial del Data Warehouse, indexación RAG y suite de pruebas:

```powershell
pwsh -File .\scripts\instalar.ps1
```

### Arranque en entorno ya instalado

```powershell
pwsh -File .\scripts\arrancar.ps1
```

Accesos locales:
- **Aplicación y Login:** `http://localhost:8000`
- **Tablero Gerencial:** `http://localhost:8000/tablero`
- **Asistente RAG y Agente de Decisión:** `http://localhost:8000/asistente`
- **Sonda de Salud:** `http://localhost:8000/salud` (devuelve únicamente `{"estado":"OPERATIVO"}`)

### Activación opcional de Gemini API para el Asistente RAG
Sin clave API ni conexión a internet, el asistente funciona en **modo extractivo** y el agente DSS con su **motor determinista local**. Para habilitar redacción con IA y vectores semánticos, añade en `sistema/.env` (archivo excluido de Git):

```env
GEMINI_API_KEY=tu_clave_de_google_ai_studio
```

Luego limpia caché y reindexa:

```powershell
cd sistema
php artisan config:clear
php artisan rag:indexar
```

---

## 4. Perfiles de Acceso

Las contraseñas privadas se asignan localmente (nunca se versionan en Git; las claves públicas legadas están bloqueadas):

| Perfil | Usuario | Área | Alcance |
| :--- | :--- | :--- | :--- |
| **Administrador** | `msalas` | Planeamiento | Cartera completa, reprogramación de línea base con motivo, usuarios, auditoría, ETL y base documental RAG. |
| **Responsable** | `aquispe`, `lrojas` | TIC | Registro de hitos y avances presupuestales en sus proyectos asignados (sin alterar el monto ni la línea base: fechas, incorporación o retiro de hitos). |
| **Consulta** | `cgarcia` | Comité GD | Solo lectura de cartera, tablero, reportes A4/CSV, asistente RAG y agente de decisión. |

Cada usuario autenticado puede cambiar su propia contraseña desde **Mi clave** (`/perfil/clave`), o el operador local mediante `php artisan sig:cambiar-clave <usuario>`.

---

## 5. Scripts de Operación y Pruebas

| Script | Función |
| :--- | :--- |
| `scripts/instalar.ps1` | Instalación completa en limpio (migraciones, `sig:inicializar`, Vite, ETL, RAG y pruebas). |
| `scripts/arrancar.ps1` | Inicia MariaDB 10.11 (`:3307`) y el servidor web Laravel (`:8000`). |
| `scripts/iniciar_mariadb.ps1` / `detener_mariadb.ps1` | Control independiente del servicio MariaDB 10.11. |
| `scripts/respaldo_db.ps1` / `restaurar_db.ps1` | Respaldo lógico completo y restauración verificada sobre esquemas descartables con privilegios mínimos (`scripts/ajustar_permisos.php`). |
| `scripts/probar.ps1` | Ejecuta la batería completa de pruebas automatizadas (**177 pruebas aprobadas** sobre esquemas aislados `_test`). |

---

## 6. Estructura del Repositorio

```text
SIGELECTRO/
├── README.md                  # Guía principal del proyecto
├── scripts/                   # Automatización: instalación, arranque, respaldo y pruebas
├── backups/                   # Destino local de volcados SQL (excluidos de Git)
├── docs/                      # Documentación técnica, académica y de sustentación
│   ├── especificacion.md                  # Especificación funcional y reglas R1–R4
│   ├── contrato_indicadores.md            # Definición matemática de los 4 KPIs
│   ├── manual_usuario.md                  # Manual operativo por perfil
│   ├── manual_tecnico.md                  # Arquitectura, esquema relacional y DW
│   ├── alineamiento_laudon_cap01_10.md    # Matriz teórica Laudon 18.ª ed. (Cap. 1–11)
│   ├── marco_institucional_electrocentro.md # Contexto Electrocentro Huancayo y D.L. 1412
│   ├── informe_cierre.md                  # Informe de cierre, guion y preguntas del jurado
│   ├── estado_desarrollo.md               # Registro de avance y certificaciones
│   ├── decisiones/                        # Registro formal de decisiones de arquitectura
│   ├── procesos_bpmn/                     # 14 procesos de negocio modelados en BPMN 2.0
│   ├── bi/                                # Tablero Power BI (.pbix), CSVs y catálogo DAX
│   ├── odoo/                              # Exportación CSV real de intercambio (Odoo 16)
│   └── presentacion/                      # Tríptico imprimible (INNOVATECH FEST 2026)
└── sistema/                   # Aplicación Laravel 12 (Blade, Tailwind, Alpine, Chart.js)
```

---

## 7. Aviso de Datos Demostrativos

Los nombres, códigos y montos de los 10 proyectos corresponden a la cartera pública del PGD 2023–2026 de Electrocentro S. A.; los hitos, fechas de seguimiento y avances presupuestales son **datos demostrativos con fines académicos** y no representan la ejecución financiera real de la empresa.
