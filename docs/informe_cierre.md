# Informe de Cierre de Proyecto y Entrega Académica — SIG Electrocentro S. A.

**Proyecto:** Sistema de Información Gerencial para el Seguimiento de la Cartera de Gobierno Digital  
**Organización Evaluada:** Electrocentro S. A. (Cartera PGD 2023–2026, 10 proyectos por S/ 8,623,550.00)  
**Institución Académica:** Universidad Nacional de Huancavelica — Escuela Profesional de Ingeniería de Sistemas  
**Asignatura:** Sistema de Información Gerencial (8vo Ciclo)  
**Fecha de Actualización:** 24 de noviembre de 2026  
**Versión de Entrega:** 2.1 (Cierre integral Capítulos 1–11 Laudon, Laudon y Traver 18.ª ed. verificado con 174 pruebas / 1,729 aserciones; incluye Power BI `.pbix`, extensión Odoo 16, Asistente RAG + Agente DSS + carga documental Solo Admin y tríptico UNTELS)

---

## 1. Resumen Ejecutivo y Alcance Logrado

El presente proyecto implementa un prototipo funcional reproducible y robusto para la supervisión y control gerencial de la cartera de proyectos de transformación y gobierno digital de **Electrocentro S. A.** El sistema abarca el seguimiento físico de 26 hitos y el control presupuestal de 24 cortes cronológicos sobre una cartera adjudicada de S/ 8,623,550.00.

El software fue desarrollado bajo una arquitectura web modular en Laravel 12 y MariaDB 10.11 aislado (puerto 3307), incorporando una estricta segregación de privilegios técnicos, integridad referencial blindada mediante triggers de negocio (R1 a R4), un motor de cálculo de indicadores temporales conciliado contra oráculo independiente, un Data Warehouse dimensional en estrella con ETL automatizado, y un tablero analítico gerencial con reportes ejecutivos protegidos.

---

## 2. Matriz de Requisitos Funcionales Canónicos (RF01 a RF08)

| Requisito Funcional Canónico                   | Alcance Comprometido                                                                                                                 | Componentes Implementados                                                             | Pruebas Asociadas                                                                                | Estado                                   |
| :--------------------------------------------- | :----------------------------------------------------------------------------------------------------------------------------------- | :------------------------------------------------------------------------------------ | :----------------------------------------------------------------------------------------------- | :--------------------------------------- |
| **RF01: Autenticación y Tres Perfiles**        | Autenticación con Bcrypt, 3 perfiles (Admin, Responsable, Consulta), control activo en sesión y protección del último administrador. | `LoginController`, `VerificarUsuarioActivo`, `UsuarioService`, `ProyectoPolicy`.      | `GateG1AutenticacionTest`, `ConcurrenciaBloqueosTest`. Sesión revocada y protección concurrente. | **Implementado; ver validación vigente** |
| **RF02: Proyectos Únicos y Cartera**           | Catálogo de 10 proyectos canónicos (S/ 8,623,550.00), código PGD único, campos restringidos a Admin.                                 | `ProyectoController`, `UpdateProyectoRequest`, `ProyectoPolicy`, `proyecto`.          | `CarteraProyectoTest`. Permisos de campos por perfil y concurrencia verificados.                 | **Implementado; ver validación vigente** |
| **RF03: Hitos y Avances Trazables**            | 26 hitos, 24 cortes, reglas R1 (límite monto), R2 (monotonía gasto), R3 (cierre con 100% hitos LB) y justificación sensible.         | `HitoController`, `AvancePresupuestalController`, `sp_validar_avance`, `trg_hito_bu`. | `HitoYCierreProyectoTest`, `AvancePresupuestalTest`, `ConcurrenciaBloqueosTest`.                 | **Implementado; ver validación vigente** |
| **RF04: Cuatro Indicadores Canónicos**         | Avance hitos LB, % proyectos retraso, desviación presupuestal, snapshot consistente y semántica «Sin datos» vs «No aplica».          | `IndicadoresService`, `IndicadorController`, `v_indicadores_proyecto`.                | `IndicadoresMotorTest` (oráculo independiente al 30/06/2024, tolerancia cero).                   | **Implementado; ver validación vigente** |
| **RF05: Filtros Compartidos y Data Warehouse** | Filtrado homogéneo (estado, responsable, objetivo, búsqueda, fechas) en OLTP y Data Mart (ETL con mutex y atomicidad).               | `EtlService`, `TableroController`, `dw_seguimiento_gd`, `sp_etl_cargar`.              | `DataWarehouseEtlTest`, `TableroGerencialTest`. Paridad de filtros y KPIs entre OLTP y DW.       | **Implementado; ver validación vigente** |
| **RF06: Reporte Gerencial Exportable**         | Reporte A4/PDF imprimible y exportación CSV con UTF-8 BOM mitigado contra CSV Injection y paridad contextual.                        | `ReporteController`, `imprimir.blade.php`, exportación CSV.                           | `GateG3ReportesExportacionTest`. Verificación de sanitización y contexto filtrado.               | **Implementado; ver validación vigente** |
| **RF07: Importación Masiva CSV (Opcional)**    | Carga masiva de proyectos e hitos desde hoja de cálculo o CSV.                                                                       | Declarada formalmente **Omitida por alcance** según la Decisión 1 del proyecto.       | No aplica por alcance del núcleo académico.                                                      | **Omitido (Permitido)**                  |
| **RF08: Alertas Tempranas y Calidad**          | Monitoreo de hitos vencidos sin cierre, sobregastos y desvíos de línea base en tiempo real.                                          | `v_alertas`, `v_calidad_datos`, `TableroController`.                                  | Verificado en `DataWarehouseEtlTest` y `TableroGerencialTest`.                                   | **Implementado; ver validación vigente** |

---

## 3. Balance de Esfuerzo Académico: Estimación del Plan vs. Horas Registradas

De acuerdo con el documento de arquitectura y el contrato de trabajo académico, el proyecto se estructuró para un equipo estudiantil universitario con un presupuesto teórico inicial de **120 horas persona** a costo de S/ 0.00.

El plan académico estimó 120 horas persona. No existe un registro de tiempos medidos que permita informar horas efectivas ni calcular una desviación real. Se retira la cifra de 114 horas y la afirmación de conclusión dentro del plazo.

---

## 4. Alcance Complementario Incorporado y Omisiones Deliberadas

### 4.1 Alcance Complementario Incorporado

1. **Control de Concurrencia Optimista (`control_revision`):** Mecanismo de serialización global para prevenir colisiones de escritura entre usuarios concurrentes.
2. **Motor Dual de Indicadores Temporales:** Soporte simultáneo para la modalidad de comité (`CORTE_COMUN`) y supervisión operativa (`ULTIMO_REPORTE_POR_PROYECTO`).
3. **Mitigación Reforzada contra Inyección CSV:** Algoritmo que antepone `'` a cadenas que inician con `=`, `+`, `-`, `@`, protegiendo valores numéricos legítimos como importes negativos o porcentajes (`-6.40 %`).
4. **Semáforo de Calidad y Alertas de Cronograma (RF08):** Detección proactiva de proyectos con hitos de línea base vencidos o desfase financiero en tiempo real.
5. **Autoservicio Web de Cambio de Contraseña (`/perfil/clave`) y Sonda Pública Mínima (`/salud`):** Renovación autenticada de clave privada (12 a 72 bytes, bloqueo de claves públicas legadas y auditoría transaccional) y verificación de salud sin exponer versiones ni puertos internos.
6. **Programación Semanal Automatizada del ETL (`etl:cargar`):** Sincronización periódica registrada en `sistema/routes/console.php` con prevención de solapamiento (`withoutOverlapping()`).
7. **Modelado de Procesos BPMN 2.0 (`docs/procesos_bpmn/`):** Diagramación formal AS-IS y TO-BE de los flujos operativos y analíticos del SIG.
8. **Extensión Controlada de Intercambio de Datos por Lotes con Odoo 16 (`abastecimiento_compra`):** Formalizada en [`docs/decisiones/2026-11-24_extension_intercambio_odoo.md`](./decisiones/2026-11-24_extension_intercambio_odoo.md) para ejemplificar el Capítulo 9 de Laudon, Laudon y Traver (18.ª ed.) mediante importación CSV idempotente de órdenes y recepciones sobre `PROYGD-005`, sin alterar las 6 tablas canónicas ni presentar al SIG como un ERP en tiempo real.

### 4.2 Omisiones Deliberadas y Delimitación de Fronteras

- **Etapa 15 (Importación Masiva de Cartera vía CSV):** Declarada formalmente **fuera de alcance** para el catálogo maestro de proyectos, dado que la cartera PGD 2023–2026 comprende exactamente 10 proyectos canónicos. (La importación CSV implementada en `/abastecimiento/importar` opera únicamente sobre la tabla técnica aislada `abastecimiento_compra` conforme a la decisión del 24/11/2026).
- **Frontera frente a ERP completo (Cap. 9) y Comercio Electrónico (Cap. 10):** Tal como se detalla en [`docs/alineamiento_laudon_cap01_10.md`](./alineamiento_laudon_cap01_10.md), SIGELECTRO supervisa proyectos estratégicos de TI pero no opera como SAP/ERP, módulo general de suministros, facturación comercial ni plataforma de comercio electrónico.

### 4.3 Framework actualizado (A14)

Laravel 12.71.0 está instalado y verificado con PHP 8.3.28, PHPUnit 11.5.57 y el lockfile actualizado. Véase la decisión técnica del 04/11/2026.

---

## 5. Estado de Verificación del Núcleo y de Power BI

De acuerdo con la **Decisión 7** del contrato maestro y las validaciones vigentes:

> [!IMPORTANT]
> **ESTADO DE ENTREGA ACTUALIZADO:**  
> **«Núcleo OLTP/DW, seguridad, restauración aislada, extensión Odoo 16, Asistente RAG y Agente de Decisión (DSS) verificados con 174 pruebas automatizadas aprobadas (1,729 aserciones); artefactos de Power BI completos en `docs/bi/` incluyendo `Tablero_Seguimiento_GD_FINAL.pbix`»**

### Detalle del Componente Power BI

- **Herramienta:** Microsoft Power BI Desktop (`PBIDesktop.exe`).
- **Artefactos Entregados en `docs/bi/`:**
  - Archivo de tablero nativo `Tablero_Seguimiento_GD_FINAL.pbix`.
  - 8 archivos CSV exportados con BOM UTF-8 en `docs/bi/datos/` (`dim_tiempo.csv`, `dim_proyecto.csv`, `dim_objetivo.csv`, `dim_responsable.csv`, `fact_avance_proyecto.csv`, `fact_hito.csv`, `v_alertas.csv`, `v_calidad_datos.csv`) y manifiesto JSON (`manifiesto_extraccion.json`).
  - Modelo dimensional documentado (`modelo_power_bi.md`), catálogo de medidas DAX dinámicas (`medidas_DAX.txt`) y guía de conciliación (`guia_power_bi.md`).

---

## 6. Sustentación Final y Siguiente Incremento (Capítulo 11 — RAG)

El sustento teórico de los capítulos 1 al 10 de la 18.ª edición de Laudon, Laudon y Traver queda consolidado en [`docs/alineamiento_laudon_cap01_10.md`](./alineamiento_laudon_cap01_10.md), priorizando los **capítulos 2, 6 y 8** como eje técnico central apoyados por los **capítulos 1, 3 y 4**.

La redacción definitiva del **guion de exposición** y del **banco de preguntas técnicas** se elaborará al cierre integral del desarrollo, una vez incorporado el módulo complementario de **Generación Aumentada por Recuperación (RAG — Capítulo 11)**.
