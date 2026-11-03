# Registro de Pruebas Ejecutadas

Fecha de inicio: 3 de noviembre de 2026.
Entorno de ejecución: Windows 11 / PowerShell 7.4.5.

| ID | Fecha | Entorno | Etapa / Módulo | Comando o Procedimiento | Resultado Esperado | Resultado Obtenido | Estado | Evidencia |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| PRV-001 | 2026-11-03 | CLI local | 00 Fuentes | `Get-FileHash docs\fuentes\*` | Coincidencia de hashes SHA256 de los 2 DOCX y 2 TXT | Hashes validados y coincidentes con la extracción de `Prompts_SIG_Electrocentro_Codex.zip` | APROBADO | Ver `docs/inventario_fuentes.md` |
| PRV-002 | 2026-11-03 | CLI local | 00 Entorno | `php -v`, `composer --version`, `node -v`, `npm.cmd -v` | Herramientas operativas en versiones compatibles (PHP 8.2+, Node 18+) | PHP 8.3.28, Composer 2.9.2, Node 24.19.0, npm 11.17.0 | APROBADO | Ver `docs/plan_ejecucion.md` |
| PRV-003 | 2026-11-03 | CLI local | 00 Git | `git init` | Inicialización de repositorio local sin pertenecer a repo padre | Repositorio creado en `E:\8VO CICLO\SISTEMA DE INFORMACIÓN GERENCIAL\proyecto team` | APROBADO | Commit inicial preparado |
