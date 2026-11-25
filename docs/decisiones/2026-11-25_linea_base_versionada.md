# Decisión: línea base protegida y versionada

Fecha: 25 de noviembre de 2026. Alcance: prioridad 1 de la auditoría integral del 25/11/2026. Sin cambios en el contrato maestro ni en las 6 tablas operativas canónicas; se agrega una tabla técnica independiente.

## Problema

La corrección P0-1 (migración `2026_11_25_000002`) impidió que un Responsable cambiara directamente la fecha prevista de un hito de línea base. Seguía abierta una evasión: el Responsable podía retirar el hito de la línea base con un motivo cualquiera, cambiar su fecha (ya fuera de la línea base no había bloqueo) y reincorporarlo. Con solo el retiro, el proyecto dejaba de contar como retrasado. En la verificación, el indicador de proyectos con retraso bajó de 3/7 a 2/7 con tres peticiones aceptadas.

## Decisión

1. **Solo un Administrador activo modifica la línea base.** Retirar un hito, incorporarlo, reprogramar su fecha prevista o eliminarlo mientras pertenece a la línea base exige el perfil Administrador y un motivo. Se valida en dos capas:
   - Aplicación: `UpdateHitoRequest` rechaza el cambio de pertenencia y de fecha para otros perfiles; el formulario los muestra bloqueados.
   - Base de datos: `trg_hito_bu` y el nuevo `trg_hito_lb_bd` consultan el perfil del actor de la transacción (`@id_responsable`). Una escritura que salte la interfaz también se rechaza.
2. **Cada cambio queda versionado.** La tabla `hito_linea_base_version` guarda una fila inmutable por alta, incorporación, reprogramación, retiro o eliminación, con la fecha anterior, la nueva, el motivo, el actor y la fecha y hora. La escriben solo los triggers (`trg_hito_lb_ai`, `trg_hito_lb_au`, `trg_hito_lb_ad`); las cuentas de la aplicación solo pueden leerla.
3. **Crear hitos de línea base sigue permitido al Responsable titular**, porque es parte de la planificación (regla vigente cubierta por `HitoYCierreProyectoTest`). El alta queda versionada con su autor.
4. **Transparencia.** La ficha del proyecto muestra, para todos los perfiles, la fecha original de cada hito, cuántas veces se reprogramó y el historial completo de la línea base.

En instalaciones existentes, la migración registra la línea base vigente como versión 1. Los cambios anteriores al versionado siguen disponibles en la bitácora.

## Lo que esta decisión no resuelve

- La aprobación sigue siendo de una sola persona: el Administrador decide y ejecuta. El flujo de solicitud y aprobación con separación de funciones (perfiles PMO y Comité) es la prioridad 2.
- Los indicadores se calculan contra la línea base vigente, que ahora solo cambia un Administrador con motivo. No se calcula todavía un indicador contra la línea base original.

## Verificación

`tests/Feature/LineaBaseVersionadaTest.php` cubre el retiro, la incorporación, la evasión en tres pasos, el bloqueo en la base de datos para actores no administradores, el registro de versiones y su inmutabilidad, y la visualización en la ficha. Tras restaurar respaldos, `scripts/ajustar_permisos.php` reconcilia los permisos de la tabla nueva.
