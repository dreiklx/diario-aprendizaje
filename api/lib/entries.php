<?php

require_once __DIR__ . '/dates.php';

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
        usort($entries, fn ($a, $b) => $a['number'] <=> $b['number']);
    }

    return $entries;
}

/**
 * Una entrada por número de semana, o null si no existe.
 */
function get_entry(int $number): ?array
{
    foreach (get_entries() as $entry) {
        if ($entry['number'] === $number) {
            return $entry;
        }
    }

    return null;
}

/**
 * Estado derivado de una entrada:
 * - completada: tiene contenido de reflexión.
 * - disponible: la fecha ya llegó pero aún no se ha escrito.
 * - proxima: la fecha todavía no llega.
 */
function entry_status(array $entry, ?string $today = null): string
{
    $today ??= today_iso();

    if (!empty(trim((string) ($entry['reflexion'] ?? '')))) {
        return STATUS_COMPLETADA;
    }

    return is_on_or_before($entry['date'], $today) ? STATUS_DISPONIBLE : STATUS_PROXIMA;
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
 * La semana "actual" del semestre: la última disponible/completada cuya
 * fecha ya llegó, o la primera si el curso aún no ha iniciado.
 */
function current_week_number(?string $today = null): int
{
    $today ??= today_iso();
    $current = 1;

    foreach (get_entries() as $entry) {
        if (is_on_or_before($entry['date'], $today)) {
            $current = $entry['number'];
        }
    }

    return $current;
}
