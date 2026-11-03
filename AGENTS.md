# Reglas de Agente - SIG Electrocentro

Este archivo remite al contrato canónico: [PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md](./PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md).

## Propósito y Límites
- Aplicación académica local para el seguimiento gerencial de proyectos de gobierno digital de Electrocentro S. A. (10 proyectos de cartera PGD, S/ 8,623,550.00).
- Ámbito acotado: seguimiento de proyectos, hitos y cortes presupuestales. Queda fuera facturación, SAP real, clientes, operaciones eléctricas y producción.

## Ubicación y Estructura
- Documentación y contrato: en la raíz y en `docs/`.
- Aplicación Laravel: en `sistema/` (comandos de Composer, Artisan y npm se ejecutan dentro de `sistema/`).
- Comandos comprobados del entorno: registrados en [docs/entorno.md](./docs/entorno.md).

## Principios de Implementación y Seguridad
- Modelo de 6 tablas operativas y 41 columnas originales; extensiones técnicas (`control_revision`, `bitacora_motivo`) documentadas por separado.
- Tres perfiles (Administrador, Responsable, Consulta) controlados por Policies de Laravel en servidor.
- Prohibido el uso de `root` en conexiones de ejecución; credenciales técnicas según la Decisión 5.
- Transacciones atómicas de negocio y auditoría con `@id_responsable` y `@motivo_cambio`, limpiados en bloque `finally`.
- Pruebas de integridad sobre MariaDB 10.11 aislado (puerto 3307).

## Gobernanza
- No editar el contrato maestro `PROMPT_SIG_ELECTROCENTRO_COMPARTIDO.md`; registrar decisiones en `docs/decisiones/`.
- Estado vivo del proyecto: [docs/estado_desarrollo.md](./docs/estado_desarrollo.md).
