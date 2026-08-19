<?php

require_once __DIR__ . '/dates.php';
require_once __DIR__ . '/blocks.php';

/**
 * Acceso y lógica de negocio sobre las entradas del diario. Todo el
 * cálculo de estado y progreso vive aquí para que ninguna plantilla
 * tenga que "decidir" nada por su cuenta.
 */

const STATUS_COMPLETADA = 'completada';
const STATUS_DISPONIBLE = 'disponible';
const STATUS_PROXIMA = 'proxima';

/**
 * Todas las entradas del diario, ordenadas por número de semana.
 *
 * @return array<int, array>
 */
function get_entries(): array
{
    static $entries = null;

    if ($entries === null) {
        $entries = require __DIR__ . '/../data/entries.php';
        usort($entries, fn ($a, $b) => $a['week'] <=> $b['week']);
    }

    return $entries;
}

/**
 * Una entrada por número de semana, o null si no existe.
 */
function get_entry(int $week): ?array
{
    foreach (get_entries() as $entry) {
        if ($entry['week'] === $week) {
            return $entry;
        }
    }

    return null;
}

/**
 * Estado derivado de una entrada:
 * - completada: tiene al menos un bloque con contenido real (ver
 *   entry_has_content() en blocks.php).
 * - disponible: la clase (class_date) ya ocurrió pero aún no se ha escrito.
 * - proxima: la clase todavía no ocurre.
 *
 * Se compara contra class_date (el día real de la sesión), no contra
 * week_start (el lunes en que arranca la semana académica) — hasta que
 * no hay clase, no hay nada que reflexionar.
 */
function entry_status(array $entry, ?string $today = null): string
{
    $today ??= today_iso();

    if (entry_has_content($entry)) {
        return STATUS_COMPLETADA;
    }

    return is_on_or_before($entry['class_date'], $today) ? STATUS_DISPONIBLE : STATUS_PROXIMA;
}

/**
 * Etiqueta legible en español para un estado.
 */
function status_label(string $status): string
{
    return match ($status) {
        STATUS_COMPLETADA => 'Completada',
        STATUS_DISPONIBLE => 'Disponible',
        STATUS_PROXIMA => 'Próxima',
        default => ucfirst($status),
    };
}

/**
 * Estadísticas de avance del diario: completadas, total y porcentaje.
 * El sitio nunca debe mostrar un número de progreso escrito a mano; todo
 * debe pasar por esta función.
 *
 * @return array{completed:int, total:int, percent:int}
 */
function progress_stats(): array
{
    $entries = get_entries();
    $total = count($entries);
    $completed = 0;

    foreach ($entries as $entry) {
        if (entry_status($entry) === STATUS_COMPLETADA) {
            $completed++;
        }
    }

    return [
        'completed' => $completed,
        'total' => $total,
        'percent' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
    ];
}

/**
 * La semana académica "actual" del semestre: la última cuyo lunes
 * (week_start) ya llegó, o la primera si el curso aún no ha iniciado.
 * Se basa en week_start (no en class_date) porque la semana ya está en
 * curso desde el lunes, aunque la clase sea hasta el miércoles.
 */
function current_week_number(?string $today = null): int
{
    $today ??= today_iso();
    $current = 1;

    foreach (get_entries() as $entry) {
        if (is_on_or_before($entry['week_start'], $today)) {
            $current = $entry['week'];
        }
    }

    return $current;
}
