# Matriz de Trazabilidad y Requisitos (RF01 - RF08)

Fecha: 3 de noviembre de 2026.
Referencia canónica: [PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md](../PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md).

---

## 1. Matriz de Requisitos Funcionales y Criterios de Aceptación

| ID | Requisito Funcional | Prioridad | Fuente Documental | Tablas Implicadas | Regla de Negocio / Refuerzo | Caso de Prueba | Criterio de Aceptación | Compuerta | Estado / Comprobación Real | Pendientes Externos |
| --- | --- | --- | --- | --- | --- | --- | --- | :---: | :---: | --- |
| **RF01** | Autenticación y control de tres perfiles | Esencial | Plan Tabla 5 (p. 10); Informe Tabla 27 (p. 61) | `responsable` | 3 perfiles (Admin, Resp, Consulta). Sesión con CSRF y rate-limiting. Denegación en servidor por Policies. | `TC-AUTH-01` a `TC-AUTH-05` | Cada perfil ejecuta únicamente las acciones autorizadas. Ataques por ID directo o formulario manipulado responden 403 Forbidden. | **G1** | Especificado | Ninguno |
| **RF02** | Registrar y editar proyectos | Esencial | Plan Tabla 5; Informe Tabla 17 (p. 31) | `proyecto`, `objetivo_gd`, `responsable` | Código PGD texto con ceros a la izquierda (ej. '005'). Unicidad de código. `monto >= 0`, `anio_fin >= anio_inicio`. | `TC-PROY-01` a `TC-PROY-04` | Alta y edición persisten con éxito; duplicados y montos negativos rechazados en servidor. | **G2** | Especificado | Ninguno |
| **RF03** | Hitos y avances con autor y trazabilidad | Esencial | Plan Tabla 5; Informe Caps. 3 y 4 | `hito`, `avance_presupuestal`, `bitacora_cambio`, `bitacora_motivo` | R1 (financiera), R2 (gasto no decreciente), R3 (cierre de proyecto con línea base completa), R4 (`@id_responsable` obligatorio). | `TC-SEGUI-01` a `TC-SEGUI-08` | Modificaciones atómicas con bitácora. Cierre impedido con hitos pendientes o 0 hitos. Monotonicidad de gasto comprobada. | **G2** | Especificado | Ninguno |
| **RF04** | Cuatro indicadores gerenciales verificables | Esencial | Plan Tabla 8 (p. 14); Informe Cap. 5 | `proyecto`, `hito`, `avance_presupuestal` | Avance de hitos, Retraso de activos, Desviación presupuestal y Estados. Distinción de "Sin datos" y "No aplica". | `TC-IND-01` a `TC-IND-06` | Tolerancia de $\le 0.01$ puntos porcentuales frente a oráculo independiente. Conteos exactos. Paridad Operativo vs DW. | **G3** | Especificado | Ninguno |
| **RF05** | Filtros sincronizados y dos modalidades temporales | Esencial | Plan Tabla 5; Informe Tabla 38 (p. 75) | Todas las anteriores | Sincronización URL entre tablero, detalle y exportación. Modos `CORTE_COMUN` y `ULTIMO_REPORTE_POR_PROYECTO`. | `TC-FILT-01` a `TC-FILT-04` | Filtros por estado, responsable y objetivo devuelven resultados idénticos en resumen y detalle; modal temporal explícito. | **G3** | Especificado | Ninguno |
| **RF06** | Reporte exportable (CSV y vista imprimible) | Esencial | Plan Tabla 5; Informe Cap. 5 | Vistas de consulta y hechos DW | Exportación respeta contexto de filtros, corte y versión (`control_revision`). Escape contra inyección de fórmulas. | `TC-EXP-01` a `TC-EXP-04` | Archivo CSV UTF-8 descargable y vista `@media print` coinciden al 100 % con las cifras mostradas en pantalla. | **G3** | Especificado | Ninguno |
| **RF07** | Importación CSV de cortes presupuestales | Complementario | Plan Tabla 5 (p. 10) | `avance_presupuestal`, `bitacora_cambio` | Validación previa de lote sin escribir en base. Reporte de errores por fila. Rollback total ante error. | `TC-IMP-01` a `TC-IMP-04` | Previsualización exacta; rechazo de duplicados o quiebres de monotonicidad; confirmación transaccional. | Post-G3 | Especificado | Ninguno |
| **RF08** | Alertas de actualización y calidad | Complementario | Plan Tabla 5 (p. 10); Informe Tabla 37 | Vistas `v_alertas`, `v_calidad_datos` | Hitos vencidos, falta de corte en 90 días, cierre sin evidencia y descripciones OGD pendientes. Enlace a detalle. | `TC-ALERT-01` a `TC-ALERT-03` | Lista de alertas deduplicada y navegable según permisos del usuario. | Post-G3 | Especificado | Ninguno |

---

## 2. Requisitos Originales vs. Refuerzos Técnicos

### Requisitos Originales de los Documentos Fuente
1. Seis tablas relacionales con 41 columnas y 5 llaves foráneas con `ON DELETE RESTRICT` y `ON UPDATE CASCADE`.
2. Cartera de 10 proyectos por S/ 8,623,550.00.
3. Tres perfiles de usuario (`Administrador`, `Responsable`, `Consulta`).
4. Cuatro indicadores gerenciales y reportes exportables.
5. Base Data Warehouse (`dw_seguimiento_gd`) con 4 dimensiones tipo 1 y 2 tablas de hechos semiaditivas.

### Refuerzos Técnicos Incorporados en el Contrato
1. **Refuerzo R3 (Cierre y Reapertura):** Exigir al menos un hito en línea base antes de permitir el cierre de un proyecto (evita cierre trivial con 0 hitos). Exigir justificación obligatoria en reaperturas.
2. **Refuerzo R4 / Extensión `bitacora_motivo`:** Provisión de una tabla de extensión para almacenar el motivo de cambios sensibles (reaperturas, desvinculación de línea base, decrementos de monto) sin añadir columnas ajenas a `bitacora_cambio`.
3. **Extensión `control_revision`:** Contador transaccional monótono de versión del conjunto para garantizar lecturas consistentes entre tablero y exportación, y detectar obsolescencia del Data Warehouse.
4. **Cuentas Técnicas de MariaDB:** Separación estricta de cuentas técnicas (`sig_migrador`, `sig_definer`, `sig_auth`, `sig_lector`, `sig_escritor`, `sig_etl`, `bi_lector`) prohibiendo el uso de `root` en la aplicación web.
5. **Decisión 2 (Aislamiento de Hitos fuera de Línea Base):** Especificación estricta de que un hito con `en_linea_base = 0` vencido no genera retraso en el proyecto evaluable.

---

## 3. Backlog de Implementación por Fases y Dependencias

```mermaid
graph TD
    subgraph Fase 1: Arquitectura y Datos (G1)
        B01["E02: Entorno MariaDB 10.11 portátil (puerto 3307) y Laravel base"]
        B02["E03: Esquema DDL, Triggers R1-R4, control_revision, bitacora_motivo"]
        B03["E04: Seeder determinista de cartera (10 proyectos, 6 OGD)"]
        B04["E05: Autenticación, Policies y pruebas HTTP (Gate G1)"]
        B01 --> B02 --> B03 --> B04
    end

    subgraph Fase 2: Operaciones y Auditoría (G2)
        B05["E06: Módulo Cartera y Catálogos (CRUD con bloqueo)"]
        B06["E07: Módulo Hitos, Línea Base y Cierre R3"]
        B07["E08: Módulo Avances Presupuestales y Reglas R1-R2"]
        B08["E09: Trazabilidad integral y verificación de auditoría (Gate G2)"]
        B04 --> B05 --> B06 --> B07 --> B08
    end

    subgraph Fase 3: Analítica y Reportes (G3)
        B09["E10: Servicio de Indicadores y Oráculo Independiente"]
        B10["E11: Data Warehouse dw_seguimiento_gd y Procedimiento ETL"]
        B11["E12: Tablero Gerencial Frontend (Blade/Tailwind/Chart.js)"]
        B12["E13: Módulo de Exportación CSV e Imprimible (Gate G3)"]
        B08 --> B09 --> B10 --> B11 --> B12
    end

    subgraph Fase 4: Entrega y Cierre (G4)
        B13["E14: Preparación de Power BI (docs/bi/)"]
        B14["E15-E16: Complementarios opcionales (CSV input / Alertas)"]
        B15["E17: Validación Integral de la Solución"]
        B16["E18: Respaldo, Restauración comprobada y Entrega (Gate G4)"]
        B12 --> B13
        B12 --> B14
        B13 --> B15
        B14 --> B15
        B15 --> B16
    end
```
