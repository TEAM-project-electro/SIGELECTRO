<?php

return [
    // Lista blanca cerrada de documentos (rutas relativas a la raíz del proyecto).
    // No existe carga de documentos por usuarios: solo el operador reindexa con `php artisan rag:indexar`.
    'fuentes' => [
        'docs/especificacion.md',
        'docs/contrato_indicadores.md',
        'docs/manual_usuario.md',
        'docs/datos_demostracion.md',
        'docs/contexto_validado.md',
        'docs/marco_institucional_electrocentro.md',
        'docs/procesos_bpmn/README.md',
        'docs/decisiones/*.md',
        'docs/fuentes/rag_admin/*.md',
    ],

    'fragmento_max_caracteres' => 1400,
    'resultados' => 5,
    'pregunta_min' => 3,
    'pregunta_max' => 500,
    'consultas_por_minuto' => 10,

    // Generación opcional. Sin clave o sin red el asistente opera en modo extractivo.
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'modelo' => env('GEMINI_MODELO', 'gemini-flash-lite-latest'),
        'modelo_embedding' => env('GEMINI_MODELO_EMBEDDING', 'gemini-embedding-001'),
        'dimensiones' => 768,
        'url' => 'https://generativelanguage.googleapis.com/v1beta/models',
        'timeout' => 25,
    ],
];
