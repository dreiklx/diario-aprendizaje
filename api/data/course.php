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

    // Nombre de quien escribe el diario. Déjalo en null para no mostrarlo
    // en el sitio, o complétalo con tu nombre cuando quieras que aparezca.
    'author'        => null,

    // Fecha ISO (YYYY-MM-DD) de inicio de lecciones del ciclo y cantidad
    // total de semanas lectivas. Estos dos valores controlan el cálculo
    // automático de progreso, por lo que deben ajustarse al calendario
    // oficial de la profesora si difiere del supuesto usado aquí
    // (semanas lectivas consecutivas, sin descontar semanas de receso).
    'semester_start' => '2026-08-03',
    'total_weeks'    => 15,

    // Texto breve que explica el propósito del diario. Se usa en la
    // portada y en la página "Acerca del curso".
    'description' => 'Un espacio digital único donde se documenta, semana a semana, el proceso de aprendizaje del curso: lo reflexionado en clase, lo aprendido, las preguntas que quedan abiertas y su aplicación a la realidad nacional.',
];
