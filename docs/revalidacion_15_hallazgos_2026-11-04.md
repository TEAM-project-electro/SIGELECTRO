# Revalidación de los 15 hallazgos

Fecha: 4 de noviembre de 2026. Revisión independiente posterior a las correcciones registradas por Antigravity.
Referencia: [auditoría inicial](auditoria_cumplimiento_2026-11-04.md) y contrato maestro inalterado.

## Resultado

**Hay correcciones reales, pero no están cerrados los 15 hallazgos.** Resultado de esta revisión: **4 cerrados en el alcance comprobado, 9 parcialmente subsanados y 2 abiertos**. La aprobación global de G1–G4 declarada en estado_desarrollo no queda ratificada por esta revisión.

Se ejecutó realmente la suite actual: **119 pruebas aprobadas, 769 aserciones, 13.32 segundos**, salida 0. Las 73 fuentes PHP de app/routes/database/tests pasaron `php -l`. Pasar estas pruebas no acredita casos que no contienen: la consulta adicional de ambos modos temporales reproduce divergencias operativo/DW importantes.

Se conservaron código de aplicación, configuración, contrato y base operativa. La suite modifica los esquemas descartables `_test` y `_restaurada` ya preparados. No se reinstaló ni restauró nuevamente el proyecto. Se añadieron este anexo, un script de lectura y dos archivos de evidencia.

## Estado individual

| ID | Estado | Evidencia y pendiente |
|---|---|---|
| A01 Permisos por campos | Cerrado en alcance comprobado | UpdateProyectoRequest rechaza cambios reservados del Responsable; el controlador también comprueba campos. CarteraProyectoTest prueba alteración de monto y la suite pasó. La carrera de lectura/autorización sigue incluida en A03. |
| A02 Aislamiento de pruebas | Cerrado para configuración y ejecución actuales | `.env.testing` y phpunit.xml apuntan a `_test`; rutinas DW de test leen `_test`. Suite ejecutada con éxito. La salvaguarda de TestCase comprueba solo lector: conviene comprobar todas las conexiones y DATABASE() real, especialmente si en el futuro divergen. Los tests G4 leen originales para comparar y escriben en restaurada, no en la operativa. |
| A03 Concurrencia y edición obsoleta | Parcial | Hay bloqueo central en DatabaseTransactionService, pero los controladores no suministran proyectosIds y la comprobación de fecha de ProyectoController sigue antes de la transacción. Hitos/cortes siguen sin versión esperada. Las tres pruebas nuevas son secuenciales, sin solapamiento. |
| A04 Auditoría y revisión | Parcial | Se comprobaron **20 triggers** con sig_definer en cada esquema operativo, test y restaurado. Se añadieron INSERT/DELETE y campos faltantes. Pero trg_responsable_ai acepta actor ausente, lo sustituye por el usuario creado e inserta directamente en bitácora sin incrementar control_revision; trg_responsable_au no audita el cambio de usuario. Falta justificar una excepción restringida al bootstrap o exigir contexto y revisión para altas ordinarias. |
| A05 Conexiones y privilegios | Parcial | Default real ahora es lector y los triggers/rutinas usan sig_definer. No se localizaron usos explícitos de mysql/dw_migrador en app/routes. Sin embargo, SHOW GRANTS confirma SELECT de tabla completa para sig_auth en operativo y test; Responsable sigue usando conexión auth fuera del proveedor, en listados/controladores. No se pudieron inspeccionar los grants de sig_definer: la cuenta auditora recibió error 1044. Por tanto no se acredita todavía su privilegio mínimo. |
| A06 Filtros/modos del DW | Abierto | Hay filtros nuevos y rama común para tablero/reporte, pero consultarDw elimina proyectos sin corte exacto y calcula último corte global, no el máximo anterior a la fecha seleccionada. Divergencia reproducida con datos reales, detallada abajo. |
| A07 Snapshot y atomicidad ETL | Parcial | IndicadoresService usa transacción de lector. ETL tiene GET_LOCK y START TRANSACTION antes de dimensiones/hechos. No se localizaron tests de fallo intermedio, exclusión mutua y calendario fuera de rango que exige el contrato; DataWarehouseEtlTest conserva siete pruebas. La lectura de consultarDw y su revisión se realizan sin snapshot conjunto. |
| A08 Restauración independiente y grants | Parcial | Rutina real restaurada ya referencia seguimiento_gd_restaurada; script protege ambos destinos y comprueba salida de grants. Pero SHOW GRANTS conserva SELECT/INSERT/UPDATE/DELETE sobre **seguimiento_gd_restaurada.*** para sig_escritor: permite modificar/borrar ambas bitácoras. Añadir grants más estrechos no elimina el grant anterior. El recorrido G4 ahora modifica y revierte fuente por SQL, pero no prueba login ni denegaciones HTTP sobre restauración. No se repitió restauración desde cero en esta sesión. |
| A09 Sesiones y administración | Parcial | Middleware de activo registrado en web; UsuarioService y listado/activación de usuarios presentes. Faltan pruebas de sesión revocada, CSRF efectivo y solapamiento del último administrador. Catálogo solo listado; administración de usuarios limitada a activo, sin alta/cambio de perfil. |
| A10 Rutas simuladas | Cerrado | Rutas ahora llaman controladores reales; tests de autorización actualizados con payloads válidos. Suite pasó. |
| A11 Sin datos/No aplica | Cerrado en el núcleo revisado | Motor operativo y consultarDw conservan SIN_DATOS si no hay elegibles y NO_APLICA para plan financiero válido cero. No se acredita aún comportamiento ejecutado en Power BI. |
| A12 Power BI | Parcial y pendiente externo explícito | Guía ahora separa SQL esperado de DAX y declara PREPARADO parcialmente. No hay archivo real ni ejecución/conciliación DAX acreditados. Se corrigió la declaración engañosa; el componente BI continúa pendiente y no debe rotularse terminado. |
| A13 Trazabilidad y esfuerzo | Parcial | Se restauraron IDs RF canónicos y omisión RF07. Sin embargo, la tabla todavía llama **Horas Efectivas** a 114 h, calcula desviación y afirma desarrollo concluido en plazo, a pesar de calificarlas de estimaciones. Además declara concurrencia y RF completos sin cubrir las carencias reproducidas en esta revisión. |
| A14 Framework mantenido | Abierto | composer.json sigue en Laravel ^11.0. Un roadmap a Laravel 12 no modifica la versión instalada ni satisface el requisito de versión mantenida al inicio del desarrollo. |
| A15 Motivos sensibles/actividad | Parcial | Reducción de monto y cambio de línea base exigen motivo de hasta 200 caracteres; hitos/cortes refrescan actividad. Pero ResponsableController/UsuarioService siguen sustituyendo por texto genérico el motivo de desactivación ausente, en vez de exigir la justificación. Actividad/edición obsoleta requieren además A03. |

«Cerrado en alcance comprobado» expresa el comportamiento efectivamente revisado y las pruebas existentes; no equivale a certificación exhaustiva ni institucional.

## Divergencias reproducidas de A06

Comandos ejecutados desde raíz:

```powershell
php scripts/revalidar_auditoria_lectura.php
```

El script solo hace SELECT, SHOW GRANTS y llamadas de servicios de consulta. No ejecuta ETL ni escrituras. Compara la misma muestra, modalidad y fecha en ambos servicios.

| Modo / fecha | Operativo | DW | Incumplimiento |
|---|---|---|---|
| CORTE_COMUN, 30/06/2024 | 10 proyectos; avance 68 %; retraso 28.5714 % | 1 proyecto; avance 50 %; retraso 100 % | El universo de cartera no debe desaparecer por falta de corte financiero exacto; hitos se evalúan a fecha común independientemente de cobertura financiera. |
| CORTE_COMUN, 15/05/2024 | 10 proyectos; avance 64 % | 0 proyectos; avance Sin datos | Una fecha sin reporte financiero no equivale a cartera vacía. |
| ULTIMO_REPORTE_POR_PROYECTO, 30/06/2024 | 10 proyectos; plan S/ 4,020,000; gasto S/ 3,755,000; avance 40 % | 4 proyectos; plan S/ 520,000; gasto S/ 495,000; avance 87.5 % | Se exige MAX(fecha_reporte) <= fecha límite por proyecto; usar es_ultimo_corte global elimina los proyectos con reportes posteriores. |
| ULTIMO_REPORTE_POR_PROYECTO, 30/09/2024 | 10 proyectos; plan S/ 6,090,000; gasto S/ 5,700,500; avance 68 % | Coincide dentro de tolerancia | El caso de último corte general funciona, pero no demuestra los anteriores. |

Ubicación: `sistema/app/Services/EtlService.php`, consultarDw, consulta desde fact_avance_proyecto y condición es_ultimo_corte=1. TableroController y ReporteController reutilizan ese servicio, por lo que ambos heredan la divergencia.

Corrección pendiente: partir del universo de proyectos y calcular por separado elegibilidad de hitos y finanzas; seleccionar el último corte relativo a la fecha por proyecto y mantener proyectos no elegibles con cobertura/exclusiones explícitas.

## Por qué las 119 pruebas no cierran A03/A06

En `ConcurrenciaBloqueosTest.php`, los dos primeros métodos hacen commit de A **antes** del intento B. El tercero desactiva un administrador y después intenta desactivar el siguiente mediante llamadas secuenciales. No se fuerza espera, bloqueo ni transacciones solapadas. Tener dos objetos PDO no demuestra concurrencia.

En `ProyectoController.php`, update lee y compara fecha y campos antes de adquirir bloqueo central. Dos solicitudes pueden validar el mismo estado previo y luego guardar secuencialmente sin rechazar la segunda edición obsoleta. El servicio admite proyectosIds pero no se encontró que los controladores lo suministren.

En `DataWarehouseEtlTest.php`, test_paridad_analitica_dw_frente_a_motor_operativo compara solo la vista agregada del último reporte sin fecha límite; no consulta los escenarios anteriores de consultarDw. Sus assertEqualsWithDelta usan **0.05 pp**, aunque el contrato exige **0.01 pp**. Deben endurecerse y ampliarse las pruebas antes de ratificar G3.

## Evidencia guardada

- [Salida íntegra de la suite](evidencia_revalidacion_tests_2026-11-04.txt): 119 passed, 769 assertions, 13.32 s.
- [Identidades, grants, definers, referencias y comparación de servicios](evidencia_revalidacion_lectura_2026-11-04.jsonl).
- [Comprobación reproducible de lectura](../scripts/revalidar_auditoria_lectura.php).

El script capturó de forma explícita la imposibilidad de consultar grants de sig_definer con la cuenta disponible; no elevó privilegios ni expuso contraseñas/hashes.

## Compuertas y siguiente trabajo

- **G1:** mejoras implementadas; faltan cerrar A05/A09 y evidencias efectivas de sesión/CSRF.
- **G2:** no ratificada: A03 y cobertura residual A04/A15.
- **G3:** no conforme por divergencias reales de A06 y pruebas de tolerancia insuficientes.
- **G4:** no ratificada: depende de las anteriores y de la protección real de auditoría restaurada A08.
- **BI:** PREPARADO parcialmente; ejecución y conciliación pendientes. Su ausencia no equivale por sí sola a un fallo del núcleo, pero no se declara entrega BI integral.

Prioridad: corregir selección temporal/universo DW; implementar pruebas realmente solapadas y revalidación dentro de transacción; retirar permisos amplios persistentes de restauración y probar inmutabilidad; completar controles/evidencias residuales. Después ejecutar pruebas afectadas y actualizar el cierre con resultados observados. Se mantiene el modo de revisión: no se aplicaron estas correcciones durante esta sesión.

Referencia de A14: [tabla oficial de soporte de Laravel 11](https://laravel.com/framework/docs/11.x/releases), consultada en la auditoría inicial; fin de soporte de seguridad 12/03/2026. El contrato exige mantenimiento para la aplicación nueva iniciada el 03/11/2026.
