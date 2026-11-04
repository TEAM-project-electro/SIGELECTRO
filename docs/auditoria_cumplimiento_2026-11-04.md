# Auditoría de cumplimiento del prompt maestro

Fecha: 4 de noviembre de 2026. Revisión independiente de ChatGPT/Codex en el directorio del proyecto.
Contrato: [PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md](../PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md), revisión 2.

## Dictamen

**No se realizó todo lo exigido por el contrato. Hay una implementación sustancial, pero no está sustentado el cierre al 100 % ni la aprobación de G4. No apto para presentarse como entrega académica terminada conforme al contrato; puede mostrarse como prototipo en desarrollo, declarando sus limitaciones.**

Este informe distingue archivos existentes, comportamiento deducido del código, consultas reales y declaraciones históricas. No modifica aplicación, contrato, configuración, privilegios ni datos. Se añadió únicamente este informe y una comprobación PHP de solo lectura. No se calcula un porcentaje de cumplimiento: contar archivos o tests no mide criterios completos.

## Comprobaciones realizadas y límites

- Inventario de aplicación, documentación, fuentes, pruebas, exportaciones y respaldos; lectura del contrato completo por secciones y contraste de sus aceptaciones con los componentes relevantes.
- Git sin cambios al inicio. Fuentes DOCX y TXT presentes; no se hizo una nueva validación integral del contenido de ambos DOCX contra todas las filas de la muestra. El contrato fue la referencia de esta auditoría.
- PHP CLI comprobado: 8.3.28. `php -l` sobre **69 archivos PHP** de app, routes, database y tests: **0 errores de sintaxis**.
- Ejecutado `php scripts/auditar_lectura.php`, que arranca Laravel y realiza exclusivamente SELECT y SHOW GRANTS. Motor real: **10.11.11-MariaDB**. Base operativa: **10 proyectos**, suma **S/ 8,623,550.00**.
- Identidades reales: mysql = sig_migrador@localhost; auth = sig_auth@localhost; lector = sig_lector@localhost; escritor = sig_escritor@localhost; dw_lector = bi_lector@localhost. Ninguna de estas cinco conexiones usó root.
- SHOW GRANTS: sig_migrador tiene SUPER global y ALL PRIVILEGES sobre esquemas del proyecto; sig_auth tiene SELECT de tabla además del SELECT de columnas; sig_lector tiene lectura por tablas y columnas; bi_lector SELECT/SHOW VIEW. Los ocho triggers operativos existentes tienen DEFINER sig_migrador@localhost.
- **No se ejecutó la suite de integración**: no existe `.env.testing` y phpunit.xml no fuerza esquemas descartables. La configuración activa apunta a seguimiento_gd y hay TRUNCATE en tests. Tampoco se ejecutó restauración, instalación, ETL ni siembra; alterarían el entorno examinado.
- No se repitió compilación frontend ni prueba visual de navegador, teclado, CSV o impresión. Las cifras históricas de 116 tests/755 aserciones y tiempos constan en documentos, pero no fueron reproducidas en esta sesión.
- Las observaciones de restauración derivan del script y de los SQL reales existentes, no de una nueva restauración.

## Hallazgos por prioridad

### A01 — Alta: Responsable puede cambiar presupuesto y asignación

Ubicación: `sistema/app/Policies/ProyectoPolicy.php`, método update; `sistema/app/Http/Requests/Proyecto/UpdateProyectoRequest.php`, rules; `sistema/app/Http/Controllers/ProyectoController.php:150`, update.

La Policy permite editar al titular. El Request acepta monto, id_responsable e id_objetivo sin distinguir perfil, y el controlador los persiste. Escenario: un Responsable envía PUT válido de su proyecto con otro monto o responsable y la revisión esperada correcta. El código permite esas modificaciones, que el contrato reserva al Administrador. No se ejecutó esta mutación durante la auditoría.

Corrección: autorizar campos por perfil en servidor, rechazar cambios prohibidos y probar persistencia y denegación con payload completo.

### A02 — Alta: no hay aislamiento seguro para repetir pruebas

Ubicación: `sistema/phpunit.xml`; `sistema/tests/TestCase.php`; `sistema/tests/Feature/BaseOperativaReglasTest.php:21`; `scripts/probar.ps1`; `.env` activo.

APP_ENV=testing no cambia por sí solo los esquemas. No existe `.env.testing`; el TestCase no remapea conexiones. BaseOperativaReglasTest trunca tablas usando mysql. El comando documentado `php artisan test` puede vaciar la muestra de trabajo. El DW y la restauración de tests además usan nombres de esquema explícitos.

Corrección: configurar y comprobar todas las conexiones de pruebas y referencias SQL; abortar antes de escrituras si no son destinos descartables. Después repetir la batería.

### A03 — Alta: escritura sin el protocolo de bloqueos y sin pruebas concurrentes requeridas

Ubicación: `sistema/app/Services/DatabaseTransactionService.php`; controladores Proyecto, Hito y AvancePresupuestal; pruebas de esas funcionalidades.

El servicio inicia transacción y limpia actor/motivo, pero no bloquea revisión central ni proyecto padre. No aparecen bloqueos FOR UPDATE en el protocolo implementado. La comparación de fecha del proyecto ocurre antes de la transacción y no forma parte de una actualización condicional. Hitos y cortes no llevan una versión esperada. Una segunda edición puede sobrescribir la primera; los SELECT de validación de cierre/R2 no implementan la serialización exigida.

No se localizaron pruebas con dos conexiones reales que fuercen solapamiento para cierre/hito, R2 y último administrador. La prueba de fecha obsoleta de CarteraProyectoTest es secuencial y no las reemplaza. Esto es un incumplimiento comprobable del protocolo; las carreras específicas deben reproducirse en entorno aislado.

Corrección: revisión central → proyecto → hijos, lectura y validación dentro de la transacción, control de versión y tres pruebas deterministas de concurrencia.

### A04 — Alta: auditoría incompleta y revisión sin cubrir cambios

Ubicación: `sistema/database/migrations/2026_11_03_000002_create_triggers_y_procedimientos.php:75`, sp_registrar_cambio, y triggers; catálogo real consultado.

Existen ocho triggers: validaciones BEFORE y auditoría AFTER UPDATE para avance, proyecto e hito. No hay triggers de auditoría de INSERT/DELETE ni de responsable/objetivo_gd. La auditoría de UPDATE no cubre todos los cambios relevantes: por ejemplo evidencia del hito, nombre/objetivo/años/fuente del proyecto. La revisión se incrementa desde sp_registrar_cambio; una operación no cubierta puede no invalidar el DW. Los controladores de altas/bajas no completan esa cobertura mediante llamadas de auditoría explícitas.

Escenario: registrar un hito o cambiar únicamente su evidencia; se persiste sin el evento completo exigido. Corrección: matriz de eventos/campos para los cinco tipos de registro y pruebas de revisión, rollback, altas, bajas y credenciales sin secretos.

### A05 — Alta: cuenta de migración usada durante ejecución y definers incorrectos

Ubicación: `sistema/config/database.php`; configuración activa DB_CONNECTION=mysql / DB_USERNAME=sig_migrador; `IndicadoresService.php`; `routes/web.php`; migraciones SQL y dumps.

Consultas DB::table y /salud usan la conexión predeterminada mysql. Se comprobó que corresponde a sig_migrador con SUPER global y amplios privilegios. El contrato prohíbe usarla en web. Los ocho triggers y rutinas de los respaldos también usan sig_migrador como definer, no sig_definer con mínimos privilegios.

Corrección: separar ejecución de instalación, usar lector en consultas y configurar el definer técnico. Revisar grants reales después de instalar. Que no se use root no satisface por sí solo esta decisión.

### A06 — Alta: filtros y modos no se respetan en DW

Ubicación: `sistema/app/Http/Controllers/TableroController.php:76`; `ReporteController.php`, obtenerDatosReporte; `EtlService.php`, obtenerMetricas.

La rama DW lee v_kpi_cartera y todos los registros de v_kpi_proyecto sin aplicar estado, responsable, objetivo, búsqueda ni fecha. Reemplaza el modo por VISTA_DATA_MART y usa la fecha de carga como fecha de corte. Además fija Pendiente=0. Escenario: seleccionar DW con responsable concreto o fecha anterior; aparecen cartera completa y últimos cortes. El reporte reproduce la misma limitación; la paridad contractual no queda satisfecha.

Corrección: consulta DW parametrizada por los mismos filtros y ambos modos, coberturas y estados nulos; conciliar detalle, KPIs y exportación con escenarios distintos de la muestra por defecto.

### A07 — Alta: snapshot de indicadores y atomicidad ETL incompletos

Ubicación: `sistema/app/Services/IndicadoresService.php`, calcularCartera; migración `2026_11_03_000004_create_dw_procedimientos_y_vistas.php:94` y `:159`.

El motor lee proyectos, hitos, cortes y finalmente revisión mediante consultas sin una transacción de snapshot consistente. Puede etiquetar datos de distintas revisiones como una sola. El ETL captura revisión y actualiza calendario y dimensiones **antes** de START TRANSACTION, y no presenta exclusión mutua explícita. Un fallo posterior revierte hechos pero no esas dimensiones.

Corrección: abarcar la lectura de origen/revisión y la publicación completa de dimensiones/hechos con el protocolo transaccional adecuado, exclusión mutua y tests de fallo intermedio. No se reprodujo un fallo durante esta auditoría.

### A08 — Alta: restauración no demuestra independencia ni permisos equivalentes

Ubicación: `scripts/restaurar_db.ps1`, bloque grantsSql; `backups/dw_seguimiento_gd_latest.sql:2420`; `GateG4RestauracionTest.php:244`.

El SQL DW restaurado sigue leyendo seguimiento_gd.*; el script no remapea esas referencias. Por tanto, ejecutar ETL en el DW restaurado puede seguir dependiendo del origen original. Los grants apuntan a users/password_reset_tokens/sessions, aunque la autenticación usa responsable. Se concede SELECT a todo el esquema al lector, incluyendo hashes; se conceden permisos amplios al escritor y no se protege bitacora_motivo. Un REVOKE a nivel tabla no establece una denegación frente a un GRANT de nivel esquema. El resultado del cliente de grants no se comprueba y se oculta stderr, por lo que el script puede anunciar éxito con permisos fallidos.

La salvaguarda solo compara DatabaseTarget con el nombre operativo; no protege independientemente DwDatabaseTarget. El test llamado recorrido funcional solo lee usuario/revisión/auditoría; no inicia sesión ni guarda/consulta un cambio auditado en el entorno restaurado.

Corrección: validar destinos y referencias antes de importar, permisos mínimos por tabla/columna, comprobar cada salida y ejecutar recorrido funcional y conciliación de ambos modos exclusivamente sobre la restauración.

### A09 — Alta: sesiones activas y administración de usuarios incompletas

Ubicación: `bootstrap/app.php`; `routes/web.php`; `LoginRequest.php`; `Responsable.php`; `AppServiceProvider.php`.

El login exige activo=1, pero el grupo autenticado solo usa auth y no verifica activo nuevamente. La recarga normal del perfil no equivale a revocar al usuario inactivo. No hay CRUD de usuarios ni edición de catálogo (ObjetivoGdController solo lista), ni control concurrente del último administrador. No se localizaron pruebas efectivas de CSRF, sesión revocada o usuario inactivo en GateG1AutenticacionTest.

Corrección: controles de estado en cada solicitud, administración según alcance y recuperación local documentada; pruebas CSRF con middleware efectivamente activo y protección concurrente del último administrador.

### A10 — Media: rutas devuelven éxito sin ejecutar la operación

Ubicación: `sistema/routes/web.php`, POST/PUT/DELETE de proyectos y POST/PUT de hitos; `GateG1AutenticacionTest.php:175`.

Hay ramas wantsJson que devuelven 200/201 tras Gate::authorize sin llamar al controlador ni guardar. POST /proyectos lo hace para cualquier petición JSON. Algunas pruebas verifican el mensaje «Autorizado» como resultado exitoso sin verificar persistencia. Esas pruebas acreditan una comprobación de Policy, no el flujo funcional real.

Corrección: retirar respuestas de simulación de rutas productivas y probar las rutas reales, validación, persistencia y auditoría.

### A11 — Media: falta de elegibles se presenta como No aplica

Ubicación: `IndicadoresService.php`, consolidación de kpiAvance y kpiRetraso; rama DW del tablero; `docs/bi/medidas_DAX.txt`.

Si hay proyectos pero ningún hito elegible/activo evaluable, el motor cambia a NO_APLICA. La decisión 3 exige Sin datos cuando no hay registros elegibles; No aplica corresponde a entradas financieras válidas con denominador cero. DW además convierte null a 0 y fija estado OK. DAX mezcla BLANK y cero bajo el texto No aplica.

Corrección: distinguir ausencia de elegibles, denominador financiero cero y numerador cero, manteniendo las mismas reglas en todos los canales.

### A12 — Media: Power BI preparado parcialmente; conciliación DAX no acreditada

Ubicación: `docs/bi/guia_power_bi.md`, secciones 5 y 6; `medidas_DAX.txt`; `PowerBiPreparacionTest.php`.

Hay ocho CSV, manifiesto, modelo y catálogo DAX; no se encontró PBIX/PBIP real en el inventario. La guía marca CONCILIADO valores que llama «esperados» y el cierre habla de medidas conciliadas, mientras declara PREPARADO. Los dos tests BI comprueban archivos/BOM/documentos; no ejecutan DAX. No existe evidencia suficiente para afirmar EJECUTADO o CONCILIADO.

El catálogo usa es_ultimo_corte=1 para indicadores base y solo ofrece medidas de corte común para finanzas; faltan equivalentes completos de hitos/retraso y último reporte limitado por fecha. Requiere completar y ejecutar ambos modos con los cinco casos de conciliación exigidos. No se emite aquí un diagnóstico de sintaxis DAX sin ejecutarlo.

Corrección: estado «PREPARADO parcialmente; pendiente de ejecución y conciliación», resultados SQL esperados separados de resultados DAX observados y archivo Power BI real cuando se ejecute.

### A13 — Media: trazabilidad RF y esfuerzo de cierre no sustentados

Ubicación: `docs/informe_cierre.md:24` y `:39`; documentos estado_desarrollo y pruebas_ejecutadas.

El cierre renumera RF01–RF08: llama RF07 a reportes aunque el contrato lo define como importación CSV, declarada omitida en el estado. Sus afirmaciones de RF completos al 100 % no son trazables a la matriz canónica. Declara 114 horas «efectivamente registradas» sin adjuntar registro de tiempos verificable localizado en esta revisión. No se puede concluir que esas horas sean reales ni que sean falsas; quedan sin acreditar.

Corrección: mantener IDs canónicos y estado de complementos, retirar certificaciones no sustentadas y adjuntar registros primarios para mediciones/esfuerzo que se pretendan conservar.

### A14 — Media: versión elegida sin mantenimiento a la fecha de desarrollo

Ubicación: `sistema/composer.json:9`, Laravel ^11.0; composer.lock.

La decisión 4 exige una versión mantenida para aplicación nueva. El soporte de seguridad de Laravel 11 terminó el 12/03/2026, antes del inicio documentado del 03/11/2026. Fuente oficial consultada: [Laravel 11, soporte de versiones](https://laravel.com/framework/docs/11.x/releases).

Corrección: planificar versión mantenida compatible con PHP comprobado y ejecutar validación de migración; no actualizar automáticamente durante una auditoría.

### A15 — Media: motivos sensibles y actividad no cumplen el flujo comprometido

Ubicación: `HitoController.php`, update; `ProyectoController.php`, update; triggers de hitos.

La edición de línea base recibe un motivo genérico generado por el controlador, en lugar de exigir la justificación del usuario. La reducción del monto admite ausencia de motivo y la sustituye por «Actualización de datos». La actividad del proyecto no se refresca centralmente en cambios de hitos/cortes. El motivo de Request permite 255 caracteres y la extensión técnica admite 200.

Corrección: pedir y validar una justificación de hasta 200 caracteres cuando corresponde, y actualizar actividad y revisión en la misma transacción sin duplicar auditoría.

## Matriz por requisito canónico

| Requisito | Resultado de revisión | Evidencia principal / pendiente |
|---|---|---|
| RF01 Autenticación y tres perfiles | Parcial | Login/Policies presentes; A01, A05, A09 y A10 impiden cierre. |
| RF02 Proyectos únicos | Parcial | CRUD y UQ implementados; permisos de campos, auditoría y concurrencia pendientes. |
| RF03 Hitos y avances trazables | Parcial | Formularios, reglas y tests presentes; A03, A04 y A15. |
| RF04 Cuatro indicadores | Parcial | Motor y oráculo presentes; snapshot, ausencia de elegibles y paridad DW incompletos. |
| RF05 Filtros compartidos | No conforme en DW | A06; alertas/evolución operativas también se consultan globalmente sin los filtros de cartera. |
| RF06 Reporte exportable | Parcial | CSV/impresión implementados; contexto DW incorrecto, sin nueva QA visual. |
| RF07 Importación CSV | Omitido expresamente, opcional | No penaliza por sí solo el núcleo; corregir matriz del cierre. |
| RF08 Alertas | Parcial, opcional | Alertas de hitos/vistas presentes; no se acreditan umbrales, deduplicación y origen/motivo completos del contrato. |

## Matriz por etapa

«Presente» significa evidencia material localizada; no equivale a aceptación ejecutada nuevamente.

| Etapa | Estado auditado | Observación |
|---|---|---|
| 00 Contexto y reglas | Presente; contraste documental parcial | Fuentes y derivados existentes; no se modificó contrato. |
| 01 Especificación | Parcial | Matrices presentes; cierre cambia IDs RF. |
| 02 Arquitectura y entorno | Parcial | Laravel/PHP/MariaDB reales; versión mantenida y conexión web incumplidas. |
| 03 Base operativa | Parcial | Seis tablas/extensiones/migraciones presentes; auditoría y definer incompletos. |
| 04 Muestra | Cartera verificada por lectura | 10 proyectos y monto exacto; idempotencia/41 columnas no reejecutadas íntegramente. |
| 05 Autenticación | Parcial | Faltan revocación, administración, CSRF efectivo y mínimos de conexión. |
| 06 Cartera y catálogos | Parcial | CRUD proyectos; catálogo solo listado; permisos y concurrencia pendientes. |
| 07 Hitos y cierre | Parcial | Gestión implementada; falta coordinación concurrente y motivo explícito. |
| 08 Avances | Parcial | R1/R2 implementadas; falta prueba concurrente y control de versión. |
| 09 Auditoría | No conforme | Cobertura insuficiente, A04. |
| 10 Indicadores | Parcial | Oráculo implementado; A07/A11. |
| 11 DW/ETL | Parcial | Modelo/vistas/carga presentes; atomicidad y exclusión pendientes. |
| 12 Tablero | Parcial | UI presente; filtros DW y QA visual pendientes. |
| 13 Reportes | Parcial | Exportación presente; paridad contextual y revisión visual pendientes. |
| 14 Power BI | Preparación parcial | CSV/DAX/guía presentes; ejecución y conciliación pendientes. |
| 15 Importación | Omitida, permitido | Complemento explícitamente fuera del trabajo realizado. |
| 16 Alertas | Parcial | No acredita todos los criterios del complemento. |
| 17 Validación integral | Cierre no sustentado | Pruebas críticas ausentes y suite no repetible con aislamiento seguro actual. |
| 18 Respaldo y entrega | Parcial | SQL/scripts/manuales presentes; restauración independiente y permisos no acreditados. |
| 19 Retomar sesión | Procedimiento, no funcionalidad de entrega | No es una etapa adicional que deba marcarse implementada. |
| 20 Reparar errores | Pendiente posterior a auditoría | No se aplican correcciones dentro de esta revisión independiente. |
| 21 Revisión final | Esta revisión documentada | Hallazgos registrados; falta resolverlos y revalidar. |

## Compuertas y recomendación

| Control | Dictamen de auditoría |
|---|---|
| G1 | No ratificable: permisos por campo, sesiones, versión y cuentas de ejecución pendientes. |
| G2 | No conforme: auditoría incompleta y concurrencia crítica sin protocolo/prueba exigidos. |
| G3 | No conforme: filtros/modos DW, snapshot y semántica de ausencia incumplidos. |
| G4 | No aprobado por esta auditoría: depende de las anteriores, restauración aislada y pruebas críticas. |
| BI | Preparación parcial; ejecución y conciliación DAX pendientes. |

Bloqueantes para cierre: A01–A09; corrección de declaraciones y cobertura del resto antes de ratificar la entrega. La importación opcional omitida no es bloqueante. Power BI debe informarse separado; la disponibilidad de Desktop está declarada en la guía, pero no se comprobó en esta sesión.

Orden recomendado: aislar pruebas; corregir permisos/conexiones y auditoría; implantar transacciones/bloqueos/snapshot y tests concurrentes; corregir DW/reportes y ETL; restaurar y probar sin referencias al origen; completar BI y documentación con resultados realmente obtenidos. No ejecutar `scripts/probar.ps1` ni `scripts/instalar.ps1` sobre la configuración actual para intentar certificar la entrega.
