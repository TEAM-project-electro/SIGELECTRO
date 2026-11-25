# Decisión: asistente RAG de consulta (Laudon Cap. 11)

Fecha: 25 de noviembre de 2026. Alcance: módulo complementario de consulta del conocimiento del SIG. Sin cambios en el contrato maestro ni en las 6 tablas operativas canónicas.

## Propósito

El asistente aplica la cadena de valor del conocimiento descrita en el Capítulo 11 de Laudon (adquirir, almacenar, difundir y aplicar). Reúne el conocimiento explícito del proyecto (especificación, contrato de indicadores, manual de usuario, decisiones y procesos) y los datos vigentes de la cartera, y responde preguntas citando siempre sus fuentes.

## Arquitectura

- Tablas técnicas independientes: `rag_documento`, `rag_fragmento` y `rag_consulta`. La conexión de lectura solo puede consultar los documentos y fragmentos. La aplicación solo puede insertar en el registro de consultas; no puede modificarlo ni borrarlo.
- Indexación local con `php artisan rag:indexar` sobre una lista blanca cerrada (`config/rag.php`). Los documentos se dividen por encabezados y párrafos.
- Recuperación híbrida: BM25 local siempre disponible; si existe clave de Gemini, se suman vectores `gemini-embedding-001` y ambos rankings se combinan con Reciprocal Rank Fusion.
- Datos de cartera obtenidos con las consultas predefinidas de `IndicadoresService` y filtrados con `ProyectoPolicy::view`. El modelo nunca redacta consultas SQL.
- Generación opcional con Gemini. Sin clave o sin red, el asistente responde en modo extractivo mostrando los fragmentos citados.

## Defensa contra manipulación del modelo (OWASP LLM01, LLM02 y LLM06)

1. Corpus cerrado: no existe carga de documentos por usuarios.
2. Escaneo de fragmentos al indexar; los fragmentos sospechosos se marcan como bloqueados y no se recuperan.
3. Guardia de entrada: longitud de 3 a 500 caracteres, rechazo de caracteres de control e invisibles, y detección de intentos de anular reglas, cambiar el rol del asistente, pedir la configuración interna, obtener credenciales o ejecutar sentencias SQL.
4. Separación entre reglas y datos: las reglas viajan como instrucción de sistema; la pregunta y los fragmentos viajan delimitados, con los signos de etiqueta neutralizados.
5. Mínimo privilegio: el asistente no tiene herramientas ni permisos de escritura sobre datos de negocio; la clave de la API se envía solo en una cabecera del servidor.
6. Validación de salida: se eliminan enlaces, imágenes, código y HTML; se exigen citas existentes y se descartan respuestas que contengan secretos o las reglas internas. Si la validación falla, se degrada al modo extractivo. La vista escapa todo el contenido.
7. Límite de 10 consultas por minuto por usuario y registro de cada consulta (sin guardar la respuesta generada).
8. Pruebas automatizadas de ataque en `AsistenteRagSeguridadTest`.

Ninguna capa es infalible por sí sola; la seguridad se basa en que, aun si el modelo fuera engañado, no tiene privilegios ni canales para causar daño o extraer información.

## Operación

- Clave opcional en `sistema/.env`: `GEMINI_API_KEY=` (nunca en el repositorio).
- Reindexar después de modificar los documentos de la lista blanca: `php artisan rag:indexar`.
