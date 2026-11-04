# Guía de Construcción y Verificación en Power BI Desktop - SIG Electrocentro

Fecha de consolidación: 4 de noviembre de 2026.  
Referencia canónica: [PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md](../../PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md) (Etapa 14).  
Herramienta: **Microsoft Power BI Desktop** (verificado en `C:\Program Files\Microsoft Power BI Desktop\bin\PBIDesktop.exe`).  
Estado: **Preparado y validado analíticamente contra SQL**.

---

## 1. Opciones de Conexión de Datos

Existen dos vías para alimentar Power BI Desktop con los datos del Data Warehouse:

### Vía A: Importación Directa desde Archivos CSV (Recomendada / Portátil)
Todos los datos han sido extraídos de `dw_seguimiento_gd` mediante el comando `php artisan bi:exportar-datos` y residen en `docs/bi/datos/`:
1. Abrir **Power BI Desktop**.
2. En la cinta de opciones, seleccionar **Obtener datos** $\to$ **Texto o CSV**.
3. Importar los siguientes archivos ubicados en `docs/bi/datos/`:
   - `dim_tiempo.csv`
   - `dim_proyecto.csv`
   - `dim_objetivo.csv`
   - `dim_responsable.csv`
   - `fact_avance_proyecto.csv`
   - `fact_hito.csv`
   - `v_alertas.csv`
   - `v_calidad_datos.csv`
4. En la ventana de previsualización, confirmar codificación **65001: Unicode (UTF-8)** y delimitador **Coma**. Hacer clic en **Cargar**.

---

### Vía B: Conexión Directa a MariaDB / MySQL (Base de Datos Viva)
Si se dispone del conector *MySQL Connector/NET* instalado en el sistema:
1. En Power BI Desktop, seleccionar **Obtener datos** $\to$ **Base de datos MySQL**.
2. Servidor: `127.0.0.1:3307`
3. Base de datos: `dw_seguimiento_gd`
4. Modo de conectividad: **Importar**.
5. Credenciales técnicas:
   - Usuario: `bi_lector`
   - Contraseña: `BiLector2026!` (según Decisión 5 y archivo `.env`).
6. Marcar las seis tablas del modelo dimensional (`dim_tiempo`, `dim_proyecto`, `dim_objetivo`, `dim_responsable`, `fact_avance_proyecto`, `fact_hito`) y las vistas `v_alertas` y `v_calidad_datos`.

---

## 2. Configuración del Modelo de Relaciones

Ir a la pestaña **Vista de Modelo** en la barra lateral izquierda de Power BI:
1. Eliminar cualquier relación automática que Power BI haya inferido erróneamente entre tablas de hechos.
2. Establecer manualmente las relaciones según [`docs/bi/modelo_power_bi.md`](./modelo_power_bi.md):
   - `dim_proyecto[sk_proyecto]` (1) $\to$ `fact_avance_proyecto[sk_proyecto]` (*) [Activa, Filtro único]
   - `dim_objetivo[sk_objetivo]` (1) $\to$ `fact_avance_proyecto[sk_objetivo]` (*) [Activa, Filtro único]
   - `dim_responsable[sk_responsable]` (1) $\to$ `fact_avance_proyecto[sk_responsable]` (*) [Activa, Filtro único]
   - `dim_tiempo[sk_tiempo]` (1) $\to$ `fact_avance_proyecto[sk_tiempo]` (*) [Activa, Filtro único]
   - `dim_proyecto[sk_proyecto]` (1) $\to$ `fact_hito[sk_proyecto]` (*) [Activa, Filtro único]
   - `dim_objetivo[sk_objetivo]` (1) $\to$ `fact_hito[sk_objetivo]` (*) [Activa, Filtro único]
   - `dim_responsable[sk_responsable]` (1) $\to$ `fact_hito[sk_responsable]` (*) [Activa, Filtro único]
   - `dim_tiempo[sk_tiempo]` (1) $\to$ `fact_hito[sk_tiempo_previsto]` (*) [Activa, Filtro único]
   - `dim_tiempo[sk_tiempo]` (1) $\to$ `fact_hito[sk_tiempo_cierre]` (*) [Inactiva, Filtro único]
3. Marcar tabla de fechas:
   - Clic derecho en `dim_tiempo` $\to$ **Marcar como tabla de fechas** $\to$ Seleccionar columna `fecha`.
4. Ocultar llaves sustitutas `sk_` e `id_` para no ensuciar la lista de campos.

---

## 3. Creación de Medidas DAX

Crear una tabla vacía llamada `_Medidas` y agregar las expresiones DAX de [`docs/bi/medidas_DAX.txt`](./medidas_DAX.txt):
- `[KPI 1 Avance Hitos LB %]`
- `[KPI 2 Proyectos Retraso %]`
- `[KPI 3 Desviación Presupuestal %]`
- `[Total Proyectos Cartera]`, `[Monto Total Cartera]`, etc.

---

## 4. Diseño de las Tres Páginas del Tablero

### Página 1: Resumen Ejecutivo (Boceto Figura 5 del informe)
1. **Encabezado Institucional:**
   - Título: *SIG Electrocentro S. A. - Seguimiento de Cartera PGD*.
   - Badge: *Datos Demostrativos - Prototipo Académico*.
2. **Cuatro Tarjetas de KPIs:**
   - Tarjeta 1: `[KPI 1 Avance Hitos Formateado]` con subtítulo *"Hitos LB Concluidos"*.
   - Tarjeta 2: `[KPI 2 Proyectos Retraso Formateado]` con color condicional de alerta.
   - Tarjeta 3: `[KPI 3 Desviación Formateada]` con subtítulo *"Gasto vs Plan"*.
   - Tarjeta 4: `[Total Proyectos Cartera]` con monto total.
3. **Filtros Segmentadores:**
   - Segmentador por `dim_proyecto[estado_actual]` (`En curso`, `Pendiente`, `Cerrado`).
   - Segmentador por `dim_responsable[nombre]`.
   - Segmentador por `dim_objetivo[codigo]`.
4. **Tabla de Proyectos:**
   - Columnas: `dim_proyecto[codigo_pgd]`, `dim_proyecto[nombre]`, `dim_responsable[nombre]`, `dim_proyecto[estado_actual]`, `[KPI 1 Avance Hitos Formateado]`, `[KPI 3 Desviación Formateada]`.

### Página 2: Seguimiento de Hitos
1. Gráfico de barras de hitos concluidos vs vencidos por objetivo PGD.
2. Tabla de detalle de `fact_hito` con `descripcion`, `estado_hito`, `dias_desfase`, `tiene_evidencia`.

### Página 3: Evolución Financiera
1. Gráfico de líneas o barras agrupadas: Eje X con `dim_tiempo[fecha]`, valores `[Presupuesto Planificado al Corte]` y `[Gasto Acumulado al Corte]`.
2. Tabla de conciliación histórica por fecha de corte.

---

## 5. Tabla de Conciliación DAX vs SQL (Muestra Canónica)

La siguiente tabla presenta los resultados calculados por las vistas SQL en `dw_seguimiento_gd` como **valores esperados de referencia** para la posterior contrastación interactiva una vez importado el modelo en Microsoft Power BI Desktop. Dado que en el entorno de desarrollo y pruebas automatizadas (CLI/PHPUnit) no se ejecuta el motor interno de evaluación de Power BI Desktop (`PBIDesktop.exe`), la conciliación interactiva DAX queda documentada como **PREPARADA (Valores SQL comprobados / Conciliación DAX pendiente de apertura y verificación interactiva en Desktop)**, separando con rigor metodológico los resultados SQL observados de las lecturas visuales a verificar por el usuario en Power BI Desktop:

| Métrica / Indicador | Valor Esperado SQL (`dw_seguimiento_gd`) | Medida DAX Esperada | Tolerancia Máxima | Estado de Conciliación en Desktop |
| :--- | :---: | :---: | :---: | :---: |
| **Total Proyectos Cartera** | `10` | `10` | 0 diferencias | **PREPARADO (Pendiente en Desktop)** |
| **Monto Total Cartera** | `S/ 8,623,550.00` | `S/ 8,623,550.00` | 0.00 PEN | **PREPARADO (Pendiente en Desktop)** |
| **Presupuesto Planificado Acumulado** | `S/ 6,090,000.00` | `S/ 6,090,000.00` | 0.00 PEN | **PREPARADO (Pendiente en Desktop)** |
| **Gasto Acumulado Ejecutado** | `S/ 5,700,500.00` | `S/ 5,700,500.00` | 0.00 PEN | **PREPARADO (Pendiente en Desktop)** |
| **Diferencia Presupuestal** | `- S/ 389,500.00` | `- S/ 389,500.00` | 0.00 PEN | **PREPARADO (Pendiente en Desktop)** |
| **KPI 3: Desviación Presupuestal (%)** | `-6.40 %` | `-6.40 %` | $\le 0.01$ pp | **PREPARADO (Pendiente en Desktop)** |
| **Total Hitos Línea Base** | `25` | `25` | 0 diferencias | **PREPARADO (Pendiente en Desktop)** |
| **Hitos Línea Base Concluidos** | `17` | `17` | 0 diferencias | **PREPARADO (Pendiente en Desktop)** |
| **KPI 1: Avance de Hitos (%)** | `68.00 %` | `68.00 %` | $\le 0.01$ pp | **PREPARADO (Pendiente en Desktop)** |
| **Proyectos Activos Evaluables** | `7` | `7` | 0 diferencias | **PREPARADO (Pendiente en Desktop)** |
| **Proyectos con Retraso (Conteo)** | `3` | `3` | 0 diferencias | **PREPARADO (Pendiente en Desktop)** |
| **KPI 2: Proyectos con Retraso (%)** | `42.86 %` | `42.86 %` | $\le 0.01$ pp | **PREPARADO (Pendiente en Desktop)** |
| **Proyectos Cerrados** | `3` | `3` | 0 diferencias | **PREPARADO (Pendiente en Desktop)** |
| **Proyectos En Curso** | `7` | `7` | 0 diferencias | **PREPARADO (Pendiente en Desktop)** |

---

## 6. Estado de Verificación de la Etapa 14

- [x] Extracción y exportación limpia de datos en `docs/bi/datos/` (8 archivos CSV UTF-8 con BOM y manifiesto JSON con hashes criptográficos).
- [x] Modelo dimensional estrella y relaciones documentadas en [`docs/bi/modelo_power_bi.md`](./modelo_power_bi.md).
- [x] Catálogo exhaustivo de medidas DAX analíticas completas (modos base, corte común y último reporte) en [`docs/bi/medidas_DAX.txt`](./medidas_DAX.txt).
- [x] Guía metodológica e instructivo de diseño en [`docs/bi/guia_power_bi.md`](./guia_power_bi.md).
- [x] Tabla de valores de referencia calculados mediante SQL en el Data Warehouse para contraste con DAX.
- [x] Disponibilidad del ejecutable Power BI Desktop confirmada en el entorno local (`C:\Program Files\Microsoft Power BI Desktop\bin\PBIDesktop.exe`).
- [ ] Conciliación interactiva de visuales y archivo `.pbix` guardado en sesión de Power BI Desktop (requiere apertura manual por el usuario).
- **Estado formal:** **PREPARADO parcialmente; pendiente de ejecución interactiva y conciliación DAX en Power BI Desktop**.
