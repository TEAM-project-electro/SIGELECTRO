# Procesos del SIG Electrocentro — BPMN 2.0

Se identificaron **13 procesos y una vista integral**, modelados a partir de las rutas, Policies, controladores, servicios y reglas SQL implementados. Son modelos **AS-IS del sistema académico**, no procedimientos institucionales aprobados de Electrocentro. No se modificaron la aplicación, los datos, el frontend ni Power BI.

## Entregables

- **Archivo único completo:** [SIG_Electrocentro_Completo.bpmn](SIG_Electrocentro_Completo.bpmn). Contiene los 14 procesos en un único lienzo editable, distribuidos en dos columnas. No contiene imágenes. En Bizagi: **Exportar/Importar → Importar → BPMN**, seleccionar este archivo y luego guardar como modelo nativo `.bpm` si se desea. Validación del consolidado: [validacion_archivo_completo.json](validacion_archivo_completo.json). No se ha fingido un archivo nativo cambiando la extensión.

- `diagramas/`: **14 archivos `.bpmn` editables**, uno por diagrama, con XML BPMN 2.0 y coordenadas BPMNDI. Estos son los archivos que se importan en Bizagi Modeler o Bonita Studio.
- `vistas/`: vistas de lectura **SVG y PNG** de los mismos diagramas. No sustituyen los modelos editables.
- [indice.html](indice.html): acceso local a las vistas y a cada archivo BPMN.
- [catalogo_procesos.json](catalogo_procesos.json): objetivo, actores, clasificación y archivos fuente de cada proceso.
- [validacion_bpmn.json](validacion_bpmn.json): resultados de validación y hash SHA-256 de cada modelo.
- `esquemas/`: los cinco XSD oficiales de OMG empleados para la validación.
- `SIG_Electrocentro_BPMN_2_0.zip`: paquete de distribución. Se descomprime antes de importar los `.bpmn`.

## Diagramas identificados

| ID | Diagrama | Tipo y actores | Resultado |
|---|---|---|---|
| P00 | [Seguimiento gerencial integral](diagramas/P00_seguimiento_integral.bpmn) | Contexto: gestor autorizado y sistema | Relaciona cartera, hitos, presupuesto, consulta y solicitud de cierre. |
| P01 | [Acceso y cierre de sesión](diagramas/P01_acceso.bpmn) | Soporte: los tres perfiles y sistema | Sesión autenticada o acceso rechazado/limitado; cierre de sesión. |
| P02 | [Registro y edición de proyectos](diagramas/P02_cartera.bpmn) | Operación: Administrador o Responsable titular, según acción | Ficha validada, actualizada y auditada, sin sobrescritura obsoleta. |
| P03 | [Gestión de hitos y línea base](diagramas/P03_hitos_linea_base.bpmn) | Operación: Administrador/Responsable titular y sistema | Hito registrado o actualizado; cambio de línea base justificado. |
| P04 | [Registro y edición de cortes presupuestales](diagramas/P04_avance_presupuestal.bpmn) | Operación: Administrador/Responsable titular y sistema | Seguimiento financiero válido según R1, R2 y unicidad. |
| P05 | [Cierre formal de proyecto](diagramas/P05_cierre_proyecto.bpmn) | Operación: Administrador/Responsable titular y sistema | Cierre cuando existe línea base y todos sus hitos tienen cierre. |
| P06 | [Reapertura de proyecto](diagramas/P06_reapertura.bpmn) | Operación: Administrador y sistema | Proyecto vuelve a En curso con motivo auditado. |
| P07 | [Consulta de cartera e indicadores](diagramas/P07_consulta_indicadores.bpmn) | Operación gerencial: cualquier perfil y sistema | KPIs con fecha, filtros, origen, revisión y cobertura. |
| P08 | [Generación de reportes](diagramas/P08_reportes.bpmn) | Operación gerencial: cualquier perfil y sistema | CSV protegido o vista imprimible con el contexto solicitado. |
| P09 | [Actualización del DW mediante ETL](diagramas/P09_actualizacion_dw.bpmn) | Soporte analítico: Administrador y sistema | Carga atómica publicada o rollback con fallo informado. |
| P10 | [Administración de usuarios y perfiles](diagramas/P10_administracion_usuarios.bpmn) | Soporte: Administrador y sistema | Usuario/perfil/estado actualizado; último administrador protegido. |
| P11 | [Administración del catálogo de objetivos](diagramas/P11_catalogo_objetivos.bpmn) | Soporte: Administrador y sistema | Objetivo creado/editado, con revisión y auditoría. |
| P12 | [Consulta de auditoría](diagramas/P12_consulta_auditoria.bpmn) | Control: Administrador y sistema | Consulta de actor, fecha, cambios y motivos; sin alterar eventos. |
| P13 | [Respaldo y verificación de recuperación](diagramas/P13_respaldo_restauracion.bpmn) | Soporte técnico: operador local y herramientas | Respaldo restaurado en copia, con permisos y recorrido verificados. |

Para una sustentación centrada en negocio, empezar por **P00, P02, P03, P04, P05, P07 y P08**. Los demás documentan soporte y controles. P00 es una visión de relación: no obliga a ejecutar todas las operaciones en cada visita y no reemplaza los diagramas de detalle.

## Convenciones BPMN utilizadas

- Un **pool** por proceso: SIG Electrocentro. Los carriles separan la intervención humana de las tareas del sistema dentro del mismo ámbito del proceso. No se dibujan flujos de mensaje entre carriles.
- Eventos de inicio y fin simples; tareas de usuario para captura/consulta; tareas de servicio para operaciones automáticas; tareas manuales para la operación técnica local.
- Compuertas exclusivas **XOR**, rutas etiquetadas y salida predeterminada en cada decisión. Las condiciones son descriptivas; no son código ejecutable de negocio de Bonita.
- Flujos de secuencia dentro del pool, con coordenadas y conectores ortogonales BPMNDI.
- Cada escritura agrupa negocio, auditoría, actividad y revisión dentro de la **misma transacción**. No se dibuja una auditoría manual posterior al guardado ni una aprobación inexistente.
- Las excepciones transaccionales se representan como resultado de la tarea «intentar/guardar» y una decisión de confirmación. En P09 el resultado fallido resume el handler SQL: puede originarse en cualquiera de las operaciones de carga, no únicamente en la última.
- Los procesos usan `isExecutable="false"`: son documentación editable y no un despliegue listo para ejecutar en un motor BPM. Para automatizarlos en Bonita harían falta actores, formularios, variables, contratos y conectores específicos.

## Reglas y delimitaciones del sistema

1. El Responsable registra seguimiento de sus proyectos; no modifica presupuesto total ni asignaciones reservadas al Administrador. Consulta no escribe.
2. Hitos y cortes se gestionan con proyecto no cerrado. Edición obsoleta o pérdida de autorización exige rechazo y revisión; no se inventa una aprobación humana adicional.
3. R1: planificado y gasto no negativos ni superiores al monto; R2: gasto acumulado no decreciente contra cortes anterior y posterior. Gasto mayor al plan puede ser válido respecto del monto.
4. P05 refleja la regla de cierre implementada: al menos un hito en línea base y ninguno sin fecha de cierre. No se agrega validación institucional o una firma no implementada.
5. Reapertura, reducción de monto, cambio de línea base y desactivación tienen motivo cuando corresponde. No se incluyen hashes ni credenciales en los diagramas.
6. En corte común, la falta de corte financiero exacto no oculta el proyecto ni impide evaluar sus hitos. En último reporte se usa el máximo corte por proyecto que no supera la fecha límite. Se conserva «Sin datos» / «No aplica» y cobertura.
7. P09 documenta el DW del núcleo, **sin trabajo sobre Power BI**. No hay un evento periódico de ETL porque no se comprobó un planificador implementado.
8. P13 es operación técnica local, no un cuarto perfil web. El operador ejecuta explícitamente las pruebas después de restaurar; el script de restauración no ejecuta por sí solo toda la suite.
9. No se incluyen importación CSV opcional, SAP real, facturación, clientes, operación eléctrica ni aprobaciones de comités. La baja física excepcional de proyectos/hijos no se presenta como flujo habitual de cartera.

## Importar en Bizagi Modeler

1. Descomprimir el paquete.
2. Abrir **Bizagi Modeler**.
3. Ir a **Export/Import → Import → BPMN** (el texto puede aparecer traducido).
4. Seleccionar uno de los archivos de `diagramas/`, por ejemplo `P00_seguimiento_integral.bpmn`.
5. Revisar el zoom y guardar el modelo en el formato nativo de Bizagi si se desea. Repetir para los diagramas de detalle.

Bizagi documenta la importación de XML BPMN 2.0 y advierte que la ausencia de coordenadas puede afectar la disposición. Estos archivos incluyen BPMNDI. Fuente: [Import a diagram from BPMN — Bizagi](https://help.bizagi.com/platform/en/import_diagram_from_bpmn.htm).

## Importar en Bonita Studio

1. Descomprimir el paquete y abrir un proyecto en Bonita Studio.
2. En el explorador, pulsar con el botón derecho sobre **Process Diagram**.
3. Elegir **Import a BPMN 2.0 file** y seleccionar el archivo `.bpmn`.
4. Revisar el diagrama importado y guardarlo. No intentar desplegarlo como aplicación ejecutable sin configurar sus elementos de automatización.

Bonita documenta `.bpmn` como formato adecuado de intercambio y puede ajustar tamaños o convertir elementos no soportados. Por ello se utilizaron elementos básicos compartidos. Fuente: [Import and export a process — Bonita](https://documentation.bonitasoft.com/bonita/latest/runtime/import-and-export-a-process).

## Validación y límite de compatibilidad

Los **14 archivos** pasaron validación XSD con [BPMN 2.0.2 de OMG](https://www.omg.org/spec/BPMN/2.0.2/), utilizando los cinco esquemas oficiales de `https://www.omg.org/spec/BPMN/20100501/`. Se comprobaron también IDs únicos, referencias de nodos/flujos/carriles, existencia de inicio y fin, alcanzabilidad, caminos hacia fin, decisiones con salida predeterminada y coordenadas de todos los nodos/conectores. Se revisaron visualmente muestras de las vistas de cartera y ETL.

**Compatibilidad prevista por el estándar y el subconjunto utilizado; la importación nativa en Bizagi Modeler/Bonita no fue ejecutada en esta sesión.** No se encontró una instalación de esos modeladores en las carpetas habituales consultadas. La validación XSD no acredita por sí sola una prueba de apertura en una versión concreta. El informe JSON lo declara expresamente.

Reproducción desde la raíz del repositorio, con Python que disponga de `lxml` y `Pillow`:

```powershell
python scripts/generar_procesos_bpmn.py
```

El generador escribe únicamente `docs/procesos_bpmn/`. No inicia Laravel, no consulta ni modifica MariaDB y no cambia los archivos del frontend. Los fuentes asociados a cada proceso están documentados dentro del XML y en el catálogo JSON.
