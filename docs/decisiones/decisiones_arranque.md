# Decisiones de Arranque del Proyecto (1 a 12)

Fecha de consolidación: 3 de noviembre de 2026.
Referencia canónica: [PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md](../../PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md).

## 1. Fuente canónica y precedencia
El contrato maestro en Markdown es la única fuente contractual viva. En caso de conflicto: los DOCX prevalecen sobre sus extracciones TXT; para nombres y montos de cartera prevalece el Plan (S/ 8,623,550.00 en 10 proyectos); para el esquema de datos prevalece el Informe. El contrato no se edita en caliente; las decisiones se registran aquí.

## 2. Retraso y línea base
El KPI de retraso evalúa exclusivamente hitos marcados `en_linea_base = 1`. Un hito fuera de línea base con fecha prevista anterior al corte no computa para retraso ni avance (puede aparecer como aviso de calidad). Denominador: proyectos activos (Pendiente, En curso) con línea base evaluable. Numerador: activos con al menos un hito de línea base vencido (fecha prevista < corte y cierre nulo o > corte).

## 3. Cero y ausencia de datos
- Sin proyectos elegibles: «Sin datos».
- Denominador financiero cero con entradas válidas: «No aplica».
- Numerador cero con denominador positivo: «0.00 %».
- Filtros vacíos: conteos 0 y porcentajes «Sin datos».

## 4. Framework y servidor MariaDB 10.11
- PHP detectado: 8.3.28.
- Framework: Laravel 11.x (versión estable y LTS compatible con PHP 8.3).
- Motor de base de datos de referencia: MariaDB 10.11. Como el equipo cuenta con MariaDB 10.4 en XAMPP y MySQL 8.4 en Laragon, se acordó ubicar MariaDB 10.11 portable en `C:\mariadb-10.11`, ejecutándose en el puerto `3307` sin registrar servicio en Windows y sin alterar configuraciones previas.

## 5. Identidad y conexiones de base de datos
Los perfiles de negocio (Administrador, Responsable, Consulta) no son cuentas de MariaDB, sino roles de aplicación gobernados por Policies de Laravel.
Cuentas técnicas fijadas:
- `sig_migrador`: DDL de instalación y migración (nunca en ejecución web).
- `sig_definer`: Definer técnico de rutinas/triggers.
- `sig_auth`: Selección mínima para autenticación (`id_responsable`, `usuario`, `clave_hash`, `activo`, `perfil`, `nombre`).
- `sig_lector`: Consultas de lectura web y del perfil Consulta (sin acceso a hashes).
- `sig_escritor`: Inserción y actualización de negocio (sin UPDATE/DELETE en bitácora).
- `sig_etl`: Carga del DW.
- `bi_lector`: Solo lectura de esquemas DW.
No se utiliza `root` en ninguna conexión de ejecución.

## 6. Transacciones y auditoría atómica
Toda mutación de negocio y su auditoría se ejecutan en una sola transacción y conexión.
Se fija el orden global de bloqueos: (1) `control_revision`, (2) fila padre `proyecto`, (3) registros dependientes (`hito`, `avance_presupuestal`). El actor `@id_responsable` se establece en la conexión antes de escribir y se limpia incondicionalmente en bloque `finally`.

## 7. Puertas de aceptación y desacoplamiento de Power BI
Las puertas G1 a G4 validan el núcleo web y DW. El componente Power BI se gestiona como artefacto desacoplado con estados: PREPARADO, EJECUTADO o CONCILIADO. Si Power BI Desktop no se ejecuta localmente, G4 puede cerrarse como "Núcleo académico verificado; Power BI preparado para ejecución".

## 8. Alcance acotado de pruebas de concurrencia
Las pruebas de concurrencia requeridas se delimitan a escenarios deterministas con dos conexiones reales de prueba para verificar:
- Bloqueo de proyecto al cerrar mientras se edita un hito.
- Validación de gasto no decreciente (R2) en solicitudes simultáneas.
- Prevención de degradación o pérdida del último administrador activo.

## 9. Reglas de proyecto e IDE
Este Markdown rige para Antigravity IDE y ChatGPT sin suponer configuraciones heredadas ni depender de plugins o herramientas externas no comprobadas.

## 10. Documentos derivados y AGENTS.md breve
Se crean `docs/contexto_validado.md`, `docs/protocolo_desarrollo.md` y `docs/plan_ejecucion.md` desde las secciones correspondientes del contrato. Se crea un `AGENTS.md` breve (< 40 líneas) en la raíz que remite al contrato canónico. Los comandos reales van a `docs/entorno.md`.

## 11. Extensión `bitacora_motivo`
Para no alterar el esquema original de `bitacora_cambio`, se crea la tabla de extensión:
`bitacora_motivo(id_cambio INT PK FK bitacora_cambio, motivo VARCHAR(200) NOT NULL)`.
El motivo es obligatorio (fijado mediante `@motivo_cambio` en la misma conexión) para: cambios de línea base en seguimiento activo, reapertura de proyectos, reducción de monto total y desactivación de usuarios.

## 12. Historial de asignaciones
El modelo conserva el responsable vigente en `proyecto.id_responsable`. El historial de traspasos se consulta en `bitacora_cambio` filtrando por `tabla_afectada = 'proyecto'` y `campo = 'id_responsable'`.
