# Contrato de Indicadores y Tratamiento Temporal

Fecha de consolidación: 3 de noviembre de 2026.
Referencia canónica: [PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md](../PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md).
Estado: Aprobado para implementación y pruebas de paridad (Etapas 01, 10, 11).

---

## 1. Definición Formal de los Cuatro Indicadores

Todos los indicadores se evalúan sobre el universo de proyectos filtrados por los parámetros de consulta (estado, responsable, objetivo) y la fecha de corte seleccionada.

### KPI 1: Avance de Hitos (%)
Mide el grado de cumplimiento de los compromisos registrados en la línea base de los proyectos evaluables.

$$\text{Avance de Hitos (\%)} = 100 \times \frac{\sum \text{hitos de línea base concluidos al corte}}{\sum \text{hitos de línea base de proyectos evaluables}}$$

**Reglas de cálculo:**
- Solo se consideran hitos con `en_linea_base = 1`.
- Un hito está **concluido al corte** si y solo si:
  $$\text{fecha\_cierre IS NOT NULL} \quad \land \quad \text{fecha\_cierre} \le \text{fecha\_evaluacion}$$
- Un hito cerrado con fecha posterior a la fecha de corte se considera **pendiente** a la fecha evaluada.
- Todos los hitos de la línea base tienen igual ponderación.
- **Prohibición:** Está prohibido promediar los porcentajes de avance de cada proyecto para obtener el indicador global; se deben sumar todos los numeradores y denominadores de la cartera antes de dividir.

### KPI 2: Proyectos con Retraso (%)
Mide la proporción de proyectos activos que presentan incumplimiento en su cronograma de línea base.

$$\text{Proyectos con Retraso (\%)} = 100 \times \frac{\text{Proyectos activos evaluables con al menos un hito de línea base vencido}}{\text{Proyectos activos evaluables}}$$

**Reglas de cálculo (Decisión 2):**
- **Proyectos activos:** Aquellos cuyo estado vigente es `Pendiente` o `En curso` (se excluyen los `Cerrado`).
- **Proyectos evaluables:** Proyectos activos que cuentan con al menos un hito en línea base (`en_linea_base = 1`) y una fecha de evaluación válida según la modalidad temporal activa.
- **Hito de línea base vencido:** Hito con `en_linea_base = 1` que cumple:
  $$\text{fecha\_prevista} < \text{fecha\_evaluacion} \quad \land \quad (\text{fecha\_cierre IS NULL} \lor \text{fecha\_cierre} > \text{fecha\_evaluacion})$$
- Si un hito vence exactamente el día de la fecha de evaluación (`fecha_prevista = fecha_evaluacion`), **no** se considera vencido ese día.
- **Hitos fuera de línea base:** Un hito con `en_linea_base = 0` cuya fecha prevista esté vencida **no** hace que el proyecto se considere con retraso (solo se expone como información de calidad).

### KPI 3: Desviación Presupuestal (%)
Mide la variación relativa entre el gasto acumulado ejecutado y el presupuesto planificado acumulado para el corte analizado.

$$\text{Desviación Presupuestal (\%)} = 100 \times \frac{\sum \text{gasto\_acumulado} - \sum \text{presupuesto\_planificado}}{\sum \text{presupuesto\_planificado}}$$

**Reglas de cálculo:**
- Se toma exactamente **un solo registro** de `avance_presupuestal` por proyecto elegible.
- **Prohibición:** Está terminantemente prohibido sumar registros de distintas fechas de un mismo proyecto (son valores acumulados, no mensuales).
- Un valor negativo (ej. $-6.5\%$) indica que el gasto va por debajo de lo planificado a esa fecha. Se presenta como tal y no se asume automáticamente como ahorro definitivo.

### KPI 4: Composición de Proyectos por Estado (Conteo n)
Conteo de proyectos distintos en la cartera filtrada clasificados en sus tres estados vigentes:
$$\text{Proyectos (n)} = \{\text{Pendiente: } n_1, \text{En curso: } n_2, \text{Cerrado: } n_3\}$$

---

## 2. Modalidades Temporales de Consulta

### 2.1 Modalidad Predeterminada: `CORTE_COMUN`
- **Propósito:** Supervisión ejecutiva rigurosa a una fecha homogénea de corte $C$ (ej. `2024-06-30`).
- **Evaluación de Hitos:** Todos los hitos se evalúan respecto a la fecha común $C$.
- **Evaluación Financiera:** Solo son elegibles los proyectos que disponen de un corte presupuestal con `fecha_reporte = C` exacto.
- **Tratamiento de Faltantes:** Si un proyecto no tiene un reporte en la fecha $C$:
  - Sus hitos sí se evalúan al día $C$ si tiene línea base.
  - Se excluye del cálculo de la desviación financiera y se muestra en la tabla con la etiqueta *"Sin corte financiero exacto"*.
  - El reporte informa la cobertura financiera (ej. "8 de 10 proyectos con corte a la fecha").
  - **No se interpolan ni arrastran saldos silenciosamente.**

### 2.2 Modalidad de Compatibilidad: `ULTIMO_REPORTE_POR_PROYECTO`
- **Propósito:** Reproducir la vista agregada documental del informe cuando los proyectos tienen fechas de reporte heterogéneas.
- **Selección de Fecha:** Para cada proyecto se toma su último corte disponible:
  $$\text{fecha\_efectiva}_i = \max(\text{fecha\_reporte}) \le \text{fecha\_limite}$$
  (o el máximo absoluto si no se define fecha límite).
- **Evaluación de Hitos:** Los hitos del proyecto $i$ se evalúan contra su respectiva $\text{fecha\_efectiva}_i$.
- **Transparencia:** La interfaz y el reporte deben mostrar explícitamente el rango de fechas utilizadas (ej. "Reportes entre 2023-12-31 y 2024-09-30") aclarando que no representa un corte homogéneo.

---

## 3. Política de Ceros, Valores Nulos y Cobertura (Decisión 3)

| Situación | Representación en UI | Representación en CSV | Significado |
| --- | --- | --- | --- |
| Denominador $= 0$ con proyectos elegibles | `No aplica` | `No aplica` | Caso donde el presupuesto planificado sumado es 0.00. |
| Ausencia de proyectos elegibles | `Sin datos` | `Sin datos` | Ningún proyecto cumple los filtros o no hay registros para el corte. |
| Numerador $= 0$ con Denominador $> 0$ | `0.00 %` | `0.00` | Cálculo exacto de cero cumplimiento o cero retraso. |
| Filtro sin resultados | Conteos: `0` / KPIs: `Sin datos` | `Sin datos` | Cartera vacía bajo ese criterio de búsqueda. |

---

## 4. Oráculo Independiente de Validación (Caso de Prueba de Referencia)

Para garantizar que el motor de cálculo no presente sesgos, se define este escenario canónico con corte al **30/06/2024** sobre tres proyectos ficticios:

### 4.1 Definición de Proyectos del Caso de Prueba

1. **Proyecto A (`001`, En curso, Monto S/ 500.00):**
   - Hito 1: Línea base = 1, Prevista = `2024-03-15`, Cierre = `2024-03-15` $\to$ **Concluido**.
   - Hito 2: Línea base = 1, Prevista = `2024-05-15`, Cierre = `2024-08-01` $\to$ **Vencido al corte** (cierre posterior a C).
   - Avance al `2024-06-30`: Plan = S/ 100.00, Gasto = S/ 80.00.
2. **Proyecto B (`002`, En curso, Monto S/ 1,000.00):**
   - Hito 1: Línea base = 1, Prevista = `2024-01-10`, Cierre = `2024-01-10` $\to$ **Concluido**.
   - Hito 2: Línea base = 1, Prevista = `2024-02-20`, Cierre = `2024-02-20` $\to$ **Concluido**.
   - Hito 3: Línea base = 1, Prevista = `2024-04-15`, Cierre = `2024-04-10` $\to$ **Concluido anticipado**.
   - Hito 4: Línea base = 1, Prevista = `2024-09-30`, Cierre = NULL $\to$ **En plazo** (vence después de C).
   - Hito 5: **Línea base = 0**, Prevista = `2024-02-01`, Cierre = NULL $\to$ **Hito fuera de línea base vencido** (comprobación de Decisión 2: **NO** debe generar retraso en el proyecto).
   - Avance al `2024-06-30`: Plan = S/ 300.00, Gasto = S/ 330.00.
3. **Proyecto C (`003`, Pendiente, Monto S/ 200.00):**
   - Sin hitos en línea base.
   - Sin avances presupuestales registrados.

### 4.2 Resultados Exactos Esperados del Oráculo

- **Avance de Hitos:**
  $$\frac{\text{Concluidos LB}}{\text{Total LB}} = \frac{1 (\text{A}) + 3 (\text{B})}{2 (\text{A}) + 4 (\text{B})} = \frac{4}{6} = 66.6666\dots\% \implies \mathbf{66.67\%}$$
- **Proyectos con Retraso:**
  - Activos evaluables: A y B (C no tiene hitos de línea base, por tanto no es evaluable). Denominador = 2.
  - Proyectos con hitos de línea base vencidos: Solo A (Hito 2 vencido). Proyecto B tiene su Hito 5 vencido pero tiene `en_linea_base = 0`, por lo que B **no** está retrasado.
  - Resultado:
    $$\frac{1}{2} = 50.0000\% \implies \mathbf{50.00\%}$$
- **Desviación Presupuestal:**
  - Plan total evaluable: $100.00 + 300.00 = 400.00$.
  - Gasto total evaluable: $80.00 + 330.00 = 410.00$.
  - Resultado:
    $$\frac{410.00 - 400.00}{400.00} = \frac{10.00}{400.00} = +2.5000\% \implies \mathbf{+2.50\%}$$
- **Proyectos por Estado:**
  $$\text{En curso: } 2, \quad \text{Pendiente: } 1, \quad \text{Cerrado: } 0$$
- **Coberturas Informadas:**
  - Cobertura de Hitos: 2 de 3 proyectos evaluables (66.7 %).
  - Cobertura Financiera: 2 de 3 proyectos evaluables (66.7 %).
  - El Proyecto C no convierte en cero los indicadores de la cartera ni altera las sumas de A y B.
