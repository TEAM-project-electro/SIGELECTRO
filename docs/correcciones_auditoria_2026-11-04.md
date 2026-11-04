# Correcciones de la auditoría — 4 de noviembre de 2026

## Resultado vigente

Se corrigieron y aplicaron las falencias verificadas del **núcleo web, DW, ETL y restauración**. La suite integral ejecutada aprobó **133 pruebas y 1,441 aserciones en 35.55 s**. La compilación Vite aprobó 66 módulos. Se verificó MariaDB **10.11.11**, PHP **8.3.28**, Laravel **12.71.0** y PHPUnit **11.5.57**.

**Power BI sigue PREPARADO parcialmente**: se actualizaron sus ocho CSV y manifiesto, pero no se creó ni ejecutó un archivo Power BI ni se conciliaron medidas DAX en Desktop. No se cierra ese pendiente mediante una consulta SQL. No se declara aceptación institucional, cobertura absoluta ni finalización de las 22 etapas por contar archivos.

El contrato maestro no se modificó. Se conserva el modelo operativo de seis tablas y 41 columnas, con las dos extensiones ya documentadas. La base de trabajo conserva los **10 proyectos / S/ 8,623,550.00 / 26 hitos / 24 cortes**. No se resembró la operativa; se ejecutaron migraciones aditivas y cargas DW después de generar respaldo.

## Trazabilidad A01–A15

| Hallazgo | Corrección / comprobación | Estado |
|---|---|---|
| A01 Campos por perfil | Request y controlador restringen campos; Policy se reevalúa con padre y actor vigentes dentro del bloqueo. Alta también reautoriza tras bloquear. | Corregido |
| A02 Aislamiento | phpunit y entorno de pruebas usan `_test`; TestCase comprueba las siete conexiones y `DATABASE()` real. `.env.testing` se mantiene local y se retiró del índice Git, sin borrar el archivo privado. | Corregido |
| A03 Concurrencia | Revisión central → padre → operación; revisión esperada obligatoria en edición de proyecto/hito/corte; roles, propiedad y estado revalidados dentro de la transacción. Dos conexiones reales fuerzan espera de B mientras A sigue abierta para cierre/hito, R2 y último administrador. | Corregido |
| A04 Auditoría | Altas/cambios/bajas por triggers del definer; alta de responsable exige actor explícito, incrementa revisión y registra cambios de usuario sin hashes. Falta de contexto revierte la operación. | Corregido |
| A05 Privilegios | Modelo Responsable usa lector sin hashes; proveedor dedicado usa sig_auth con seis columnas. Reconciliador aplicado en operativa, test y restaurada. Pruebas rechazan lectura de hash por lector y de área por auth. | Corregido en las rutas y esquemas comprobados |
| A06 DW | Universo independiente de presencia de cortes, cálculo de fecha por proyecto y evaluación de hitos a corte común. Consulta en snapshot DW y cálculo puro común. Dos modos × cinco fechas × siete filtros concilian; desaparecidos se retiran sin cambiar claves vigentes. | Corregido |
| A07 ETL | Mutex, bloqueo de revisión de origen y publicación de dimensiones/hechos/estado/revisión en una transacción. Pruebas de rollback intermedio, exclusión y fechas de hitos fuera del calendario inicial. | Corregido |
| A08 Restauración | Remapeo a copia, protección de ambos destinos, reconciliación que retira grants heredados y respeta host/puerto. Restauración real; recorrido HTTP y ETL sobre copia; UPDATE/DELETE de ambas bitácoras denegados al escritor. | Corregido para el servidor local verificado |
| A09 Sesiones/administración | Revocación de cuenta activa, CSRF efectivo, CRUD de usuarios/perfiles y edición de catálogo con Policies; servicio administrativo exige actor/admin y conserva último administrador. | Corregido |
| A10 Rutas simuladas | Se mantienen controladores reales con pruebas de persistencia; no se reintrodujeron respuestas de autorización ficticias. | Corregido |
| A11 Ausencia/cero | Sin elegibles → Sin datos; plan financiero válido cero → No aplica; reglas iguales en operativo y DW. | Corregido en núcleo; DAX pendiente |
| A12 BI | Guía rotula SQL esperado y DAX pendiente; exportaciones regeneradas desde DW vigente. | **Pendiente de ejecución/conciliación en Power BI** |
| A13 Cierre/esfuerzo | IDs RF canónicos; se retiran horas efectivas no medidas y desviación ficticia. Documentación registra resultados y límites observados. | Corregido |
| A14 Framework | Actualización real a Laravel 12.71.0 / PHPUnit 11.5.57 / Collision 8.9.5 con lockfile. Composer no reportó avisos de vulnerabilidad al resolver. | Corregido |
| A15 Motivos/actividad | Justificación explícita de hasta 200 caracteres para reducción/LB/desactivación; trigger rechaza desactivación sin motivo; actividad y revisión forman parte de la transacción; finally limpia actor y motivo. | Corregido |

## Evidencia que demuestra la corrección

La comparación que antes fallaba, en último reporte al 30/06/2024, devuelve ahora en ambos orígenes:

| Dato | Operativo | DW |
|---|---:|---:|
| Proyectos | 10 | 10 |
| Planificado | S/ 4,020,000 | S/ 4,020,000 |
| Ejecutado | S/ 3,755,000 | S/ 3,755,000 |
| Avance de hitos | 40 % | 40 % |
| Retraso | 14.2857 % | 14.2857 % |

En corte común sin reporte financiero exacto al 15/05/2024 se conservan los 10 proyectos y avance 64 %, con cobertura financiera ausente. Ya no desaparece la cartera.

`CorreccionesAuditoriaTest` verifica las regresiones, además del oráculo independiente que sigue en `IndicadoresMotorTest`. `ConcurrenciaBloqueosTest` comprueba error 1205 y espera real antes del commit de A; después confirma que el estado consolidado rechaza la operación incompatible. `GateG4RestauracionTest` verifica inmutabilidad con los privilegios reales y recorre login → alta auditada → consulta → ETL → escritura denegada a Consulta sobre la copia.

Se inspeccionó el diff y una revisión independiente de bypasses identificó tres rutas adicionales: reautorización del alta, dimensiones de proyectos eliminados y host/puerto del reconciliador. Se corrigieron y se agregaron pruebas de revocación y depuración. No se ejecutó restauración contra otro servidor ni se declara compatibilidad no probada.

## Comandos y archivos

Desde `sistema/`, se ejecutaron:

```powershell
composer update laravel/framework phpunit/phpunit nunomaduro/collision --with-all-dependencies --no-interaction --no-progress
php artisan migrate --database=mysql --env=testing --force --no-interaction
php artisan migrate --database=mysql --force --no-interaction
php artisan etl:cargar
php artisan bi:exportar-datos
php artisan test --no-ansi
npm.cmd run build
```

Desde raíz se ejecutaron `php scripts/ajustar_permisos.php` para los pares operativo/DW, test y restaurado; `scripts/respaldo_db.ps1` y `scripts/restaurar_db.ps1`. Último respaldo: sufijo **20261104_114038**, con alias latest. El escritor ya no dispone de grants amplios de esquema en la restauración ni permisos directos de INSERT/UPDATE/DELETE sobre las bitácoras. Los errores HTTP de negocio omiten SQL/credenciales y distinguen validación, duplicado, FK y bloqueo.

Evidencia de ejecución local (ignorada por Git): `sistema/storage/pruebas_integrales.txt`, `pruebas_login_final.txt`, `pruebas_reglas_final.txt` y `paridad_corregida.jsonl`. Los informes de auditoría y revalidación anteriores se conservan como históricos. La decisión material se registra en [decisiones/2026-11-04_subsanacion_auditoria.md](decisiones/2026-11-04_subsanacion_auditoria.md).

Para otras instalaciones: las cuentas técnicas deben existir y tener credenciales privadas; el reconciliador requiere capacidad administrativa para conceder/revocar permisos sobre los esquemas indicados. Si se desea usar otra cuenta administrativa, proporcionar `SIG_ADMIN_USER` y `SIG_ADMIN_PASSWORD` solo en el proceso local. El script falla con código distinto de cero si no puede reconciliar y no imprime las credenciales. No modifica otros esquemas ni permisos globales. No reiniciar el sistema con instalación/siembra: usar `scripts/arrancar.ps1`.

## Alcance y pendientes

El núcleo queda apto para demostración académica de los flujos automatizados comprobados. Los cambios de fuente se mantienen coherentes con revisión y coberturas. RF07 (importación CSV) continúa omitido por alcance. La preparación y la ejecución BI permanecen separadas; falta abrir Power BI, guardar un archivo real y registrar los casos DAX con corte común, último reporte, responsable, ausencia de corte exacto y plan cero. No se sustituyó esa ejecución por consultas SQL ni se inventó PBIX.

No se realizó en esta sesión una revisión visual exhaustiva de teclado/impresión ni una instalación desde un equipo vacío. Se comprobaron renderizados HTTP, compilación, migraciones sobre los esquemas existentes, restauración y batería automatizada. No se declara que esas comprobaciones sustituyan las revisiones visuales o una instalación en otro equipo.
