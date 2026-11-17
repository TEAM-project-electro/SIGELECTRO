# Decisión: credenciales privadas y salud pública mínima

Fecha: 6 de noviembre de 2026. Alcance: los dos hallazgos confirmados en el informe de seguridad; sin cambios en el contrato maestro, frontend o Power BI.

La siembra de responsables sólo inserta cuentas ausentes. Conserva íntegramente identidades existentes y rechaza colisiones entre ID y usuario. Las cuentas nuevas reciben hashes de claves aleatorias privadas; el alta inicial de `msalas` utiliza la contraseña elegida mediante `php artisan sig:inicializar`. El instalador migra primero y luego ejecuta ese comando. La contraseña se introduce dos veces sin eco ni argumentos; se retira de la configuración temporal al finalizar. Las otras cuentas reciben su clave privada desde Usuarios.

Las contraseñas públicas de versiones anteriores se rechazan en el login aunque su hash continúe almacenado, con el mismo error genérico y limitación de intentos. Tampoco pueden asignarse desde el servicio administrativo. Las claves ya personalizadas siguen funcionando. No se sobrescriben contraseñas operativas automáticamente.

Para recuperar el acceso de una instalación anterior, el operador autorizado ejecuta desde `sistema/`:

```powershell
php artisan sig:cambiar-clave msalas
```

El comando solicita una clave privada de 12 a 72 bytes, sin espacios extremos, y su confirmación. Conserva perfil/estado y registra el motivo de mantenimiento en la auditoría transaccional existente, con actor técnico canónico 1. Repetir por usuario cuando corresponda. Esta autoridad es la del operador con acceso local al proyecto y a sus credenciales técnicas; no se expone mediante HTTP.

`GET /salud` conserva comprobación de MariaDB y activos, pero responde únicamente `estado` y HTTP 200/503. Las excepciones se registran en servidor, sin serializar versiones, esquema, puerto o mensajes internos.

Las pruebas de autenticación disponen de contraseñas privadas explícitas sólo en esquemas `_test` y restauran los hashes anteriores al finalizar. Las regresiones cubren claves legadas, recuperación privada, preservación al resembrar, colisiones y fallo de salud. Los resultados finales se registran en el informe de corrección; esta decisión no certifica configuración del host ni remedia las mejoras operativas de respaldos del informe anterior.
