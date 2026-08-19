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

/**
 * Abreviatura de mes en mayúsculas ("AGO", "SEP"...) — usada en el
 * tratamiento editorial de fechas del timeline y la cabecera de semana.
 */
function format_month_abbr(int $month): string
{
    return mb_strtoupper(mb_substr(MESES_ES[$month], 0, 3));
}

/**
 * Rango de una semana académica completa (lunes a domingo) a partir de
 * su fecha de inicio, p. ej. "10 — 16 AGO 2026" o, si cruza de mes,
 * "31 AGO — 6 SEP 2026".
 */
function format_week_range(string $weekStart): string
{
    $start = new DateTime($weekStart);
    $end = (clone $start)->modify('+6 days');

    $startMonth = (int) $start->format('n');
    $endMonth = (int) $end->format('n');

    if ($startMonth === $endMonth) {
        return sprintf(
            '%d — %d %s %s',
            (int) $start->format('j'),
            (int) $end->format('j'),
            format_month_abbr($endMonth),
            $end->format('Y')
        );
    }

    return sprintf(
        '%d %s — %d %s %s',
        (int) $start->format('j'),
        format_month_abbr($startMonth),
        (int) $end->format('j'),
        format_month_abbr($endMonth),
        $end->format('Y')
    );
}

/**
 * Fecha de clase en formato corto con día de la semana, p. ej.
 * "Miércoles 12 AGO" — usada junto al rango de semana para distinguir
 * cuándo empieza la semana académica de cuándo es la sesión del curso.
 */
function format_class_short(string $classDate): string
{
    $date = new DateTime($classDate);

    return sprintf(
        '%s %d %s',
        ucfirst(format_weekday($classDate)),
        (int) $date->format('j'),
        format_month_abbr((int) $date->format('n'))
    );
}
