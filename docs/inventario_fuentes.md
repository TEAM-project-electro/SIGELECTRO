# Inventario de Fuentes y Trazabilidad Documental

Fecha de generación: 3 de noviembre de 2026.
Referencia contractual: [PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md](../PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md).

## 1. Archivos presentes en `docs/fuentes/`

Los archivos fuente fueron extraídos y verificados localmente:

| Archivo | Formato | Tamaño (bytes) | Hash SHA-256 | Descripción y rol |
| --- | --- | --- | --- | --- |
| `Plan_de_proyecto_SIG_Electrocentro_mejorado.docx` | DOCX | 573,858 | `C836D397062FFBD269C0D4F98A15E97E5BC3B5E039FABF42D62A93DE7E66C023` | Documento canónico para objetivos, alcance (RF01-RF08), EDT, cronograma (120 h), presupuesto (S/ 0.00) y nombres/montos de cartera. |
| `Plan_de_proyecto_SIG_Electrocentro_mejorado.txt` | TXT | 42,394 | `3416B0EC55B461A003BEF9C3D235D8C2018B57E79551F1C2BA10CF18CCE153BE` | Extracción auxiliar del plan para lectura rápida de tablas y párrafos. |
| `BD_Informe_Final.docx` | DOCX | 1,275,918 | `F63DA9A2A41ADB10421088587DE951B850A1BFC5607BE821A852C676851FED94` | Documento canónico para esquema relacional (6 tablas, 41 columnas), diseño físico, reglas R1-R4, DW/ETL y diseño Power BI. |
| `BD_Informe_Final.txt` | TXT | 88,282 | `EC949FFAEB3DEE696FC3780B05C81A582BBCEE40E0B4003CF666108F414703B1` | Extracción auxiliar del informe de base de datos. |

Prevalencia formal: Los archivos DOCX prevalecen sobre las transcripciones TXT en caso de discrepancias o truncamientos.

## 2. Anexos y artefactos citados pero ausentes

En la Tabla 43 y diversas secciones de `BD_Informe_Final.docx` se mencionan scripts y archivos adjuntos que **no están incluidos** en el paquete entregado:

1. **Scripts SQL Operativos:**
   - `01_estructura.sql`
   - `02_triggers.sql`
   - `03_carga_inicial.sql`
   - `04_privilegios.sql`
   - `05_pruebas.sql`
   - `06_consultas.sql`
   - `seguimiento_gd_fisico_completo.sql`
   - `Workbench_importar_modelo.sql`
2. **Scripts SQL DW / ETL:**
   - `dw_completo.sql`
   - `01_dw_estructura.sql`
   - `02_dw_etl.sql`
   - `03_dw_vistas_kpi.sql`
   - `04_dw_cargar_y_validar.sql`
   - `05_dw_programar_carga.sql`
   - `06_dw_usuario_bi.sql`
3. **Archivos de Datos y BI:**
   - `DW_seguimiento_gd_datos.xlsx` y carpeta `csv` con datos exportados.
   - `medidas_DAX.txt` (original citado).
   - Archivo `.pbix` de Power BI (el propio informe reconoce que no se ejecutaron las medidas en Power BI Desktop).
   - Libro Excel de normalización y diccionario de datos (`06_Anexos`).
   - Diagramas originales en draw.io o imágenes PNG.

**Tratamiento de faltantes:**
Conforme a la Decisión 1 del contrato, todos los scripts de base de datos, procedimientos almacenados, vistas, triggers y migraciones se reconstruyen como una implementación derivada ejecutable y verificada en el motor objetivo (MariaDB 10.11), sin asumir existencia previa.

## 3. Registro de discrepancias y decisiones iniciales

1. **Denominación Social:** "Electrocentro S. A." es la razón social explícita en ambos documentos.
2. **Cartera:** 10 proyectos de la muestra que suman exactamente S/ 8,623,550.00. Cubren OGD 01 a OGD 06. Se toman los nombres y montos del Plan frente a las abreviaciones del Informe.
3. **Descripciones OGD Pendientes:** OGD 01 y OGD 06 no tienen descripción textual completa en el texto disponible; se mantienen marcadas como pendientes de confirmación según la página 25 del PGD.
4. **Muestra Completa de Filas:** Se mencionan 24 hitos y 24 cortes en el informe, pero no se publicaron todas las filas de la muestra en el texto. Se construirá una muestra determinista y demostrativa en la Etapa 04.
5. **Decisiones de Arranque:** Se adoptan las decisiones 1 a 12 de [PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md](../PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md).
