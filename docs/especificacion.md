# Especificación Funcional y Técnica del SIG de Electrocentro

Fecha de especificación: 3 de noviembre de 2026.
Referencia canónica: [PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md](../PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md).
Estado del documento: Aprobado para implementación (Etapa 01).

---

## 1. Propósito, Alcance y Límites

### 1.1 Propósito
Desarrollar una aplicación web académica local para la supervisión y seguimiento gerencial de la cartera de proyectos de gobierno digital de **Electrocentro S. A.** correspondiente al Plan de Gobierno Digital (PGD) 2023–2026. El sistema centraliza la planificación de hitos, los cortes presupuestales periódicos y la auditoría de cambios para proveer indicadores ejecutivos al Comité de Gobierno Digital.

### 1.2 Alcance
- Seguimiento de una cartera acotada de **10 proyectos** (S/ 8,623,550.00) que cubren los seis Objetivos de Gobierno Digital (`OGD 01` a `OGD 06`).
- Registro y edición de proyectos y catálogos de objetivos.
- Registro periódico de hitos (previsto, cierre, evidencia, línea base).
- Registro de avances presupuestales (presupuesto planificado acumulado y gasto acumulado).
- Tablero gerencial con cuatro indicadores clave, filtros compartidos y dos modalidades temporales (`CORTE_COMUN` y `ULTIMO_REPORTE_POR_PROYECTO`).
- Exportación de reportes en CSV y vista imprimible optimizada para PDF.
- Trazabilidad y auditoría completa de modificaciones mediante variables de sesión `@id_responsable` y `@motivo_cambio`.
- Data Warehouse dimensional (`dw_seguimiento_gd`) con proceso ETL reproducible y desacoplamiento para visualización en Power BI.

### 1.3 Límites Explícitos
- Ámbito estrictamente académico y local (desarrollo para el curso de SIG, UNH).
- Queda totalmente fuera la facturación eléctrica comercial, suministro a clientes, SAP real en producción, operaciones de subestaciones y analítica predictiva.
- Los datos de hitos, montos de avance y nombres de responsables ficticios son estrictamente demostrativos.

---

## 2. Actores y Matriz de Permisos

### 2.1 Perfiles de Usuario
La aplicación reconoce tres perfiles diferenciados gobernados mediante *Policies* en servidor:

1. **Administrador (`perfil = 'Administrador'`):**
   - Gestión integral de usuarios (crear, editar, activar/desactivar, resetear clave local).
   - Gestión de catálogo de objetivos (`objetivo_gd`).
   - Alta y edición completa de proyectos (código, nombre, monto total, años, objetivo, asignación de responsable, fuente).
   - Consulta y exportación de la cartera, tablero y bitácora de auditoría.
   - Disparo y administración manual del proceso ETL.
2. **Responsable (`perfil = 'Responsable'`):**
   - Consulta de toda la cartera y tablero de indicadores en modo lectura.
   - Gestión de hitos y avances presupuestales **únicamente sobre los proyectos donde figure asignado** (`proyecto.id_responsable = auth.id_responsable`).
   - Modificación del estado del proyecto (**únicamente de sus proyectos**, sujeto a reglas de cierre/reapertura).
   - **Prohibido:** Modificar monto total del proyecto, cambiar la asignación de responsable, gestionar usuarios u objetivos, o alterar datos de proyectos ajenos.
3. **Consulta (`perfil = 'Consulta'`):**
   - Acceso de solo lectura a la cartera, fichas de proyecto, tablero gerencial y reportes.
   - Exportación de datos en CSV e impresión.
   - **Prohibido:** Toda acción de inserción, actualización o eliminación en cualquier entidad.

### 2.2 Matriz de Autorización por Objeto y Acción

| Entidad / Acción | Administrador | Responsable (Proyecto Propio) | Responsable (Proyecto Ajeno) | Consulta |
| --- | :---: | :---: | :---: | :---: |
| **Login / Logout / Mi Clave** | Sí | Sí | Sí | Sí |
| **Usuarios:** Listar / Crear / Editar / Desactivar | Sí | No | No | No |
| **Objetivos GD:** Listar / Crear / Editar | Sí | Solo lectura | Solo lectura | Solo lectura |
| **Proyecto:** Listar / Ver detalle | Sí | Sí | Sí | Sí |
| **Proyecto:** Crear / Editar datos generales / Monto | Sí | No | No | No |
| **Proyecto:** Cambiar Responsable asignado | Sí | No | No | No |
| **Proyecto:** Cambiar Estado (Pendiente / En curso) | Sí | Sí | No | No |
| **Proyecto:** Cerrar Proyecto (R3) | Sí | Sí | No | No |
| **Proyecto:** Reabrir Proyecto (con motivo) | Sí | No | No | No |
| **Hito:** Crear / Editar / Cerrar | Sí | Sí | No | No |
| **Hito:** Modificar `en_linea_base` (con motivo) | Sí | No | No | No |
| **Avance Presupuestal:** Crear / Editar | Sí | Sí | No | No |
| **Bitácora de Auditoría:** Consultar | Sí | Solo sus proyectos | No | No |
| **Tablero Gerencial / Filtros** | Sí | Sí | Sí | Sí |
| **Exportar Reporte (CSV / Imprimible)** | Sí | Sí | Sí | Sí |
| **Ejecutar ETL Data Warehouse** | Sí | No | No | No |

*Principio de denegación por defecto:* Cualquier ruta directa, llamada API o POST anidado que no cumpla explícitamente con esta matriz será abortado en servidor con código HTTP `403 Forbidden`.

---

## 3. Pantallas y Módulos de la Aplicación

1. **Módulo de Acceso (Login y Sesión):**
   - Pantalla de inicio de sesión sobria (azul oscuro institucional `#003366`, blanco, grises).
   - Campos: Usuario y Contraseña. Validación de usuario activo y credencial bcrypt/Argon2id.
   - Cierre de sesión e invalidación de token CSRF.
2. **Módulo de Cartera (`/proyectos`):**
   - Lista paginada con búsqueda por texto y filtros por Estado (`Pendiente`, `En curso`, `Cerrado`), Responsable y Objetivo GD.
   - Botón de "Nuevo Proyecto" (solo Administrador).
   - Ficha de detalle del proyecto (`/proyectos/{id}`):
     - Pestaña de Datos Generales.
     - Pestaña de Hitos (con badges de estado: Concluido, En plazo, Vencido, Línea base).
     - Pestaña de Avances Presupuestales (tabla cronológica con verificación de gasto no decreciente).
     - Pestaña de Historial de Cambios (auditoría contextualizada).
3. **Módulo de Tablero Gerencial (`/tablero`):**
   - Encabezado con selector de modalidad temporal (`CORTE_COMUN` predeterminado vs. `ULTIMO_REPORTE_POR_PROYECTO`), selector de fecha de corte y badge de revisión de datos (`control_revision`) y última carga ETL.
   - 4 tarjetas de KPIs principales (Avance de hitos, Proyectos con retraso, Desviación presupuestal, Proyectos por estado).
   - Detalle de proyectos evaluados con fecha efectiva, estado y botones de acceso directo.
   - Resumen de hitos vencidos que sustentan el porcentaje de retraso.
   - Tabla de composición presupuestal y gráfica de evolución.
4. **Módulo de Reportes (`/reportes`):**
   - Vista de exportación sincronizada con los filtros y modalidad activa en el tablero.
   - Descarga de archivo CSV formateado (UTF-8, delimitado por comas, cabeceras en español, sin fórmulas maliciosas).
   - Vista de impresión limpia `@media print` para generación de PDF nativo desde el navegador.
5. **Módulo de Administración (`/admin`):**
   - Catálogo de Objetivos GD (`/admin/objetivos`).
   - Gestión de Usuarios y Perfiles (`/admin/usuarios`).
   - Consola de Control ETL (`/admin/etl`).

---

## 4. Flujos Operativos y Reglas de Negocio

### 4.1 Regla R1: Consistencia Financiera
- En cada corte (`avance_presupuestal`):
  $$0 \le \text{presupuesto\_planificado} \le \text{monto\_proyecto}$$
  $$0 \le \text{gasto\_acumulado} \le \text{monto\_proyecto}$$
- Al reducir el monto de un proyecto en `proyecto.monto`, el servicio valida que el nuevo monto no sea inferior a ningún `presupuesto_planificado` ni `gasto_acumulado` previamente registrado en sus avances.

### 4.2 Regla R2: Monotonicidad Cronológica del Gasto
- Al insertar o editar un corte con fecha $T$, se comprueba:
  $$\text{gasto\_acumulado}(T_{\text{anterior}}) \le \text{gasto\_acumulado}(T) \le \text{gasto\_acumulado}(T_{\text{posterior}})$$
  excluyendo el propio registro de la consulta en caso de modificación.
- El presupuesto planificado no está forzado a crecer monótonamente (puede reflejar reprogramaciones), pero el gasto acumulado nunca disminuye cronológicamente.

### 4.3 Regla R3: Cierre de Proyectos y Refuerzo de Reapertura
- **Condición estricta de Cierre:** Para transicionar el estado de un proyecto a `Cerrado`, se exige:
  1. Que el proyecto cuente con **al menos un hito** con `en_linea_base = 1`. (Refuerzo frente al caso trivial de cero hitos).
  2. Que **todos los hitos de línea base** tengan `fecha_cierre IS NOT NULL`.
- **Inmutabilidad en Cierre:** Un proyecto en estado `Cerrado` tiene bloqueada la adición o edición de hitos y cortes presupuestales.
- **Flujo de Reapertura:** Solo el Administrador puede reabrir un proyecto (`Cerrado` $\to$ `En curso`), requiriendo obligatoriamente el ingreso de un texto de justificación que se almacena en `bitacora_motivo`.

### 4.4 Regla R4: Auditoría Atómica y Trazabilidad Obligatoria
- Toda escritura de negocio en `proyecto`, `hito`, `avance_presupuestal`, `objetivo_gd` y `responsable` requiere que en la conexión activa se ejecute previamente:
  ```sql
  SET @id_responsable = <id_autenticado>;
  SET @motivo_cambio = <motivo_o_null>;
  ```
- Si `@id_responsable` es nulo o inválido, el trigger rechaza la transacción con `SIGNAL SQLSTATE '45000'`.
- El bloque `finally` de la aplicación limpia `@id_responsable = NULL` y `@motivo_cambio = NULL` incondicionalmente, incluso ante excepciones.
- La tabla `bitacora_cambio` no tiene permisos de `UPDATE` ni `DELETE` para ninguna cuenta de aplicación.

---

## 5. Extensiones Técnicas Justificadas

Para dar cumplimiento estricto a las reglas sin desnaturalizar las 6 tablas y 41 columnas originales del informe, se formalizan dos extensiones técnicas:

1. **`control_revision` (Concurrencia y Sincronización):**
   - Tabla técnica de una sola fila (`id INT PRIMARY KEY, version BIGINT NOT NULL, fecha_hora DATETIME NOT NULL`).
   - Cada transacción que modifica proyectos, hitos o avances ejecuta un incremento atómico de `version`.
   - Permite que el tablero y la exportación certifiquen que provienen del mismo conjunto de datos y permite al DW detectar si está desactualizado.
2. **`bitacora_motivo` (Auditoría de Acciones Sensibles):**
   - Tabla técnica: `bitacora_motivo(id_cambio INT PRIMARY KEY, motivo VARCHAR(200) NOT NULL, FOREIGN KEY (id_cambio) REFERENCES bitacora_cambio(id_cambio))`.
   - Se puebla mediante el trigger de bitácora cuando `@motivo_cambio` tiene contenido.
   - Es obligatorio para: cambio de línea base, reapertura de proyectos, reducción de monto y desactivación de usuarios.
