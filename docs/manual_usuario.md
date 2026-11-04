# Manual de Usuario — SIG Electrocentro S. A.

## Actualización operativa del 04/11/2026

El Administrador dispone de **Usuarios** y **Objetivos** en la navegación. Puede crear/editar usuarios, sus perfiles y credenciales; la clave nueva es opcional al editar. Desactivar exige una justificación de 5 a 200 caracteres. No se permite desactivar ni degradar al último Administrador activo. El usuario desactivado pierde acceso en su siguiente petición.

La edición de proyecto, hito y corte conserva la revisión que estaba abierta. Si otra operación la cambia, se rechaza el guardado: vuelva a cargar y revise los datos antes de reenviar. Nunca se sobrescribe el cambio ajeno silenciosamente. La revisión es global; puede ser necesario recargar aunque el cambio afecte otro proyecto.

El origen **DW** respeta los mismos filtros y fechas que el operativo. En corte común, carecer de un corte financiero exacto excluye solo las finanzas, sin ocultar el proyecto ni sus hitos. En último reporte se utiliza el máximo reporte de cada proyecto que no supera la fecha límite. Revise la advertencia de DW desactualizado y ejecute ETL con perfil Administrador antes de comparar.

Power BI sigue preparado parcialmente; las cifras SQL esperadas no son evidencia de ejecución DAX.

**Sistema de Información Gerencial para el Seguimiento de la Cartera de Gobierno Digital**  
**Organización:** Electrocentro S. A. (Cartera PGD 2023–2026, 10 proyectos por S/ 8,623,550.00)  
**Fecha de Publicación:** 4 de noviembre de 2026  
**Versión:** 1.0 (Entrega Académica - Gate G4)

---

## 1. Introducción y Propósito del Sistema

El **SIG Electrocentro** es una plataforma web gerencial diseñada para supervisar, controlar y analizar el progreso físico y presupuestal de la cartera de proyectos del Plan de Gobierno Digital (PGD) de Electrocentro S. A.

El sistema garantiza:
1. **Trazabilidad inmutable:** Todo cambio en fechas, montos o estados queda registrado en una bitácora auditada con actor técnico y motivo obligatorio.
2. **Consistencia de negocio:** Reglas estrictas en base de datos impiden gastos mayores al presupuesto adjudicado (R1), retrocesos en gasto devengado (R2) y cierres de proyectos con hitos pendientes (R3).
3. **Visión temporal dual:** Permite evaluar la cartera a una fecha de corte común para comités directivos (`CORTE_COMUN`) o mediante el estado más reciente de cada proyecto para supervisión operativa (`ULTIMO_REPORTE_POR_PROYECTO`).
4. **Analítica y Exportación Segura:** Tablero interactivo con conmutación en caliente entre base relacional y Data Warehouse, reportes imprimibles en A4 y exportaciones CSV neutralizadas contra inyecciones maliciosas.

---

## 2. Perfiles de Usuario y Credenciales de Demostración

El acceso al sistema está controlado por roles y políticas estrictas a nivel de servidor:

| Perfil | Usuario | Contraseña Demo | Rol y Responsabilidad Principal |
| :--- | :--- | :--- | :--- |
| **Administrador** | `msalas` | `Admin2026!` | Gestión global de cartera, reapertura de proyectos, auditoría y ejecución ETL. |
| **Responsable Titular** | `aquispe` | `Resp2026!` | Registro de avances presupuestales y cierre de hitos en sus proyectos asignados. |
| **Responsable Delegado** | `lrojas` | `Resp2026!` | Actualización técnica de proyectos alternos bajo su jefatura. |
| **Perfil Consulta** | `cgarcia` | `Consulta2026!` | Visualización ejecutiva, tablero gerencial, exportación CSV e impresión. |

> [!NOTE]
> Las contraseñas se almacenan mediante algoritmos `bcrypt` seguros en MariaDB 10.11. El sistema aplica bloqueo temporal por fuerza bruta tras 5 intentos fallidos consecutivos.

---

## 3. Guía Operativa por Perfil

### 3.1 Perfil Consulta (Auditoría y Directivos)
El perfil de Consulta tiene acceso de solo lectura orientado a la supervisión estratégica:

1. **Catálogo de Cartera (`/proyectos`):**
   - Visualice la lista completa de los 10 proyectos canónicos.
   - Aplique filtros rápidos por estado (`En curso`, `Pendiente`, `Cerrado`) o por área responsable.
   - Haga clic en **«Ver Ficha»** para examinar la metadata del proyecto, la lista de hitos de línea base y el histórico de cortes presupuestales.

2. **Tablero Gerencial de Control (`/tablero`):**
   - **4 Tarjetas de KPIs Principales:**
     - *Avance Físico Ponderado de Cartera:* Porcentaje promedio de hitos de línea base concluidos.
     - *Presupuesto Planificado vs Gasto Acumulado:* Comparativa consolidada de importes ejecutados.
     - *Desviación Presupuestal Global:* Diferencia porcentual entre lo planificado y lo gastado.
     - *Proyectos con Retraso:* Cantidad y porcentaje de proyectos activos con hitos vencidos.
   - **Conmutador de Origen de Datos:**
     - Botón `OPERATIVO`: Consulta directa a la base transaccional (`seguimiento_gd`).
     - Botón `DATA WAREHOUSE`: Consulta a las vistas dimensionales (`dw_seguimiento_gd`).
   - **Selector de Modalidad Temporal:**
     - *Corte Común:* Simula la foto de la cartera a una fecha específica (ej. 30/06/2024).
     - *Último Reporte por Proyecto:* Toma el corte más reciente de cada proyecto individualmente.
   - **Gráficos Interactivos (Chart.js):**
     - Evolución temporal de Planificado vs Gasto Acumulado.
     - Distribución de proyectos por estado de avance.
   - **Panel de Alertas de Cronograma (RF08):**
     - Semáforo de advertencia para hitos vencidos no cerrados y proyectos con sobregasto.

3. **Exportación de Reportes (`/reportes`):**
   - **Vista Imprimible / Guardar PDF (`/reportes/imprimir`):** Formato optimizado para A4 apaisado con resumen gerencial, estado de cartera y firma de conformidad.
   - **Descarga CSV (`/reportes/csv`):** Archivo delimitado por comas con codificación UTF-8 y marca BOM para compatibilidad directa con Microsoft Excel en español.

---

### 3.2 Perfil Responsable de Proyecto (`aquispe`, `lrojas`)
El responsable tiene autorización de edición exclusiva sobre los proyectos asignados a su persona:

1. **Actualización de Hitos de Proyecto:**
   - Ingrese a la ficha de su proyecto asignado (ej. `010 - Solución Integral Cloud ERP`).
   - En la sección **Hitos**, haga clic en **«Editar Hito»**.
   - Para concluir un hito:
     - Marque la casilla **Concluido**.
     - Ingrese la **Fecha de Cierre** real y la referencia documental de evidencia (ej. `Acta de Conformidad N° 014-2024-TIC`).
   - Para modificar la **Línea Base** (fecha prevista o inclusión):
     - El sistema exigirá obligatoriamente un **Motivo Justificado**. Si no se provee motivo, la transacción será rechazada por el servidor.

2. **Registro de Cortes Presupuestales (`/proyectos/{id}/avances/crear`):**
   - Seleccione la fecha del nuevo corte de seguimiento.
   - Ingrese el *Presupuesto Planificado Acumulado* y el *Gasto Devengado Acumulado*.
   - **Reglas Validadas en Base de Datos:**
     - *Regla R1:* Ningún corte puede superar el monto adjudicado total del proyecto (S/ 8,623,550.00 en cartera).
     - *Regla R2:* El gasto acumulado no puede ser menor al del corte cronológico anterior (monotonicidad devengada).
     - El sistema detectará intentos de crear dos cortes en la misma fecha para el mismo proyecto.

3. **Restricción de Acceso Cruzado:**
   - Si un responsable intenta editar o agregar avances a un proyecto asignado a otro titular (ej. Luis Rojas intentando editar un proyecto de Ana Quispe), el servidor responderá automáticamente con `403 Forbidden`.

---

### 3.3 Perfil Administrador (`msalas`)
El administrador posee privilegios gerenciales ampliados para la gobernanza del sistema:

1. **Gestión de Cartera:**
   - Alta de nuevos proyectos en la cartera y reasignación de responsables titulares.
   - **Cierre de Proyecto (Regla R3):**
     - Un proyecto solo puede marcarse como `Cerrado` si cuenta con hitos de línea base y **todos ellos se encuentran concluidos**. El intento de cerrar con hitos abiertos es bloqueado a nivel de base de datos.
   - **Reapertura de Proyecto Cerrado:**
     - El administrador puede reabrir un proyecto cerrado siempre que ingrese un **Motivo de Reapertura** formal. La acción queda registrada en `bitacora_motivo`.

2. **Auditoría Gerencial de Cambios (`/auditoria`):**
   - Registro cronológico inmutable de todas las mutaciones realizadas en la cartera.
   - Filtros de auditoría por:
     - Actor o responsable.
     - Tabla auditada (`proyecto`, `hito`, `avance_presupuestal`).
     - Rango de fechas.
   - Inspección detallada del valor anterior (`valor_anterior`) versus el nuevo valor (`valor_nuevo`).
   - La bitácora tiene privilegios de `SELECT` e `INSERT` únicamente; cualquier intento de `UPDATE` o `DELETE` es abortado por el motor MariaDB.

3. **Administración del Data Warehouse y ETL:**
   - Desde el Tablero Gerencial o mediante el comando de consola `php artisan etl:cargar`, el administrador puede refrescar las tablas dimensionales.
   - El panel muestra la fecha del último refresco, la cantidad de filas cargadas y el tiempo de respuesta.

---

## 4. Interpretación Correcta de los KPIs Gerenciales

Para una evaluación analítica rigurosa, el personal gerencial debe aplicar los siguientes criterios de interpretación:

### 4.1 Avance Físico Ponderado
- **Cálculo:** Proporción de hitos de la línea base completados sobre el total de hitos programados en línea base.
- **Criterio de borde:** Si un hito vence exactamente el mismo día del corte, **no** se considera retrasado. Solo se marca con retraso a partir del día calendario siguiente sin cierre.

### 4.2 Desviación Presupuestal
$$\text{Desviación (\%)} = \frac{\text{Gasto Acumulado} - \text{Presupuesto Planificado}}{\text{Presupuesto Planificado}} \times 100$$

> [!WARNING]
> **Advertencia de Negocio sobre Desviación Negativa:**  
> Cuando el gasto acumulado es menor al planificado ($\text{Desviación} < 0$), **no debe asumirse como un ahorro económico consolidado**. En la gestión pública y de gobierno digital, una desviación negativa suele representar un **desfase temporal en el devengado**, atrasos en los procesos de contratación o demoras en la emisión de actas de conformidad de proveedores.

### 4.3 Modalidades Temporales del Tablero
1. **Modalidad `CORTE_COMUN`:**
   - Aplica un filtro de corte unificado a toda la cartera (ejemplo: corte trimestral al 30 de junio de 2024).
   - Proporciona una imagen estricta y comparable para comités directivos.
   - Si un proyecto no registró avance a dicha fecha, se presenta como *Sin datos a la fecha*.

2. **Modalidad `ULTIMO_REPORTE_POR_PROYECTO`:**
   - Toma el último corte reportado de cada proyecto de forma independiente.
   - Refleja el estado operativo más fresco disponible, ideal para el seguimiento diario de la oficina de proyectos (PMO).

---

## 5. Prevención de Errores y Mensajes del Sistema

- **«Error de Concurrencia Optimista»:** Ocurre cuando dos usuarios intentan modificar el mismo proyecto simultáneamente. El sistema protege la integridad de los datos solicitando recargar la página para revisar los cambios más recientes.
- **«Regla R1 / R2 Violada»:** Indica que el importe ingresado no respeta el presupuesto total adjudicado o que se intentó registrar un gasto menor al del período anterior.
- **«Motivo Obligatorio»:** Recuerda que la modificación de líneas base y reapertura de proyectos exige una justificación escrita para fines de auditoría.
