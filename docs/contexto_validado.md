# Contexto Validado del SIG de Electrocentro

> Documento derivado del capítulo «Contexto de referencia» del contrato [PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md](../PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md) (Decisión 10). En caso de discrepancia, prevalece el contrato principal.

## Propósito y límites

Aplicación académica local para supervisar proyectos de gobierno digital de Electrocentro S. A. La denominación de los dos documentos es S. A.; no cambiarla a S. A. C. sin fuente. Curso Sistema de Información Gerencial, Universidad Nacional de Huancavelica. Plazo del plan: tres semanas, 15 jornadas, alrededor de 120 horas persona, presupuesto académico S/ 0,00. Los costos comerciales de herramientas que el equipo ya usa no deben confundirse con los montos de los proyectos del PGD.

Se conserva el seguimiento de proyectos como dominio. Quedan fuera facturación eléctrica, suministros, clientes, recibos, interrupciones, SAP real, puesta en producción y analítica predictiva. Los metadatos de cartera se toman de los adjuntos; hitos, responsables y finanzas son demostrativos.

## Requisitos

| ID | Requisito | Prioridad |
| --- | --- | --- |
| RF01 | Autenticación y tres perfiles con acciones autorizadas. | Esencial |
| RF02 | Registrar y editar proyectos con identificador único. | Esencial |
| RF03 | Hitos y avances con fecha, autor y trazabilidad. | Esencial |
| RF04 | Cuatro indicadores verificables. | Esencial |
| RF05 | Filtros de estado, responsable y corte compartidos por tablero y detalle. | Esencial |
| RF06 | Reporte exportable con corte y datos consultados. | Esencial |
| RF07 | Importación CSV con errores por fila y sin duplicados. | Complementario |
| RF08 | Alertas de actualización con motivo y registro de origen. | Complementario |

Objetivo adicional de filtro: código OGD. Calidad: validaciones, permisos, copia restaurable, tablero en hasta tres segundos en el equipo de ensayo con diez proyectos; tolerancia de indicadores de 0,01 puntos porcentuales y conteos exactos. La ayuda de interpretación de una cifra no se considera una función opcional de alertas.

## Modelo operativo original

| Tabla | Columnas originales |
| --- | --- |
| objetivo_gd | id_objetivo, codigo, descripcion |
| responsable | id_responsable, nombre, area, perfil, usuario, clave_hash, activo |
| proyecto | id_proyecto, codigo_pgd, nombre, monto, anio_inicio, anio_fin, estado, id_objetivo, id_responsable, fuente, fecha_actualizacion |
| hito | id_hito, id_proyecto, descripcion, fecha_prevista, fecha_cierre, evidencia, en_linea_base |
| avance_presupuestal | id_avance, id_proyecto, fecha_reporte, presupuesto_planificado, gasto_acumulado |
| bitacora_cambio | id_cambio, tabla_afectada, id_registro, campo, valor_anterior, valor_nuevo, fecha_hora, id_responsable |

Total original: seis tablas, 41 columnas y cinco llaves foráneas. Cada tabla tiene PK entera autoincremental. UQ: objetivo_gd.codigo, responsable.usuario, proyecto.codigo_pgd y avance_presupuestal(id_proyecto, fecha_reporte). Todas las FK tienen ON DELETE RESTRICT y ON UPDATE CASCADE. Motor InnoDB, utf8mb4, nombres físicos en minúsculas. Los montos son DECIMAL(12,2), en soles. Estados exactos: Pendiente, En curso, Cerrado. Perfiles exactos: Administrador, Responsable, Consulta. Campos opcionales originales: hito.fecha_cierre, hito.evidencia y los dos valores de bitacora_cambio.

Longitudes según informe: objetivo codigo 10 y descripcion 200; responsable nombre 100, area 80, perfil 20, usuario 30, clave_hash 255; proyecto codigo 10, nombre 150, estado 20, fuente 100; hito descripcion 150 y evidencia 200; bitácora tabla 30, campo 40, valores 200. No añadir columnas Eloquent timestamps o una tabla users por inercia. Las tablas técnicas justificadas (control_revision, bitacora_motivo) deben documentarse por separado del modelo académico original.

## Reglas originales y refuerzos necesarios

R1: planificado y gasto acumulado no negativos y no superiores al monto total del proyecto. R2: el gasto no disminuye cronológicamente; al insertar o editar entre cortes se compara con el anterior y el posterior. R3: un proyecto solo se cierra si todos los hitos de línea base están cerrados. R4: cada cambio clave se registra mediante contexto de autor SET @id_responsable en la conexión de escritura y triggers.

Reforzar R3 para cero hitos de línea base, reaperturas y modificaciones de hitos de proyectos cerrados. Reforzar R1 al reducir el monto de un proyecto. Completar auditoría de altas y cambios relevantes; excluir contraseñas y hashes. La auditoría original no demuestra cobertura de todos los campos ni de todos los eventos. El actor autenticado se obtiene de la sesión, nunca del formulario.

## Perfiles de aplicación

Administrador: gestiona usuarios y catálogos, crea y modifica proyectos, asigna responsables, registra seguimiento y administra ETL. Responsable: consulta la cartera y el tablero; crea y modifica hitos y avances y cambia el estado solamente de sus proyectos. No administra usuarios, objetivos, presupuesto total ni asignaciones. Consulta: consulta y exporta, sin modificar. Los tres perfiles ven la cartera, conforme al informe; el control por propietario es obligatorio en las escrituras del Responsable. Aplicar también a rutas directas e identificadores anidados.

## Cartera de referencia

| Código | Nombre del proyecto según el plan | OGD | Monto PEN | Años |
| --- | --- | --- | --- | --- |
| 005 | Equipos de ciberseguridad | OGD 04 | 500000.00 | 2023 a 2024 |
| 010 | Servicio Software Factory (FONAFE 5) | OGD 05 | 2383550.00 | 2023 a 2026 |
| 011 | Enlaces de fibra óptica en subestaciones | OGD 03 | 2500000.00 | 2023 a 2025 |
| 013 | Implementación de SAP S/4 HANA | OGD 06 | 2000000.00 | 2023 a 2026 |
| 014 | Actualización del marco de gobierno e incorporación del Comité de Gobierno Digital | OGD 02 | 120000.00 | 2023 |
| 017 | Fortalecimiento de las competencias digitales del personal | OGD 01 | 300000.00 | 2023 a 2026 |
| 020 | Transición al protocolo IPv6 | OGD 03 | 400000.00 | 2023 a 2025 |
| 022 | Publicación de datos abiertos en la Plataforma Nacional de Datos Abiertos | OGD 05 | 108000.00 | 2023 |
| 023 | Implementación y certificación del sistema de gestión de seguridad de la información (NTP ISO/IEC 27001:2014) | OGD 04 | 212000.00 | 2023 |
| 024 | Mesa de partes digital integrada al sistema de gestión documental | OGD 05 | 100000.00 | 2023 |

Total: S/ 8623550.00. La cartera completa citada en el plan tiene 19 proyectos por S/ 10819550.00; no inventar los nueve proyectos restantes. Mantener códigos como texto con ceros iniciales. La cartera de diez cubre seis objetivos. El informe abrevia algunos nombres; usar los del plan y registrar esa precedencia.

El informe describe una muestra con seis objetivos, tres responsables, diez proyectos, 24 hitos y 24 avances, pero no presenta todas las filas. Ana Quispe, Luis Rojas y Marta Salas son responsables ficticios. Agregar un usuario de Consulta para probar los tres perfiles si se requiere; registrar el aumento de usuarios respecto de la muestra original.

## Indicadores y tiempo

Avance = 100 × suma de hitos de línea base concluidos al corte / suma de hitos de línea base. Retraso = 100 × proyectos activos evaluables con al menos un hito de línea base vencido / proyectos activos evaluables. Activos: Pendiente o En curso. Hito vencido: fecha_prevista < fecha de evaluación y (fecha_cierre nula o fecha_cierre > fecha de evaluación). Concluido: fecha_cierre no nula y <= fecha de evaluación. No usar el cierre actual para dar por cerrado un hito en una fecha anterior.

Desviación = 100 × (suma de gasto − suma de presupuesto planificado) / suma de presupuesto planificado para registros elegibles del mismo modo temporal. No promediar porcentajes. No sumar presupuestos o gastos acumulados de fechas distintas del mismo proyecto. Proyectos por estado cuenta proyectos distintos de la cartera filtrada. Denominador cero: No aplica; falta de información: Sin datos. Informar numeradores, denominadores, elegibles y excluidos. No presentar ausencia de datos como cero.

Contrato recomendado para resolver la contradicción documental:

- CORTE_COMUN, predeterminado: evaluar hitos al día C elegido; para el indicador financiero exigir un avance con fecha_reporte = C, y marcar como sin corte financiero exacto los demás. Mostrar cobertura financiera. No arrastrar ni interpolar importes de forma silenciosa.
- ULTIMO_REPORTE_POR_PROYECTO, compatibilidad: seleccionar el máximo fecha_reporte <= fecha límite C de cada proyecto; sin límite, su máximo disponible. Evaluar sus hitos a ese reporte. Mostrar por proyecto la fecha usada, el rango de fechas y que la comparación usa reportes de fechas diferentes. Los proyectos sin reporte no son evaluables para esos indicadores. Este es el modo de las cifras documentales; no llamarlo corte homogéneo.
- Ambos modos usan estado, propietario y línea base vigentes porque así funciona la fuente. Son evaluaciones de fechas usando los datos vigentes, no historia inmutable. No reconstruir una dimensión tipo 2 sin registros suficientes ni ampliar el alcance por defecto.

En el modo común, los proyectos sin reporte financiero pueden conservar indicadores de hitos si tienen línea base evaluable. Las coberturas de cada KPI deben explicarse por separado. El estado se muestra expresamente como vigente en los dos modos. Cada consulta transporta modo, corte o límite, filtros, versión de datos, origen operativo o DW y fecha de emisión.

## DW y BI del informe

Base dw_seguimiento_gd. Dimensiones: dim_tiempo, dim_proyecto, dim_objetivo, dim_responsable. Hechos: fact_avance_proyecto, una fila por proyecto y fecha_reporte; fact_hito, una fila por hito. Dimensiones tipo 1. Calendario original 2023 a 2027, ampliable según fechas reales de la fuente. Control etl_control, procedimiento sp_etl_cargar y calendario sp_cargar_dim_tiempo. Carga completa por el volumen pequeño; semiaditividad de acumulados.

Seis vistas: v_kpi_cartera, v_kpi_proyecto, v_proyectos_por_estado, v_evolucion_por_corte, v_alertas, v_calidad_datos. Las vistas sin parámetros sirven para las modalidades que documenten; las consultas con corte variable necesitan un mecanismo parametrizado. fact_hito no almacena múltiples estados históricos: recalcular con sus fechas o consultar fact_avance_proyecto según el contrato. No unir hechos entre sí antes de agregarlos.

BI: tres páginas Resumen, Hitos y Evolución. Fuente preferida para portabilidad: archivos exportados del DW con manifiesto; conexión directa solo si se comprueba el conector y el entorno. No se recibió un archivo PBIX ni las medidas DAX mencionadas en el informe. El propio informe aclara que no ejecutó las medidas DAX en Power BI.

## Referencias de control condicionadas

Solo con las filas originales y el modo documental: 17/24 hitos = 70.8333… %, 3/7 activos retrasados = 42.8571… %, 7 En curso y 3 Cerrado. Desviación aproximadamente −6.5 %; falta en el texto el desglose completo para exigir más precisión. Con límite 30/06/2024 el informe cita 58.3 %, 14.3 % y aproximadamente −7.2 %. No convertir valores redondeados a una décima en pruebas con tolerancia de 0.01 puntos porcentuales sin obtener los importes originales.

## Material disponible y tratamiento de faltantes

Se recibieron únicamente el plan y el informe DOCX. Las extracciones TXT son auxiliares de lectura; los DOCX prevalecen si una extracción omite contexto. No se adjuntaron los SQL enumerados, el Excel, un PBIX ni medidas DAX. Reconstruir objetos ejecutables como una implementación nueva derivada de los documentos. Registrar las suposiciones y validar el resultado en el motor elegido. No afirmar ejecución previa de archivos no recibidos.
