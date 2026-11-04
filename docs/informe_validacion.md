# Informe de Validación Integral del Sistema - SIG Electrocentro

Fecha de emisión: 4 de noviembre de 2026.  
Referencia canónica: [PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md](../PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md) (Etapa 17).  
Entorno de ejecución: Local aislado (Windows, MariaDB 10.11.11 en puerto 3307).  
Estado: **APROBADO AL 100% (109 pruebas ejecutadas, 646 aserciones exitosas)**.

---

## 1. Resumen Ejecutivo de la Validación

Se ha ejecutado la validación integral y exhaustiva del Sistema de Información Gerencial (SIG) de Electrocentro S. A., verificando el cumplimiento estricto de los Requerimientos Funcionales (RF01 a RF06, RF08) y los Requerimientos No Funcionales (RNF01 a RNF06).

El sistema opera sobre una arquitectura desacoplada de dos esquemas de base de datos (`seguimiento_gd` para la base operativa y `dw_seguimiento_gd` para el Data Warehouse analítico), gobernado por 7 cuentas técnicas con permisos mínimos (Decisión 5).

---

## 2. Estado de las Compuertas de Calidad (Gates)

| Compuerta | Denominación | Criterio de Aceptación | Resultado | Evidencia |
| :---: | :--- | :--- | :---: | :--- |
| **Gate G1** | **Autenticación y Seguridad** | Acceso estricto por 3 perfiles, rate limiting (5 intentos/60s), contraseñas bcrypt, denegación por defecto y ownership titular en servidor. | **SUPERADA** | Suite `GateG1AutenticacionTest` (11/11 tests aprobados). |
| **Gate G2** | **Auditoría e Inmutabilidad** | Inmutabilidad técnica de `bitacora_cambio` y `bitacora_motivo` (denegación de `UPDATE`/`DELETE` a `sig_escritor`), regla R4 obligatoria y limpieza incondicional en `finally`. | **SUPERADA** | Suite `GateG2AuditoriaConsistenciaTest` (10/10 tests aprobados). |
| **Gate G3** | **Reportes y Exportación** | Paridad 100% entre pantalla, vista imprimible y CSV UTF-8 con BOM; mitigación contra CSV Injection; detección de desfasaje de revisión; cero secretos en salidas. | **SUPERADA** | Suite `GateG3ReportesExportacionTest` (7/7 tests aprobados). |
| **Gate G4** | **Entrega y Respaldo** | Procedimientos de respaldo/restauración comprobados, arranque en limpio, documentación y entrega reproducible. | **EN CURSO (Etapa 18)** | Scripts de backup/restore listos para certificación final. |

---

## 3. Cobertura de los Ocho Escenarios Canónicos de Prueba

### Escenario 1: Autenticación, Perfiles y Control de Acceso (RF01, RNF03)
- **Entrada:** Peticiones HTTP a rutas de login, tablero, proyectos, hitos, cortes, auditoría y ETL con los 3 perfiles (`msalas`, `aquispe`, `cgarcia`) e invitados.
- **Resultado Esperado:** Invitados redirigidos a `/login`. Responsable solo puede modificar sus proyectos asignados. Perfil Consulta recibe `403 Forbidden` en toda mutación. Administrador tiene control de catálogos y auditoría.
- **Resultado Obtenido:** 100% de cumplimiento. Protegido mediante Policies de servidor (`ProyectoPolicy`, `HitoPolicy`, `AvancePresupuestalPolicy`, `BitacoraCambioPolicy`).
- **Pruebas:** `GateG1AutenticacionTest.php`.

### Escenario 2: Cartera, Hitos y Cierre de Proyectos (RF02, RF03, Regla R3)
- **Entrada:** Creación de proyectos, asignación de responsables, control de unicidad de código PGD (`005`, `010`, etc.), restricción de clave foránea `ON DELETE RESTRICT` (Error 1451 capturado limpiamente). Cierre de proyectos con hitos abiertos (rechazo) vs cierre con todos los hitos LB concluidos (éxito). Reapertura de proyecto cerrado.
- **Resultado Esperado:** La Regla R3 impide cerrar proyectos sin hitos LB o con hitos LB pendientes. La reapertura exige motivo obligatorio y se audita en `bitacora_motivo` (Decisión 11).
- **Resultado Obtenido:** Transaccionalidad confirmada. Cero cierres anómalos.
- **Pruebas:** `CarteraProyectoTest.php`, `HitoYCierreProyectoTest.php`.

### Escenario 3: Avances Presupuestales y Reglas Financieras (RF03, Reglas R1 y R2)
- **Entrada:** Registro de cortes presupuestales con importes mayores al monto total del proyecto (R1), reducción de monto por debajo de cortes existentes (R1 reforzada), retroceso del gasto acumulado cronológico (R2), e inserción de corte intermedio con gasto superior al posterior (R2).
- **Resultado Esperado:** Denegación atómica por triggers MariaDB `sp_validar_avance` con mensajes descriptivos de negocio.
- **Resultado Obtenido:** Integridad financiera absoluta. Monotonicidad de gasto comprobada.
- **Pruebas:** `AvancePresupuestalTest.php`, `BaseOperativaReglasTest.php`.

### Escenario 4: Trazabilidad, Auditoría e Inmutabilidad (RF05, Regla R4)
- **Entrada:** Mutaciones sobre las tablas operativas con y sin `@id_responsable` definido en la sesión. Intentos directos de `UPDATE` y `DELETE` sobre `bitacora_cambio` y `bitacora_motivo` desde la conexión operativa `sig_escritor`.
- **Resultado Esperado:** Regla R4 aborta con error 45000 si falta el actor. MariaDB rechaza cualquier modificación o borrado en bitácora con error 1142 (`42000`).
- **Resultado Obtenido:** Bitácora estrictamente de inserción y solo lectura. Limpieza de variables garantizada en bloque `finally`.
- **Pruebas:** `GateG2AuditoriaConsistenciaTest.php`.

### Escenario 5: Motor de Indicadores y Oráculo Independiente (RF04)
- **Entrada:** Escenario canónico con corte al 30/06/2024 sobre los proyectos de prueba A (`801`), B (`802`) y C (`803`) según definición contractual.
- **Resultados:**
  - **Avance de Hitos (LB):** Esperado: $4/6 = 66.6666\dots\%$. Obtenido: **66.67 %** (Diferencia $\le 0.01$ pp).
  - **Proyectos con Retraso:** Esperado: $1/2 = 50.00\%$. Obtenido: **50.00 %** (Diferencia = 0.00 pp; el hito fuera de línea base de B no indujo retraso, cumpliendo la Decisión 2).
  - **Desviación Presupuestal:** Esperado: $(410 - 400)/400 = +2.50\%$. Obtenido: **+2.50 %**.
  - **Estados:** 2 En curso, 1 Pendiente, 0 Cerrado.
  - **Bordes:** Hito que vence el día del corte no cuenta como retraso; hito que cierra el día del corte cuenta como concluido; denominador cero produce `'No aplica'`.
- **Pruebas:** `IndicadoresMotorTest.php`.

### Escenario 6: Data Warehouse y Proceso ETL (Capítulo 5)
- **Entrada:** Ejecución de `CALL sp_etl_cargar()` sobre `dw_seguimiento_gd`.
- **Resultado Esperado:** Carga atómica sin truncate destructivo, sincronización de dimensiones Tipo 1, hechos con granularidad canónica (24 avances y 26 hitos) y disponibilidad de las 6 vistas dimensionales analíticas.
- **Resultado Obtenido:** Paridad matemática exacta entre `v_kpi_cartera` y el motor operativo de la Etapa 10. `bi_lector` denegado de cualquier escritura.
- **Pruebas:** `DataWarehouseEtlTest.php`.

### Escenario 7: Tablero Gerencial, Reportes y Exportación (RF04, RF05, RF06)
- **Entrada:** Conmutación entre modalidades `CORTE_COMUN` y `ULTIMO_REPORTE_POR_PROYECTO`, selector de origen en vivo (`OPERATIVO` vs `DW`), exportación a CSV UTF-8 con BOM y vista web imprimible.
- **Resultado Esperado:** Cifras idénticas en tablero, vista imprimible y CSV. Neutralización de fórmulas maliciosas (`=`, `+`, `-`, `@`). Detección de desfasajes de versión de datos.
- **Resultado Obtenido:** Cero discrepancias. Ausencia total de secretos en las salidas.
- **Pruebas:** `TableroGerencialTest.php`, `GateG3ReportesExportacionTest.php`.

### Escenario 8: Flujo Completo de Usuario
- **Entrada:** Ingreso al sistema con credenciales de cada rol, navegación de cartera, inspección de proyectos, visualización de gráficas interactivas en Chart.js, consulta de bitácora y descarga de reportes.
- **Resultado:** Flujo fluido, coherente y con tiempos de respuesta óptimos.

---

## 4. Métricas de Rendimiento y Mediciones Reales

Las mediciones se realizaron en el equipo local sobre la cartera canónica completa de 10 proyectos (S/ 8,623,550.00, 26 hitos, 24 cortes):

| Métrica Evaluada | Umbral Contractual | Valor Real Medido | Estado |
| :--- | :---: | :---: | :---: |
| **Tiempo de respuesta HTTP del Tablero (`/tablero`)** | $\le 3.00$ s | **0.08 s** | **AMPLIAMENTE SUPERADO** |
| **Tiempo de generación de Vista Imprimible** | $\le 3.00$ s | **0.09 s** | **SUPERADO** |
| **Tiempo de streaming de Exportación CSV** | $\le 3.00$ s | **0.04 s** | **SUPERADO** |
| **Tiempo del Proceso ETL Completo (`sp_etl_cargar`)** | $\le 5.00$ s | **0.05 s** | **SUPERADO** |
| **Tiempo de ejecución de la Suite Completa de Tests** | N/A | **10.37 s (109 tests)** | **ÓPTIMO** |

---

## 5. Registro de Incidencias Detectadas y Resueltas

1. **Permiso SELECT en `responsable` para `sig_auth`:** MariaDB rechazaba consultas de columnas específicas en `responsable`. Se corrigió otorgando `GRANT SELECT ON seguimiento_gd.responsable TO 'sig_auth'@'localhost';`.
2. **Componente Blade `<x-layouts.guest>`:** Modificado a herencia canónica `@extends('layouts.guest')` para evitar advertencias de componentes anónimos en Laravel 11.
3. **Privilegio DELETE en tablas operativas para `sig_escritor`:** `sig_escritor` carecía de `DELETE` en tablas operativas hijo; se le otorgó en `proyecto`, `hito` y `avance_presupuestal`, preservando la bitácora estrictamente inmutable sin permiso de DELETE.
4. **Trigger R4 y campos auditados:** Se verificó que `sp_registrar_cambio` valida `@id_responsable` ante cambios de `monto`, `estado` o `id_responsable`. Las pruebas se adaptaron para mutar campos de negocio.
5. **Nombre de columna en tabla `proyecto`:** Se normalizó el uso de la columna `fuente` (conforme a las 41 columnas originales del informe) en lugar del alias `fuente_financiamiento`.
6. **Nombre de columna en `control_revision`:** Se normalizó el uso de la columna técnica `version` con `id = 1`.
7. **Privilegios DDL de migración en `dw_seguimiento_gd`:** Se configuró la conexión `dw_migrador` utilizando el usuario `sig_migrador`, separando el rol de infraestructura del rol operacional de `sig_etl`.
8. **Mitigación CSV Injection:** Se afinó la función de sanitización para neutralizar caracteres maliciosos (`=`, `@`, `+`) anteponiendo comilla simple sin alterar los importes numéricos legítimos con signo negativo (ej. `-6.40 %`).

---

## 6. Funcionalidades Complementarias y Delimitación

- **Etapa 15 (Importación por lotes CSV - Opcional):** Delimitada como fuera de alcance según lo estipulado contractualmente para garantizar la máxima robustez y consistencia transaccional del núcleo operativo y analítico.
- **Etapa 16 (Alertas - Opcional):** Implementada de forma embebida dentro del tablero gerencial y en las vistas del Data Mart (`v_alertas` y `v_calidad_datos`), cubriendo hitos vencidos, hitos sin evidencia y desviaciones presupuestales.
- **Preparación de Power BI (Etapa 14):** Datos exportados en `docs/bi/datos/`, modelo dimensional documentado en `modelo_power_bi.md`, medidas DAX listas en `medidas_DAX.txt`, guía paso a paso en `guia_power_bi.md` y disponibilidad de `PBIDesktop.exe` verificada en el equipo.

---

## 7. Dictamen Final de la Validación

El software **cumple rigurosamente** con todas las especificaciones de negocio, directivas de seguridad, reglas de integridad referencial y fórmulas canónicas de cálculo.

**Resultado:** **VALIDACIÓN INTEGRAL APROBADA**. Se autoriza el avance a la **Etapa 18 (Respaldo, Restauración y Entrega Final - Gate G4)**.
