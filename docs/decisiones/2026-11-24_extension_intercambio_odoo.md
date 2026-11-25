# Decisión: extensión controlada de intercambio de datos por lotes con Odoo (Capítulo 9)

Fecha: 24 de noviembre de 2026. Alcance: formalización documental y delimitación de alcance de la tabla técnica `abastecimiento_compra` y del importador CSV desde Odoo 16 Community; sin cambios en el contrato maestro `PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md` ni en las 6 tablas operativas canónicas (41 columnas).

## Contexto y motivación académica

El contrato canónico define a SIGELECTRO como un Sistema de Información Gerencial (SIG / MIS) acotado al seguimiento de 10 proyectos de la cartera PGD, sus hitos y sus cortes presupuestales, excluyendo facturación, clientes, operaciones eléctricas, SAP productivo y gestión general de suministros.

En el marco de la asignatura *Sistema de Información Gerencial* —guiada por la 18.ª edición de Laudon, Laudon y Traver (*Sistemas de Información Gerencial*, Cap. 9: *Aplicaciones empresariales*, PDF 323–351)— se incorporó una práctica demostrativa sobre la dificultad real de integrar sistemas empresariales heterogéneos con herramientas de supervisión gerencial.

## Decisión adoptada

1. **Aislamiento estructural respecto del núcleo canónico:** La información de órdenes de compra y recepciones físicas se almacena exclusivamente en una tabla de extensión técnica independiente (`abastecimiento_compra`), vinculada por clave foránea a `proyecto(id_proyecto)` con `ON DELETE RESTRICT`. Las 6 tablas operativas originales (41 columnas), el motor de los 4 indicadores canónicos (`IndicadoresService`) y el Data Warehouse (`dw_seguimiento_gd`) permanecen inalterados.
2. **Intercambio de datos por lotes (no integración en tiempo real ni ERP):** El componente implementado (`ImportadorOdooService`, `AbastecimientoController`, rutas `/abastecimiento/importar` y `/abastecimiento/plantilla`) procesa archivos CSV exportados desde una instancia local aislada de Odoo 16 Community (`sigelectro_odoo`), con vista previa obligatoria, actualización idempotente por identificador externo de línea (`odoo_linea_id`) y registro del usuario y fecha de carga.
3. **Delimitación conceptual expresa:**
   - **SIGELECTRO no es un ERP, ni reemplaza a SAP, ni constituye un módulo integral de logística o suministros.** El seguimiento del proyecto `010 - Solución Integral Cloud ERP` dentro de la cartera PGD es supervisión gerencial de inversión TI, no operación de un ERP.
   - **Tampoco constituye comercio electrónico (Capítulo 10, PDF 352–389):** disponer de interfaz web o importar un archivo estructurado de compras internas no convierte al sistema en un mercado digital ni en una plataforma de transacciones comerciales.
4. **Seguridad y respaldo:** Los privilegios sobre `abastecimiento_compra` respetan la segregación de cuentas técnicas sin `root` (`SELECT` para `sig_lector`, `sig_etl` y `sig_definer`; `SELECT, INSERT, UPDATE` para `sig_escritor`, sin `DELETE` libre en aplicación) y se reconcilian automáticamente durante la restauración aislada mediante `scripts/ajustar_permisos.php`.
