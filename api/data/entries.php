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
        'title' => 'Una empresa no tiene que existir solo para ganar plata',
        'theme' => 'B Corps, cooperativismo y los principios de Rochdale: distintas formas de entender el éxito de una empresa',
        'blocks' => [
            ['type' => 'paragraph', 'text' => "La verdad esta clase me dejó pensando en algo tan básico como qué es una empresa. Si alguien me lo hubiera preguntado antes, casi seguro habría dicho que una empresa existe para ganar plata y punto. Pero después de ver el tema de las Empresas B y todo lo del cooperativismo, siento que la cosa puede ser un poco más complicada que eso."],
            ['type' => 'paragraph', 'text' => "Uno de los videos que vimos, sobre las Empresas B, se llamaba justamente algo así como \"las mejores empresas para el mundo\", y esa idea se me quedó: no se trata de ser la mejor empresa del mundo en ventas o en tamaño, sino la mejor para el mundo. La certificación esa mira cómo la empresa trata a sus trabajadores, a la comunidad y al ambiente, no solo cuánta plata genera, y varias empresas hasta cambian sus estatutos para dejar por escrito que van a tomar decisiones pensando en toda esa gente y no solo en los dueños."],
            ['type' => 'heading', 'text' => "No es que la empresa \"normal\" sea el malo de la película"],
            ['type' => 'paragraph', 'text' => "Quiero aclarar eso porque no es mi punto. No estoy diciendo que un negocio que quiere ganar plata esté mal. Lo que me pareció interesante fue darme cuenta de que hay distintas formas de entender qué significa que a una empresa le vaya bien. Ganar plata es necesario para sobrevivir y para que el negocio siga funcionando, pero eso no quiere decir que tenga que ser lo único que importe."],
            ['type' => 'heading', 'text' => "Rochdale: gente organizándose desde 1844"],
            ['type' => 'paragraph', 'text' => "El otro video contaba la historia de los pioneros de Rochdale, un grupo de trabajadores de un pueblo textil en Inglaterra que en 1844, después de que les fallara una huelga, se pusieron a juntar de a poquitos durante un año y con eso abrieron una tienda pequeña que vendía productos básicos honestos, a precio justo y sin engañar en el peso. Lo que me llamó la atención fue que desde el arranque se pusieron reglas: cada socio tenía un voto sin importar cuánta plata había metido, las ganancias se repartían según lo que cada quien había comprado, y cualquiera que quisiera podía entrar. O sea, gente juntándose para resolver una necesidad común con base en ciertos principios."],
            ['type' => 'paragraph', 'text' => "Me pareció curioso que una idea de hace más de 150 años todavía se pueda conectar con discusiones de ahora sobre cooperación, sobre cómo se reparte el valor que se genera, sobre participación y responsabilidad. No es que todo lo de hoy venga directamente de Rochdale, eso sería exagerar, pero la idea de fondo, que es organizarse para decidir juntos qué hacer con lo que uno tiene, no se siente vieja para nada."],
            ['type' => 'highlight', 'text' => "Uno está acostumbrado a pensar que competir y ganar más es la meta obvia de cualquier empresa. Ver lo de las Empresas B y el cooperativismo me hace pensar que tal vez la pregunta también debería ser qué hace una empresa con el poder y los recursos que tiene."],
            ['type' => 'paragraph', 'text' => "Al final esto conecta con algo que ya he dicho en el curso: a mí me interesa el emprendimiento, pero no solo la fórmula de montar una empresa y ganar plata. Me interesa más entender cómo un proyecto puede meterse con problemas sociales o ambientales reales y aun así sostenerse en el tiempo. Y no quiero caer en la idea ingenua de que \"la plata no importa\", porque sí importa: una organización que no logra sostenerse tampoco va a poder mantener su impacto. Lo interesante está justo en el equilibrio, en si se puede armar algo que sea viable económicamente y que al mismo tiempo genere un impacto positivo de verdad. Eso es algo que quiero seguir aprendiendo este semestre."],
        ],
        'comments' => [],
    ],
    [
        'week' => 4,
        'week_start' => '2026-08-31',
        'class_date' => '2026-09-02',
        'title' => 'La economía también puede ser solidaria',
        'theme' => 'Economía Solidaria',
        'blocks' => [
            ['type' => 'heading', 'text' => "Una idea que me cambió un poco la forma de verlo"],
            ['type' => 'paragraph', 'text' => "La verdad, esta clase me hizo entender mejor qué significa eso de economía solidaria, porque antes yo escuchaba la palabra y pensaba más que todo en ayudar a los demás, pero es bastante más que eso. Tiene que ver con la forma en que las personas se organizan para trabajar, producir y generar ingresos, pero tratando de que también existan cosas como la cooperación, la participación y el beneficio de un grupo."],
            ['type' => 'paragraph', 'text' => "Algo que me llamó la atención es que esto no es simplemente una idea nueva que alguien inventó y ya. En Costa Rica existe desde hace bastante tiempo y hay cooperativas, asociaciones y diferentes organizaciones que ya forman parte de la economía del país. De hecho, la ESS se relaciona con cosas como la solidaridad, la cooperación, la participación y también con temas ambientales."],
            ['type' => 'highlight', 'text' => "No todas las organizaciones tienen las mismas necesidades, aunque formen parte de la economía social y solidaria."],
            ['type' => 'paragraph', 'text' => "Esto también me pareció bastante interesante porque uno podría pensar que si todas entran dentro de la misma categoría, entonces todas necesitan el mismo tipo de ayuda, pero no necesariamente. En la lectura se habla de organizaciones muy diferentes entre sí, desde cooperativas grandes hasta iniciativas mucho más pequeñas, y por eso las soluciones tampoco pueden ser exactamente iguales para todas."],
            ['type' => 'heading', 'text' => "Cuando la teoría se vuelve algo real"],
            ['type' => 'paragraph', 'text' => "Creo que Grameen fue de los ejemplos que más me ayudaron a entenderlo porque ahí ya uno puede ver cómo estas ideas se llevan a la práctica. No se trata solamente de prestar dinero, sino de crear oportunidades y trabajar de una manera donde las personas también se apoyan entre ellas. Eso me hizo pensar que la economía puede servir para resolver problemas reales y no solamente para generar ganancias."],
            ['type' => 'paragraph', 'text' => "También me pareció interesante ver que en Costa Rica la economía social y solidaria ha tenido bastante relación con el Estado y con diferentes instituciones. Hay ejemplos relacionados con cooperativas, asociaciones comunales, acceso al crédito, servicios y hasta cosas como el agua y la electricidad en algunas comunidades."],
            ['type' => 'paragraph', 'text' => "Y aquí es donde siento que el tema se conecta con lo que yo espero aprender en este curso. A mí sí me interesa el emprendimiento, pero no solamente por la idea de crear una empresa y ganar plata. También me interesa entender cómo un proyecto puede ser sostenible y al mismo tiempo ayudar a resolver un problema social, ambiental o de una comunidad."],
            ['type' => 'quote', 'text' => "Tal vez el punto no es dejar de ganar dinero, sino pensar también en qué estamos generando además de dinero."],
            ['type' => 'paragraph', 'text' => "No creo que ganar dinero sea algo malo ni que una organización pueda funcionar sin ser económicamente sostenible. Al final, si no puede mantenerse, tampoco va a poder mantener el impacto que quiere generar. Pero sí me parece interesante que existan modelos donde el éxito no se mida solamente por cuánto dinero se produce, sino también por lo que se logra para las personas y para la comunidad."],
            ['type' => 'paragraph', 'text' => "Al final, siento que esta clase me ayudó a ver la economía de una forma un poco más amplia. Uno está muy acostumbrado a pensar en competir, vender y ganar más, pero también existen formas de organizarse donde la cooperación tiene un papel importante. Y la verdad, me interesa seguir viendo cómo funcionan estos modelos en la vida real y si realmente pueden ser sostenibles a largo plazo."],
        ],
        'comments' => [],
    ],
    [
        'week' => 5,
        'week_start' => '2026-09-07',
        'class_date' => '2026-09-09',
        'title' => '¿Compartir, trabajar o simplemente usar una app?',
        'theme' => 'Economía Colaborativa',
        'blocks' => [
            ['type' => 'heading', 'text' => "La tecnología cambió la forma de trabajar"],
            ['type' => 'paragraph', 'text' => "Esta clase me gustó bastante porque siento que la economía colaborativa es de esos temas que uno realmente ve todos los días sin ponerse a pensar mucho en lo que hay detrás. Hablamos de plataformas como Uber y de cómo una aplicación puede conectar a una persona que necesita un servicio con otra que quiere generar ingresos. Al final parece algo bastante sencillo, pero realmente cambia bastante la forma tradicional de hacer negocios."],
            ['type' => 'paragraph', 'text' => "Lo que más me llamó la atención es que estas plataformas no solamente sirven para pedir un viaje o comida, sino que también pueden convertirse en una forma de generar ingresos para personas que tal vez no tenían muchas opciones antes. El estudio que vimos habla bastante de la flexibilidad y la independencia que valoran los conductores y repartidores, y eso me parece lógico porque una de las cosas atractivas de este tipo de trabajo es poder decidir cuándo conectarse y cuándo no."],
            ['type' => 'highlight', 'text' => "La tecnología no solo cambia lo que compramos, también cambia las formas en que podemos trabajar."],
            ['type' => 'heading', 'text' => "Pero tampoco todo es tan simple"],
            ['type' => 'paragraph', 'text' => "Al mismo tiempo, la clase me hizo pensar que tampoco se puede ver esto solamente como algo positivo. Si una plataforma genera oportunidades, también hay que preguntarse qué pasa con las personas que dependen de ella para vivir, qué responsabilidades tiene la empresa y qué debería hacer el Estado. El estudio incluso señala que existe una necesidad importante de regulación y seguridad jurídica alrededor de estas plataformas."],
            ['type' => 'paragraph', 'text' => "El artículo sobre Uber también me pareció interesante por eso, porque plantea que no se puede simplemente agarrar un modelo nuevo y tratarlo como si fuera exactamente igual al modelo tradicional. La tecnología cambió la forma en que se conectan las personas y ahora toca encontrar reglas que tengan sentido para esa realidad."],
            ['type' => 'paragraph', 'text' => "En lo personal, creo que eso es de las cosas que más me interesa del tema. Yo uso aplicaciones de este tipo y normalmente uno solamente piensa en que son más cómodas o rápidas, pero detrás de eso hay personas trabajando, empresas ganando dinero y también decisiones del Estado que terminan afectando cómo funciona todo. Entonces ya no lo veo simplemente como \"una app para pedir un Uber\", sino como otra forma en la que la tecnología está cambiando la economía."],
            ['type' => 'heading', 'text' => "La presentación de hoy"],
            ['type' => 'paragraph', 'text' => "También me gustó bastante la presentación de los compañeros. Sentí que fue bastante dinámica y que no se quedaron simplemente leyendo información de las diapositivas, sino que lograron hacer que el tema se sintiera más interesante. Creo que eso también tiene que ver con lo que estamos trabajando en el curso, porque no es solo aprender conceptos sino saber explicarlos y hablar de ellos de una manera que los demás los entiendan."],
            ['type' => 'paragraph', 'text' => "Al final, siento que la economía colaborativa tiene bastante potencial, pero también tiene cosas que todavía se tienen que resolver. Me parece interesante porque es un ejemplo bastante claro de cómo algo tecnológico puede terminar afectando directamente la economía y la vida de las personas. Y sinceramente, ahora cada vez que veo Uber o alguna otra plataforma parecida, siento que ya no la veo exactamente de la misma manera."],
        ],
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
