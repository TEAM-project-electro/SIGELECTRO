# Protocolo de Desarrollo Compartido

> Documento derivado del capítulo «Protocolo operativo» del contrato [PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md](../PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md) (Decisión 10). En caso de discrepancia, prevalece el contrato principal.

## Contrato de ejecución

El prompt maestro autoriza implementar el alcance local y continuar después de cada etapa verificada. Los archivos de prompts delimitan las etapas; no son comandos especiales del agente o del IDE. Si se envía una etapa individual, el agente se limita a esa etapa. Un plan, estado o matriz vacío es un artefacto de trabajo, no evidencia de que una función ya existe.

En cada etapa: leer contrato y código pertinente, definir la verificación, realizar cambios pequeños, ejecutar comprobaciones, corregir fallos, revisar el diff y actualizar evidencia. Evitar rehacer documentos completos o repetir pruebas de áreas intactas sin una razón. La integración final sí verifica el recorrido completo.

## Controles para continuar

| Control | Etapa | Debe quedar demostrado |
| --- | --- | --- |
| G1 | Después de 05 | Entorno reproducible, base y muestra coherentes, autenticación con tres perfiles y denegación de acciones no autorizadas. |
| G2 | Después de 09 | Alta y edición de cartera, hitos, cortes y cierre; propiedad en escrituras, auditoría atómica y reglas financieras bajo concurrencia. |
| G3 | Después de 13 | KPI con oráculo independiente, ambos modos temporales, paridad operativo DW y reporte que coincide con filtros y revisión del tablero. |
| G4 | Después de 18 | RF esenciales aprobados, compilación correcta, restauración ejecutada, manuales y estado verdadero de BI y complementos. |

Un defecto crítico bloquea las etapas que dependen de él: acceso no autorizado, pérdida de datos, indicador incorrecto o fallo del recorrido principal. Una herramienta externa no disponible se registra como pendiente; no permite declarar comprobada la función que depende de ella. El núcleo no se considera aceptado con pruebas críticas omitidas.

## Entorno real y ubicación de la aplicación

La raíz contiene este contrato, AGENTS.md breve y docs. Si se crea el proyecto desde cero, el instalador genera Laravel en `sistema/`, que inicialmente no existe. Después se trabaja desde `sistema/` para Composer, Artisan y npm, manteniendo docs en la raíz del repositorio. Si existe un proyecto, se conserva su ubicación y arquitectura útil y se documenta la adaptación.

Identificar Windows nativo, WSL u otro entorno y el shell que ejecuta realmente las herramientas. Verificar versiones, ejecutables y extensiones antes de instalar dependencias; no asumir que tener MySQL Workbench significa tener un servidor funcionando. En PowerShell, si npm.ps1 está bloqueado, evaluar el ejecutable npm.cmd disponible antes de cambiar políticas globales. No modificar la seguridad del equipo como atajo.

Configurar MariaDB por host y puerto comprobados, no por un puerto supuesto. Mantener el framework compatible con el PHP detectado. Un servidor ya instalado no se sustituye sin necesidad. No reinstalar dependencias bloqueadas en cada fase ni generar otra APP_KEY al reanudar. Registrar en `docs/entorno.md` un comando comprobado por tarea y el directorio donde se ejecuta.

## Separación de datos y pruebas

Usar `seguimiento_gd` y `dw_seguimiento_gd` para la demostración. Para pruebas, usar nombres diferenciados, por ejemplo `seguimiento_gd_test` y `dw_seguimiento_gd_test`, con cuentas, procedimientos y vistas que apunten exclusivamente a ese par. Los nombres son valores de configuración validados; no proceden de entradas web.

Antes de una operación destructiva verificar host, puerto, nombres de ambos esquemas, identidad del entorno y propiedad del destino. Revisar las referencias internas del ETL y sus definers: un procedimiento instalado en el DW test no debe leer ni escribir en la demo. Las pruebas de fallo de ETL nunca retiran tablas de una fuente compartida. El reset solo se permite en un destino descartable creado para la prueba.

## Prueba de ejecución real

El desarrollo incluye formularios persistentes, reglas del servidor y consultas a la base. El frontend no usa arreglos fijos, localStorage o respuestas simuladas como sustituto de la persistencia. Los únicos datos demostrativos son registros sembrados en la base con procedencia declarada.

Desde G2, repetir un recorrido corto: ingresar, guardar un cambio autorizado, verificar auditoría, consultar el dato persistido y probar una acción denegada. Desde G3, confirmar que ese cambio se refleja en el tablero operativo, que el DW avisa su desactualización y que, tras ETL, ambos concilian. El reporte conserva exactamente el contexto seleccionado.

## Revisión de datos y actualización

Implementar y documentar una revisión central de origen que cubra altas y cambios de proyectos, hitos, cortes y catálogos que afectan cálculos o filtros. Usar `control_revision` como tabla técnica de una fila con contador actualizado dentro de la misma transacción, separada del modelo académico. Definir un orden consistente de bloqueos junto con los proyectos. No basarla únicamente en el máximo fecha_actualizacion ni en leer la bitácora con una carrera de transacciones sin controlar.

Usar ese identificador en cachés, respuestas, exportaciones y `etl_control`. La revisión del DW debe representar la fuente leída de forma coherente por la carga. Si cambia la fuente durante la consulta o entre pantalla y descarga, refrescar o usar un snapshot autorizado. No mezclar lecturas de revisiones distintas y llamarlas un único conjunto.

## Evidencia de cada etapa

`docs/estado_desarrollo.md` usa estados: pendiente, en curso, bloqueada, verificada y omitida con motivo para complementos. Cada fila enlaza entregables y pruebas. `docs/pruebas_ejecutadas.md` registra fecha, entorno, revisión, comando o procedimiento, esperado, obtenido y resultado. No almacenar secretos ni datos de sesión.

`docs/plan_ejecucion.md` conserva decisiones, dependencias y siguiente paso. Antes de cerrar una sesión debe indicar qué archivo se estaba modificando y qué falta ejecutar. Al retomar, inspeccionar el repositorio y la evidencia antes de regenerar componentes. Una nueva conversación no es una nueva aplicación.

## Tratamiento de bloqueos

Resolver errores rutinarios del alcance mediante diagnóstico y cambios verificables. Si fallan permisos o faltan credenciales, explicar el recurso concreto y pedir solo lo indispensable; un rechazo no autoriza desactivar controles ni cambiar de cuenta de manera encubierta. Si falta una herramienta, separar lo implementado de lo ejecutado. Si hay ambigüedad de negocio, revisar primero los valores por defecto y las decisiones conservadas.

No crear un servidor empresarial, publicar, activar servicios pagos ni enviar información a otras personas. Las autorizaciones para el trabajo local no sustituyen las de infraestructura externa.
