<?php

/**
 * Utilidades de fecha. Todas las fechas del diario se almacenan en
 * formato ISO (YYYY-MM-DD) en api/data/entries.php; estas funciones son
 * la única forma en la que el resto del sitio debe leer, comparar o
 * formatear esas fechas.
 */

const MESES_ES = [
    1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
    5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
    9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
];

const DIAS_ES = [
    'Monday' => 'lunes', 'Tuesday' => 'martes', 'Wednesday' => 'miércoles',
    'Thursday' => 'jueves', 'Friday' => 'viernes', 'Saturday' => 'sábado',
    'Sunday' => 'domingo',
];

/**
 * Fecha de "hoy" en formato ISO. Centralizada para que toda la lógica de
 * estado (pasado/futuro) use la misma referencia temporal.
 */
function today_iso(): string
{
    return date('Y-m-d');
}

/**
 * Convierte una fecha ISO (YYYY-MM-DD) a formato largo en español,
 * p. ej. "19 de agosto de 2026".
 */
function format_date_long(string $isoDate): string
{
    [$year, $month, $day] = explode('-', $isoDate);
    $day = (int) $day;
    $month = (int) $month;

    return sprintf('%d de %s de %s', $day, MESES_ES[$month], $year);
}

/**
 * Versión corta: "19 ago" — usada en espacios reducidos (índice, chips).
 */
function format_date_short(string $isoDate): string
{
    [$year, $month, $day] = explode('-', $isoDate);
    $month = (int) $month;

    return sprintf('%d %s', (int) $day, mb_substr(MESES_ES[$month], 0, 3));
}

/**
 * Nombre del día de la semana en español para una fecha ISO.
 */
function format_weekday(string $isoDate): string
{
    $weekday = date('l', strtotime($isoDate));

    return DIAS_ES[$weekday] ?? $weekday;
}

/**
 * true si la fecha ISO ya pasó (o es hoy), comparada contra $today (ISO).
 */
function is_on_or_before(string $isoDate, string $today): bool
{
    return $isoDate <= $today;
}
