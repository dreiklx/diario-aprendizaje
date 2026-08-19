<?php

/**
 * Metadatos del curso. Única fuente de verdad para todo lo que el sitio
 * muestra sobre el curso (portada, pie de página, página "Acerca").
 *
 * Ajusta estos valores si cambian en el programa oficial del curso.
 */

return [
    'code'          => 'SR-0022',
    'name'          => 'Seminario de Realidad Nacional II',
    'subtitle'      => 'Producción y Desarrollo',
    'university'    => 'Universidad de Costa Rica',
    'campus'        => 'Sede del Caribe',
    'term'          => 'II Ciclo 2026',

    // Nombre y carné de quien escribe el diario. Deja 'author' en null
    // para no mostrar esta línea en el sitio.
    'author'        => 'Derek Farley Noguera',
    'student_id'    => 'C5F012',

    // Fecha ISO (YYYY-MM-DD) del lunes en que inicia la semana académica 1
    // (no la fecha de la primera clase — ver api/data/entries.php, campo
    // 'week_start' vs 'class_date') y cantidad total de semanas lectivas.
    // Ajusta estos dos valores al calendario oficial si difiere del
    // supuesto usado aquí (semanas lectivas consecutivas desde el lunes
    // 10 de agosto de 2026, sin descontar semanas de receso).
    'semester_start' => '2026-08-10',
    'total_weeks'    => 15,

    // Texto breve que explica el propósito del diario. Se usa en la
    // portada y en la página "Acerca del curso".
    'description' => 'Un espacio digital único donde se documenta, semana a semana, el proceso de aprendizaje del curso: lo reflexionado en clase, lo aprendido, las preguntas que quedan abiertas y su aplicación a la realidad nacional.',
];
