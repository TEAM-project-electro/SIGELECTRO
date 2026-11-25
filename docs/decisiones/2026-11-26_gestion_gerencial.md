# Decisión: gestión gerencial de la cartera (prioridad 2 de la auditoría)

Fecha: 26 de noviembre de 2026. Alcance: segundo grupo de mejoras de la auditoría integral del 25/11/2026. El núcleo de 6 tablas no cambia de columnas; se agregan tablas técnicas independientes (migración `2026_11_26_000001_crear_gestion_gerencial`).

## Módulos

| Módulo | Qué resuelve | Dónde |
| --- | --- | --- |
| Priorización multicriterio | Falta de un método de priorización (PEI). Criterios con pesos configurables, calificación 1–5 con sustento obligatorio, ranking 0–100 con el aporte de cada criterio y sensibilidad del primer lugar (pesos ±10 %). | `/priorizacion` |
| Riesgos e incidencias | Anticipar problemas. Probabilidad × impacto, matriz 5×5, dueño, plan de respuesta y estado. | `/riesgos` y ficha |
| Avance físico y valor ganado | Distinguir ahorro de retraso. Pesos por hito de línea base (suman 100); EV, SPI, CPI y EAC solo con todos los hitos ponderados y un corte con plan y gasto. | Ficha |
| Presupuesto vigente y comprometido | Controlar la inversión. Versiones del monto y compromisos que no pueden superar el presupuesto vigente. | Ficha |
| Alertas preventivas | Actuar a tiempo. Hitos próximos a vencer o vencidos, proyectos sin actualizar, desfase o sobregasto, riesgos críticos y priorización incompleta, con umbrales editables. | `/alertas` |
| Historial, documentos y beneficios | Trazabilidad y valor demostrable. Historial auditado visible en la ficha, documentos inmutables con huella SHA-256 y beneficios planificados y medidos. | Ficha |

## Reglas en la base de datos

- Priorización, pesos de hitos, compromisos y umbrales: solo un Administrador activo (`sp_exigir_administrador`).
- Riesgos, documentos y beneficios: Administrador o Responsable titular del proyecto (`sp_exigir_actor_proyecto`).
- Los compromisos vigentes no superan el monto; el monto no baja de lo comprometido; un compromiso solo se anula, con motivo.
- Los documentos no se modifican ni se borran. Ninguna tabla nueva admite DELETE desde la aplicación.
- Toda alta y modificación queda en `bitacora_cambio` (se amplió `ck_bitacora_tabla` a las tablas nuevas).

## Criterios y umbrales iniciales

Los 12 criterios arrancan con el mismo peso (1,00) y la pantalla lo advierte: los pesos definitivos los aprueba el Comité de Gobierno Digital. Los umbrales de alerta son valores de partida editables por el Administrador. El parámetro `exigir_acta_cierre` (0 por defecto) permite exigir acta y beneficios antes de cerrar un proyecto.

## Limitaciones

- El Administrador ejerce el rol de PMO: no hay todavía perfiles PMO ni Comité ni flujo de aprobación con separación de funciones (prioridad 1, punto 2).
- Las alertas se muestran en la aplicación; no se envían por correo porque `responsable` no tiene dirección de correo.
- No se cargaron calificaciones, riesgos ni beneficios de ejemplo en la siembra: deben venir de la organización.

## Verificación

`tests/Feature/GestionGerencialTest.php` (8 pruebas) cubre cálculos, permisos de la aplicación y de la base de datos, inmutabilidad, alertas y regla de cierre. `scripts/ajustar_permisos.php` reconcilia los permisos de las tablas nuevas tras restaurar respaldos.
