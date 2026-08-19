<?php

/**
 * Entradas del diario de aprendizaje. Única fuente de verdad para el
 * contenido de cada semana.
 *
 * CÓMO AGREGAR / COMPLETAR UNA ENTRADA
 * -------------------------------------
 * Busca el arreglo cuyo 'number' corresponda a la semana y completa los
 * campos de texto. No es necesario tocar ningún otro archivo del sitio:
 * el estado (próxima / disponible / completada), el progreso y el
 * formato de fecha se calculan automáticamente a partir de esta lista.
 *
 * Una entrada se considera "completada" en cuanto el campo 'reflexion'
 * deja de estar vacío. Los demás campos de reflexión son opcionales,
 * pero se recomienda completarlos todos para aprovechar el diseño de la
 * página de cada semana.
 *
 * CAMPOS
 * ------
 * number         int     Número de semana (1..total_weeks en course.php).
 * date           string  Fecha ISO YYYY-MM-DD de la sesión de esa semana.
 * title          ?string Título breve de la entrada. Null si aún no se define.
 * theme          ?string Tema o eje temático de la sesión. Null si no se define.
 * reflexion      ?string Reflexión general de la semana (determina el estado).
 * aprendizaje    ?string Principal aprendizaje obtenido.
 * cuestionamiento?string Pregunta o duda que queda abierta.
 * aplicacion     ?string Cómo se relaciona con la realidad nacional / práctica.
 * evidencia      ?array  Opcional: ['label' => string, 'url' => string]
 */

return [
    [
        'number' => 1,
        'date' => '2026-08-03',
        'title' => null,
        'theme' => null,
        'reflexion' => null,
        'aprendizaje' => null,
        'cuestionamiento' => null,
        'aplicacion' => null,
        'evidencia' => null,
    ],
    [
        'number' => 2,
        'date' => '2026-08-10',
        'title' => null,
        'theme' => null,
        'reflexion' => null,
        'aprendizaje' => null,
        'cuestionamiento' => null,
        'aplicacion' => null,
        'evidencia' => null,
    ],
    [
        'number' => 3,
        'date' => '2026-08-17',
        'title' => null,
        'theme' => null,
        'reflexion' => null,
        'aprendizaje' => null,
        'cuestionamiento' => null,
        'aplicacion' => null,
        'evidencia' => null,
    ],
    [
        'number' => 4,
        'date' => '2026-08-24',
        'title' => null,
        'theme' => null,
        'reflexion' => null,
        'aprendizaje' => null,
        'cuestionamiento' => null,
        'aplicacion' => null,
        'evidencia' => null,
    ],
    [
        'number' => 5,
        'date' => '2026-08-31',
        'title' => null,
        'theme' => null,
        'reflexion' => null,
        'aprendizaje' => null,
        'cuestionamiento' => null,
        'aplicacion' => null,
        'evidencia' => null,
    ],
    [
        'number' => 6,
        'date' => '2026-09-07',
        'title' => null,
        'theme' => null,
        'reflexion' => null,
        'aprendizaje' => null,
        'cuestionamiento' => null,
        'aplicacion' => null,
        'evidencia' => null,
    ],
    [
        'number' => 7,
        'date' => '2026-09-14',
        'title' => null,
        'theme' => null,
        'reflexion' => null,
        'aprendizaje' => null,
        'cuestionamiento' => null,
        'aplicacion' => null,
        'evidencia' => null,
    ],
    [
        'number' => 8,
        'date' => '2026-09-21',
        'title' => null,
        'theme' => null,
        'reflexion' => null,
        'aprendizaje' => null,
        'cuestionamiento' => null,
        'aplicacion' => null,
        'evidencia' => null,
    ],
    [
        'number' => 9,
        'date' => '2026-09-28',
        'title' => null,
        'theme' => null,
        'reflexion' => null,
        'aprendizaje' => null,
        'cuestionamiento' => null,
        'aplicacion' => null,
        'evidencia' => null,
    ],
    [
        'number' => 10,
        'date' => '2026-10-05',
        'title' => null,
        'theme' => null,
        'reflexion' => null,
        'aprendizaje' => null,
        'cuestionamiento' => null,
        'aplicacion' => null,
        'evidencia' => null,
    ],
    [
        'number' => 11,
        'date' => '2026-10-12',
        'title' => null,
        'theme' => null,
        'reflexion' => null,
        'aprendizaje' => null,
        'cuestionamiento' => null,
        'aplicacion' => null,
        'evidencia' => null,
    ],
    [
        'number' => 12,
        'date' => '2026-10-19',
        'title' => null,
        'theme' => null,
        'reflexion' => null,
        'aprendizaje' => null,
        'cuestionamiento' => null,
        'aplicacion' => null,
        'evidencia' => null,
    ],
    [
        'number' => 13,
        'date' => '2026-10-26',
        'title' => null,
        'theme' => null,
        'reflexion' => null,
        'aprendizaje' => null,
        'cuestionamiento' => null,
        'aplicacion' => null,
        'evidencia' => null,
    ],
    [
        'number' => 14,
        'date' => '2026-11-02',
        'title' => null,
        'theme' => null,
        'reflexion' => null,
        'aprendizaje' => null,
        'cuestionamiento' => null,
        'aplicacion' => null,
        'evidencia' => null,
    ],
    [
        'number' => 15,
        'date' => '2026-11-09',
        'title' => null,
        'theme' => null,
        'reflexion' => null,
        'aprendizaje' => null,
        'cuestionamiento' => null,
        'aplicacion' => null,
        'evidencia' => null,
    ],
];
