<?php

/**
 * Front controller único del sitio. Toda petición (excepto archivos
 * estáticos en /assets, servidos directamente por Vercel / el servidor
 * PHP embebido) pasa por aquí. Ver CLAUDE.md, sección "Arquitectura".
 */

declare(strict_types=1);

require_once __DIR__ . '/lib/dates.php';
require_once __DIR__ . '/lib/entries.php';
require_once __DIR__ . '/lib/render.php';
require_once __DIR__ . '/lib/router.php';

$course = require __DIR__ . '/data/course.php';
$route = resolve_route($_SERVER['REQUEST_URI'] ?? '/');

switch ($route['page']) {
    case 'home':
        render_page('home', [
            'course' => $course,
            'entries' => get_entries(),
            'progress' => progress_stats(),
            'currentWeek' => current_week_number(),
            'pageTitle' => null,
            'pageDescription' => $course['description'],
        ]);
        break;

    case 'course':
        render_page('course', [
            'course' => $course,
            'progress' => progress_stats(),
            'pageTitle' => 'Acerca del curso',
            'pageDescription' => $course['description'],
        ]);
        break;

    case 'week':
        $number = $route['params']['number'];
        $entry = get_entry($number);

        if ($entry === null || $number < 1 || $number > $course['total_weeks']) {
            http_response_code(404);
            render_page('not-found', [
                'course' => $course,
                'pageTitle' => 'Página no encontrada',
                'pageDescription' => null,
            ]);
            break;
        }

        $entries = get_entries();
        $index = array_search($number, array_column($entries, 'number'), true);

        render_page('week', [
            'course' => $course,
            'entry' => $entry,
            'status' => entry_status($entry),
            'entries' => $entries,
            'prevEntry' => $entries[$index - 1] ?? null,
            'nextEntry' => $entries[$index + 1] ?? null,
            'pageTitle' => 'Semana ' . $number . ($entry['title'] ? ' — ' . $entry['title'] : ''),
            'pageDescription' => $entry['theme'] ?? $course['description'],
        ]);
        break;

    default:
        http_response_code(404);
        render_page('not-found', [
            'course' => $course,
            'pageTitle' => 'Página no encontrada',
            'pageDescription' => null,
        ]);
        break;
}
