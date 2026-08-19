<?php

/**
 * Lee y reescribe api/data/entries.php de forma quirúrgica: preserva el
 * docblock y todo lo que hay antes de "return [" tal cual, y regenera
 * solo el cuerpo del arreglo en el mismo estilo en que está escrito a
 * mano en el resto del proyecto (ver CLAUDE.md, "Editor privado").
 * Nunca usa var_export ni serializadores genéricos — eso destruiría el
 * formato y los comentarios.
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

require_once __DIR__ . '/blocks.php';

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

function php_nullable_scalar(?string $value): string
{
    if ($value === null || trim($value) === '') {
        return 'null';
    }

    return php_single_quote($value);
}

/** Igual que php_nullable_scalar() pero para texto que puede tener saltos de línea. */
function php_nullable_multiline(?string $value): string
{
    if ($value === null || trim($value) === '') {
        return 'null';
    }

    return php_double_quote_multiline($value);
}

/** Serializa un único bloque como literal de arreglo PHP, en una línea. */
function format_block_literal(array $block): string
{
    $type = (string) ($block['type'] ?? '');

    switch ($type) {
        case 'heading':
        case 'paragraph':
        case 'highlight':
        case 'quote':
            return "['type' => " . php_single_quote($type) . ", 'text' => " . php_double_quote_multiline((string) $block['text']) . ']';

        case 'list':
            $items = array_map(
                fn ($item) => php_double_quote_multiline((string) $item),
                $block['items']
            );

            return "['type' => 'list', 'style' => " . php_single_quote((string) $block['style']) . ", 'items' => [" . implode(', ', $items) . ']]';

        case 'divider':
            return "['type' => 'divider']";

        case 'link':
            return "['type' => 'link', 'text' => " . php_double_quote_multiline((string) $block['text']) . ", 'url' => " . php_single_quote((string) $block['url']) . ']';

        case 'image':
            return "['type' => 'image', 'url' => " . php_single_quote((string) $block['url'])
                . ", 'alt' => " . php_double_quote_multiline((string) ($block['alt'] ?? ''))
                . ", 'caption' => " . php_double_quote_multiline((string) ($block['caption'] ?? '')) . ']';

        default:
            // Inalcanzable si el bloque pasó por sanitize_blocks_input() antes.
            return "['type' => 'paragraph', 'text' => '']";
    }
}

function format_blocks_literal(array $blocks): string
{
    if (empty($blocks)) {
        return '[]';
    }

    $out = "[\n";

    foreach ($blocks as $block) {
        $out .= '            ' . format_block_literal($block) . ",\n";
    }

    return $out . '        ]';
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
        $out .= "        'title' => " . php_nullable_scalar($entry['title'] ?? null) . ",\n";
        $out .= "        'theme' => " . php_nullable_scalar($entry['theme'] ?? null) . ",\n";
        $out .= "        'blocks' => " . format_blocks_literal((array) ($entry['blocks'] ?? [])) . ",\n";
        $out .= "        'teacher_comment' => " . php_nullable_multiline($entry['teacher_comment'] ?? null) . ",\n";
        $out .= "    ],\n";
    }

    return $out . "];\n";
}

/** Normaliza un campo de texto simple (título/tema): recorta, vacío -> null. */
function normalize_editor_text(string $value): ?string
{
    $value = trim(str_replace(["\r\n", "\r"], "\n", $value));

    return $value === '' ? null : $value;
}

/**
 * Aplica título, tema y bloques (ya saneados por sanitize_blocks_input())
 * a la entrada $week dentro de $entries, sin tocar ninguna otra entrada
 * ni los campos de fecha/semana.
 */
function apply_entry_edit(array $entries, int $week, string $title, string $theme, array $sanitizedBlocks): array
{
    foreach ($entries as $i => $entry) {
        if ((int) $entry['week'] !== $week) {
            continue;
        }

        $entries[$i]['title'] = normalize_editor_text($title);
        $entries[$i]['theme'] = normalize_editor_text($theme);
        $entries[$i]['blocks'] = $sanitizedBlocks;
        break;
    }

    return $entries;
}

/**
 * Aplica solo el comentario de la profesora a la entrada $week — nunca
 * toca título/tema/bloques. Es un flujo de edición separado del de la
 * reflexión (mismo archivo, mismo mecanismo de guardado, otro campo).
 */
function apply_teacher_comment_edit(array $entries, int $week, string $comment): array
{
    foreach ($entries as $i => $entry) {
        if ((int) $entry['week'] !== $week) {
            continue;
        }

        $entries[$i]['teacher_comment'] = normalize_editor_text($comment);
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
