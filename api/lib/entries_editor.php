<?php

/**
 * Lee y reescribe api/data/entries.php de forma quirúrgica: preserva el
 * docblock y todo lo que hay antes de "return [" tal cual, y regenera
 * solo el cuerpo del arreglo en el mismo estilo en que está escrito a
 * mano en el resto del proyecto (ver CLAUDE.md, "Cómo funciona el
 * editor"). Nunca usa var_export ni serializadores genéricos — eso
 * destruiría el formato y los comentarios.
 *
 * Sobre el eval(): el contenido que se parsea viene de GitHub, pero
 * SOLO de la ruta fija GITHUB_ENTRIES_PATH en GITHUB_REPO — el único
 * lugar donde ese archivo puede cambiar es a través de este mismo
 * editor (protegido por contraseña + token de GitHub con permisos
 * mínimos). Es el mismo nivel de confianza que un `require` normal del
 * archivo local; la única diferencia es que se lee la versión más
 * reciente del repositorio en vez de la copia empaquetada en el
 * deployment, que es justamente el punto: evitar pisar una edición
 * hecha directamente en GitHub o desde otra pestaña del editor.
 */

class EntriesParseException extends RuntimeException
{
}

/**
 * @return array{preamble:string, entries:array}
 */
function parse_entries_source(string $source): array
{
    $marker = 'return [';
    $pos = strpos($source, $marker);

    if ($pos === false) {
        throw new EntriesParseException('No se encontró "return [" en entries.php.');
    }

    $preamble = substr($source, 0, $pos);
    $code = substr($source, strlen('<?php'));

    try {
        $entries = eval($code);
    } catch (Throwable $e) {
        throw new EntriesParseException('entries.php no se pudo interpretar: ' . $e->getMessage());
    }

    if (!is_array($entries)) {
        throw new EntriesParseException('entries.php no devolvió un arreglo.');
    }

    return ['preamble' => $preamble, 'entries' => $entries];
}

function php_single_quote(string $value): string
{
    return "'" . str_replace(['\\', "'"], ['\\\\', "\\'"], $value) . "'";
}

function php_double_quote_multiline(string $value): string
{
    $escaped = str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value);
    $escaped = str_replace(["\r\n", "\r", "\n"], '\\n', $escaped);

    return '"' . $escaped . '"';
}

function php_nullable_scalar(?string $value, bool $multiline): string
{
    if ($value === null || trim($value) === '') {
        return 'null';
    }

    return $multiline ? php_double_quote_multiline($value) : php_single_quote($value);
}

function format_evidencia_literal($evidencia): string
{
    if (!is_array($evidencia) || empty($evidencia['url'])) {
        return 'null';
    }

    $label = php_single_quote((string) ($evidencia['label'] ?? 'Ver evidencia'));
    $url = php_single_quote((string) $evidencia['url']);

    return "['label' => {$label}, 'url' => {$url}]";
}

/** Reconstruye "return [ ... ];" completo a partir del arreglo de entradas. */
function format_entries_body(array $entries): string
{
    $out = "return [\n";

    foreach ($entries as $entry) {
        $out .= "    [\n";
        $out .= "        'week' => " . (int) $entry['week'] . ",\n";
        $out .= "        'week_start' => " . php_single_quote((string) $entry['week_start']) . ",\n";
        $out .= "        'class_date' => " . php_single_quote((string) $entry['class_date']) . ",\n";
        $out .= "        'title' => " . php_nullable_scalar($entry['title'] ?? null, false) . ",\n";
        $out .= "        'theme' => " . php_nullable_scalar($entry['theme'] ?? null, false) . ",\n";
        $out .= "        'reflexion' => " . php_nullable_scalar($entry['reflexion'] ?? null, true) . ",\n";
        $out .= "        'aprendizaje' => " . php_nullable_scalar($entry['aprendizaje'] ?? null, true) . ",\n";
        $out .= "        'cuestionamiento' => " . php_nullable_scalar($entry['cuestionamiento'] ?? null, true) . ",\n";
        $out .= "        'aplicacion' => " . php_nullable_scalar($entry['aplicacion'] ?? null, true) . ",\n";
        $out .= "        'evidencia' => " . format_evidencia_literal($entry['evidencia'] ?? null) . ",\n";
        $out .= "    ],\n";
    }

    $out .= "];\n";

    return $out;
}

/** Normaliza el texto de un <textarea>: CRLF -> LF, recorta, vacío -> null. */
function normalize_editor_text(string $value): ?string
{
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    $value = trim($value);

    return $value === '' ? null : $value;
}

const EDITABLE_ENTRY_FIELDS = ['title', 'theme', 'reflexion', 'aprendizaje', 'cuestionamiento', 'aplicacion'];

/**
 * Aplica los campos editables a la entrada $week dentro de $entries, sin
 * tocar ninguna otra entrada ni los campos de fecha/semana/evidencia.
 */
function apply_entry_edit(array $entries, int $week, array $submittedFields): array
{
    foreach ($entries as $i => $entry) {
        if ((int) $entry['week'] !== $week) {
            continue;
        }

        foreach (EDITABLE_ENTRY_FIELDS as $field) {
            $entries[$i][$field] = normalize_editor_text((string) ($submittedFields[$field] ?? ''));
        }

        break;
    }

    return $entries;
}

function find_entry_by_week(array $entries, int $week): ?array
{
    foreach ($entries as $entry) {
        if ((int) $entry['week'] === $week) {
            return $entry;
        }
    }

    return null;
}
