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
 * comments   array   Foro público de esta entrada: lista de comentarios
 *                     ['id', 'name', 'content', 'created_at'], en orden
 *                     cronológico (más antiguo primero). [] si todavía no
 *                     hay ninguno. Se publican desde /semana/N (sin login);
 *                     ver api/lib/comments.php y CLAUDE.md, "Comentarios".
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
        'comments' => [
            ['id' => '71bcb50ac0e42713', 'name' => "Jhendry", 'content' => "Hola Dereck, \nViene a leer su diario. Primero está muy bonito y es interactivo.\nMuy buena reflexión. Me alegra que desde la primera clase ya estés cuestionando y relacionando los temas con nuestra realidad. Precisamente, uno de los retos del seminario será aprender a escuchar otras perspectivas, cuestionar nuestras propias ideas y construir criterios con base en el análisis.", 'created_at' => '2026-08-19T20:51:30-06:00'],
        ],
    ],
    [
        'week' => 2,
        'week_start' => '2026-08-17',
        'class_date' => '2026-08-19',
        'title' => 'Desarrollo no es solo que crezca la economía',
        'theme' => 'Qué es desarrollo, crecimiento vs. desarrollo, indicadores y nuevas economías',
        'blocks' => [
            ['type' => 'paragraph', 'text' => "Esta segunda clase fue más de aterrizar conceptos, pero la verdad terminé pensando más de lo que esperaba. Empezamos hablando de qué significa \"desarrollo\" y en el momento me pareció una palabra bastante obvia, pero en cuanto se empezó a desglosar (desarrollo económico, social, humano, tecnológico) me di cuenta de que es una palabra que uso todo el tiempo sin pensar realmente en qué estoy diciendo."],
            ['type' => 'heading', 'text' => "Crecer no es lo mismo que desarrollarse"],
            ['type' => 'paragraph', 'text' => "Lo que más se me quedó fue justamente esa diferencia entre crecimiento y desarrollo. Un país puede estar creciendo en números, más producción, más PIB, y aun así la gente no estar mejor. Eso le da otra vuelta a cómo uno lee las noticias económicas, porque muchas veces el titular es solo \"la economía creció X%\" y ya, como si eso significara automáticamente que a todos les está yendo mejor."],
            ['type' => 'highlight', 'text' => "Si algo me quedó de hoy es que crecer y desarrollarse no son lo mismo, y esa distinción cambia bastante cómo debería leer uno un titular económico."],
            ['type' => 'paragraph', 'text' => "También vimos que hay distintos estilos de desarrollo, industrialista, sostenible, extractivista, y en el fondo cada uno es una forma distinta de decidir qué hacer con los recursos y qué se entiende por \"progreso\". No es que uno sea automáticamente el correcto, sino que cada modelo prioriza cosas distintas."],
            ['type' => 'paragraph', 'text' => "Después entramos en indicadores: IDH, Gini, PIB, desempleo, inflación, esperanza de vida. Esta parte me pareció interesante porque uno normalmente escucha estos números en las noticias y ya, sin preguntarse qué están midiendo en realidad. Un número solo no cuenta toda la historia, puede haber crecimiento en el PIB y aun así mucha desigualdad, por ejemplo, y el PIB por sí solo no te lo dice."],
            ['type' => 'paragraph', 'text' => "Lo que más me llamó la atención de toda la clase fue el tema de las llamadas nuevas economías transformadoras: social, solidaria, del bien común, colaborativa, circular, verde. Me gustó porque no se sienten solo como teoría bonita, sino que aparecen como respuesta a problemas que no son solamente económicos, sino también sociales y ambientales. Esto conecta directo con lo que yo espero sacar de este curso, que es entender mejor el emprendimiento social: no solo montar un negocio, sino que un proyecto realmente sirva para algo y sea sostenible en el tiempo."],
            ['type' => 'paragraph', 'text' => "Aparte de la clase, esta semana también se habló bastante de las \"batallas de farmear aura\" que han estado pasando en la U, básicamente gente reuniéndose a hacer bailes raros con canciones de \"aura\". En lo personal no es algo que yo haría ni que me llame la atención, pero tampoco siento que le estén haciendo daño a nadie. Mientras la gente que participa no esté molestando o afectando a alguien más, que hagan lo suyo. Al final me hizo pensar un poco en que no todo tiene que coincidir con lo que uno personalmente haría para aceptar que está bien que otras personas lo hagan."],
        ],
        'comments' => [],
    ],
    [
        'week' => 3,
        'week_start' => '2026-08-24',
        'class_date' => '2026-08-26',
        'title' => null,
        'theme' => null,
        'blocks' => [],
        'comments' => [],
    ],
    [
        'week' => 4,
        'week_start' => '2026-08-31',
        'class_date' => '2026-09-02',
        'title' => null,
        'theme' => null,
        'blocks' => [],
        'comments' => [],
    ],
    [
        'week' => 5,
        'week_start' => '2026-09-07',
        'class_date' => '2026-09-09',
        'title' => null,
        'theme' => null,
        'blocks' => [],
        'comments' => [],
    ],
    [
        'week' => 6,
        'week_start' => '2026-09-14',
        'class_date' => '2026-09-16',
        'title' => null,
        'theme' => null,
        'blocks' => [],
        'comments' => [],
    ],
    [
        'week' => 7,
        'week_start' => '2026-09-21',
        'class_date' => '2026-09-23',
        'title' => null,
        'theme' => null,
        'blocks' => [],
        'comments' => [],
    ],
    [
        'week' => 8,
        'week_start' => '2026-09-28',
        'class_date' => '2026-09-30',
        'title' => null,
        'theme' => null,
        'blocks' => [],
        'comments' => [],
    ],
    [
        'week' => 9,
        'week_start' => '2026-10-05',
        'class_date' => '2026-10-07',
        'title' => null,
        'theme' => null,
        'blocks' => [],
        'comments' => [],
    ],
    [
        'week' => 10,
        'week_start' => '2026-10-12',
        'class_date' => '2026-10-14',
        'title' => null,
        'theme' => null,
        'blocks' => [],
        'comments' => [],
    ],
    [
        'week' => 11,
        'week_start' => '2026-10-19',
        'class_date' => '2026-10-21',
        'title' => null,
        'theme' => null,
        'blocks' => [],
        'comments' => [],
    ],
    [
        'week' => 12,
        'week_start' => '2026-10-26',
        'class_date' => '2026-10-28',
        'title' => null,
        'theme' => null,
        'blocks' => [],
        'comments' => [],
    ],
    [
        'week' => 13,
        'week_start' => '2026-11-02',
        'class_date' => '2026-11-04',
        'title' => null,
        'theme' => null,
        'blocks' => [],
        'comments' => [],
    ],
    [
        'week' => 14,
        'week_start' => '2026-11-09',
        'class_date' => '2026-11-11',
        'title' => null,
        'theme' => null,
        'blocks' => [],
        'comments' => [],
    ],
    [
        'week' => 15,
        'week_start' => '2026-11-16',
        'class_date' => '2026-11-18',
        'title' => null,
        'theme' => null,
        'blocks' => [],
        'comments' => [],
    ],
];
