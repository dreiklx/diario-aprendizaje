<?php

/**
 * Entradas del diario de aprendizaje. Única fuente de verdad para el
 * contenido de cada semana.
 *
 * CÓMO AGREGAR / COMPLETAR UNA ENTRADA
 * -------------------------------------
 * La forma recomendada es el editor visual en /editar: entra, elegí la
 * semana, construí la reflexión con el editor de bloques y guardá — el
 * commit y el redeploy son automáticos (ver CLAUDE.md, "Editor
 * privado"). Editar este archivo a mano también funciona si preferís
 * hacerlo directo: buscá el arreglo cuyo 'week' corresponda y completá
 * 'title', 'theme' y 'blocks'. No es necesario tocar ningún otro
 * archivo del sitio: el estado (próxima / disponible / completada), el
 * progreso y el formato de fecha se calculan automáticamente a partir
 * de esta lista.
 *
 * Una entrada se considera "completada" en cuanto 'blocks' tiene al
 * menos un bloque con contenido real (ver entry_has_content() en
 * api/lib/blocks.php).
 *
 * SEMANA vs. CLASE
 * ----------------
 * La semana académica empieza el lunes ('week_start'); la sesión del
 * curso es un día específico dentro de esa semana ('class_date', los
 * miércoles en este curso). Son fechas distintas a propósito — no
 * asumas que la clase cae el mismo día que el inicio de semana. El
 * estado "disponible" de una entrada se activa cuando 'class_date' ya
 * pasó (no cuando empezó la semana), porque es la fecha en la que
 * realmente hay algo que reflexionar.
 *
 * CAMPOS
 * ------
 * week       int     Número de semana (1..total_weeks en course.php).
 * week_start string  Fecha ISO YYYY-MM-DD del lunes de esa semana académica.
 * class_date string  Fecha ISO YYYY-MM-DD de la sesión de clase de esa semana.
 * title      ?string Título de la reflexión. Null si aún no se define.
 * theme      ?string Tema o eje temático de la sesión. Null si no se define.
 * blocks     array   Contenido de la reflexión, en bloques tipados (ver
 *                     api/lib/blocks.php, BLOCK_TYPES). [] si aún no hay nada.
 * teacher_comment ?string Retroalimentación de la profesora sobre esta
 *                     entrada (texto simple, no bloques). Null si todavía
 *                     no hay comentario. Se edita en /editar/semana/N/comentario.
 *
 * BLOQUES
 * -------
 * Cada bloque es ['type' => ..., ...campos según el tipo]:
 *   heading   ['type' => 'heading', 'text' => string]
 *   paragraph ['type' => 'paragraph', 'text' => string]
 *   highlight ['type' => 'highlight', 'text' => string]   — destacado grande
 *   quote     ['type' => 'quote', 'text' => string]
 *   list      ['type' => 'list', 'style' => 'ordered'|'unordered', 'items' => string[]]
 *   divider   ['type' => 'divider']
 *   link      ['type' => 'link', 'text' => string, 'url' => string]
 *   image     ['type' => 'image', 'url' => string, 'alt' => string, 'caption' => string]
 *
 * El texto de heading/paragraph/highlight/quote/list-items/caption
 * admite un marcado mínimo: **negrita**, *cursiva*, ==destacado==,
 * [texto](url). Se procesa siempre con render_inline_markup() —
 * jamás pongas HTML directo en un campo de texto.
 */

return [
    [
        'week' => 1,
        'week_start' => '2026-08-10',
        'class_date' => '2026-08-12',
        'title' => 'Bienvenida e introducción',
        'theme' => 'Presentación del curso y expectativas',
        'blocks' => [
            ['type' => 'heading', 'text' => "Lo que me llevo"],
            ['type' => 'paragraph', 'text' => "Hoy fue la primera clase del seminario y la verdad salí pensando en varias cosas. No fue una sesión cargada de contenido pesado, sino más bien para entender de qué va el curso y hacia dónde vamos con esto de \"producción y desarrollo\". Lo que más se me quedó fue que esto no va de memorizar teoría, sino de realmente entender lo que pasa en el país en vez de quedarme solo con lo que uno escucha por ahí sin pensarlo dos veces."],
            ['type' => 'paragraph', 'text' => "Me gustó que desde el inicio quedara claro que el curso es un espacio para discutir y no solo para que nos den la materia. Siendo un seminario, se supone que gran parte va a depender de lo que aportemos nosotros mismos, así que ya de una vez me quedó la sensación de que hay que llegar a cada clase con algo que decir."],
            ['type' => 'heading', 'text' => "Lo que espero desarrollar"],
            ['type' => 'paragraph', 'text' => "De este curso espero sacar sobre todo dos cosas. Primero, entender mejor cómo funciona la economía de Costa Rica: siento que es algo que como costarricense debería manejar bien, porque al final nos va a afectar a todos en el futuro y casi nunca lo vemos con calma. Segundo, me llama mucho la atención el tema de emprendimiento social. No me refiero solo a montar un negocio, sino a usar un proyecto sostenible para resolver problemas reales —sociales, ambientales, lo que sea— y no solo para generar ganancias."],
            ['type' => 'paragraph', 'text' => "También quiero trabajar mi pensamiento crítico: aprender a analizar una situación antes de aceptar una opinión así nomás, sin dejarme llevar por lo primero que escucho. Y de paso mejorar cómo me llevo trabajando en grupo, la comunicación y esas habilidades blandas que uno da por hechas pero que en realidad hay que trabajar. Al ser un seminario espero que las discusiones me obliguen a ver las cosas desde puntos de vista que normalmente no considero."],
            ['type' => 'heading', 'text' => "Una pregunta que me queda"],
            ['type' => 'quote', 'text' => "Lo que me quedó dando vueltas es hasta qué punto un espacio como este realmente nos va a hacer ver las cosas distinto, o si al final uno termina reforzando lo mismo que ya pensaba porque es más cómodo. Ojalá sea lo primero."],
        ],
        'teacher_comment' => null,
    ],
    [
        'week' => 2,
        'week_start' => '2026-08-17',
        'class_date' => '2026-08-19',
        'title' => null,
        'theme' => null,
        'blocks' => [],
        'teacher_comment' => null,
    ],
    [
        'week' => 3,
        'week_start' => '2026-08-24',
        'class_date' => '2026-08-26',
        'title' => null,
        'theme' => null,
        'blocks' => [],
        'teacher_comment' => null,
    ],
    [
        'week' => 4,
        'week_start' => '2026-08-31',
        'class_date' => '2026-09-02',
        'title' => null,
        'theme' => null,
        'blocks' => [],
        'teacher_comment' => null,
    ],
    [
        'week' => 5,
        'week_start' => '2026-09-07',
        'class_date' => '2026-09-09',
        'title' => null,
        'theme' => null,
        'blocks' => [],
        'teacher_comment' => null,
    ],
    [
        'week' => 6,
        'week_start' => '2026-09-14',
        'class_date' => '2026-09-16',
        'title' => null,
        'theme' => null,
        'blocks' => [],
        'teacher_comment' => null,
    ],
    [
        'week' => 7,
        'week_start' => '2026-09-21',
        'class_date' => '2026-09-23',
        'title' => null,
        'theme' => null,
        'blocks' => [],
        'teacher_comment' => null,
    ],
    [
        'week' => 8,
        'week_start' => '2026-09-28',
        'class_date' => '2026-09-30',
        'title' => null,
        'theme' => null,
        'blocks' => [],
        'teacher_comment' => null,
    ],
    [
        'week' => 9,
        'week_start' => '2026-10-05',
        'class_date' => '2026-10-07',
        'title' => null,
        'theme' => null,
        'blocks' => [],
        'teacher_comment' => null,
    ],
    [
        'week' => 10,
        'week_start' => '2026-10-12',
        'class_date' => '2026-10-14',
        'title' => null,
        'theme' => null,
        'blocks' => [],
        'teacher_comment' => null,
    ],
    [
        'week' => 11,
        'week_start' => '2026-10-19',
        'class_date' => '2026-10-21',
        'title' => null,
        'theme' => null,
        'blocks' => [],
        'teacher_comment' => null,
    ],
    [
        'week' => 12,
        'week_start' => '2026-10-26',
        'class_date' => '2026-10-28',
        'title' => null,
        'theme' => null,
        'blocks' => [],
        'teacher_comment' => null,
    ],
    [
        'week' => 13,
        'week_start' => '2026-11-02',
        'class_date' => '2026-11-04',
        'title' => null,
        'theme' => null,
        'blocks' => [],
        'teacher_comment' => null,
    ],
    [
        'week' => 14,
        'week_start' => '2026-11-09',
        'class_date' => '2026-11-11',
        'title' => null,
        'theme' => null,
        'blocks' => [],
        'teacher_comment' => null,
    ],
    [
        'week' => 15,
        'week_start' => '2026-11-16',
        'class_date' => '2026-11-18',
        'title' => null,
        'theme' => null,
        'blocks' => [],
        'teacher_comment' => null,
    ],
];
