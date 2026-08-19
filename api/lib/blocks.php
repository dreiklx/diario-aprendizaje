<?php

/**
 * Modelo de contenido por bloques y su renderer. Reemplaza los cuatro
 * campos fijos que tenía cada entrada (reflexion/aprendizaje/
 * cuestionamiento/aplicacion) por una lista de bloques tipados —
 * ver CLAUDE.md, "Modelo de contenido: bloques".
 *
 * REGLA DE SEGURIDAD CENTRAL DE ESTE ARCHIVO
 * -------------------------------------------
 * El texto de un bloque nunca es HTML. Es texto plano con un marcado
 * mínimo tipo Markdown (**negrita**, *cursiva*, ==destacado==,
 * [texto](url)). render_inline_markup() SIEMPRE escapa el texto
 * completo primero (e()) y SOLO DESPUÉS aplica sustituciones que
 * insertan un conjunto fijo y pequeño de etiquetas seguras. Ningún
 * dato del usuario llega jamás a imprimirse como HTML crudo. No cambies
 * ese orden (escapar -> transformar), y no agregues una ruta que
 * permita HTML arbitrario "por conveniencia".
 */

const BLOCK_TYPES = ['heading', 'paragraph', 'highlight', 'quote', 'list', 'divider', 'link', 'image'];

const BLOCK_MAX_COUNT = 40;
const BLOCK_TEXT_MAX = 4000;
const BLOCK_SHORT_MAX = 300;
const BLOCK_LIST_ITEM_MAX = 500;
const BLOCK_LIST_ITEMS_MAX = 30;

/**
 * Convierte marcado mínimo tipo Markdown en HTML seguro. Escapa todo el
 * texto primero; las sustituciones de abajo son las únicas fuentes de
 * etiquetas HTML en la salida.
 */
function render_inline_markup(string $text): string
{
    $html = e($text);

    // ==destacado== -> <mark> (usa el color de acento, no amarillo por defecto — ver components.css)
    $html = preg_replace('/==(.+?)==/s', '<mark class="inline-highlight">$1</mark>', $html);

    // **negrita** (antes que *cursiva* para no dejar asteriscos sueltos mal emparejados)
    $html = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $html);

    // *cursiva*
    $html = preg_replace('/\*(.+?)\*/s', '<em>$1</em>', $html);

    // [texto](url) — solo si la URL pasa sanitize_block_url(); si no, se deja el texto tal cual escapado.
    $html = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/', function (array $m): string {
        $url = sanitize_block_url(html_entity_decode($m[2], ENT_QUOTES));

        if ($url === null) {
            return $m[0];
        }

        $external = str_starts_with($url, 'http');
        $attrs = $external ? ' target="_blank" rel="noopener noreferrer"' : '';

        return '<a href="' . e($url) . '"' . $attrs . '>' . $m[1] . ($external ? ' ↗' : '') . '</a>';
    }, $html);

    return $html;
}

/**
 * Solo URLs http(s) absolutas o rutas relativas del propio sitio
 * (empiezan con "/"). Cualquier otro esquema (javascript:, data:, etc.)
 * queda rechazado — devuelve null y el llamador decide qué hacer.
 */
function sanitize_block_url(string $url): ?string
{
    $url = trim($url);

    if ($url === '') {
        return null;
    }

    if (preg_match('#^https?://[^\s<>"]+$#i', $url)) {
        return $url;
    }

    if (str_starts_with($url, '/') && !str_starts_with($url, '//') && !preg_match('/\s/', $url)) {
        return $url;
    }

    return null;
}

/** true si el bloque tiene contenido real (no solo estructura vacía). */
function block_has_content(array $block): bool
{
    switch ($block['type'] ?? '') {
        case 'heading':
        case 'paragraph':
        case 'highlight':
        case 'quote':
            return trim((string) ($block['text'] ?? '')) !== '';

        case 'list':
            foreach ((array) ($block['items'] ?? []) as $item) {
                if (trim((string) $item) !== '') {
                    return true;
                }
            }

            return false;

        case 'link':
            return trim((string) ($block['text'] ?? '')) !== ''
                && sanitize_block_url((string) ($block['url'] ?? '')) !== null;

        case 'image':
            return sanitize_block_url((string) ($block['url'] ?? '')) !== null;

        default:
            return false;
    }
}

/** true si la entrada tiene al menos un bloque con contenido real. */
function entry_has_content(array $entry): bool
{
    foreach ((array) ($entry['blocks'] ?? []) as $block) {
        if (is_array($block) && block_has_content($block)) {
            return true;
        }
    }

    return false;
}

/** Renderiza un bloque individual a HTML seguro. '' si no aplica. */
function render_block_html(array $block): string
{
    $type = $block['type'] ?? '';

    switch ($type) {
        case 'heading':
            return '<h2 class="block-heading">' . render_inline_markup((string) ($block['text'] ?? '')) . '</h2>';

        case 'paragraph':
            return '<p class="block-paragraph">' . render_inline_markup((string) ($block['text'] ?? '')) . '</p>';

        case 'highlight':
            return '<p class="block-highlight">' . render_inline_markup((string) ($block['text'] ?? '')) . '</p>';

        case 'quote':
            return '<blockquote class="block-quote">' . render_inline_markup((string) ($block['text'] ?? '')) . '</blockquote>';

        case 'list':
            $tag = ($block['style'] ?? '') === 'ordered' ? 'ol' : 'ul';
            $items = '';

            foreach ((array) ($block['items'] ?? []) as $item) {
                $item = trim((string) $item);

                if ($item === '') {
                    continue;
                }

                $items .= '<li>' . render_inline_markup($item) . '</li>';
            }

            return $items === '' ? '' : "<{$tag} class=\"block-list\">{$items}</{$tag}>";

        case 'divider':
            return '<hr class="block-divider">';

        case 'link':
            $url = sanitize_block_url((string) ($block['url'] ?? ''));
            $text = trim((string) ($block['text'] ?? ''));

            if ($url === null || $text === '') {
                return '';
            }

            $external = str_starts_with($url, 'http');
            $attrs = $external ? ' target="_blank" rel="noopener noreferrer"' : '';

            return '<p class="block-link"><a href="' . e($url) . '"' . $attrs . '>' . e($text) . ($external ? ' ↗' : '') . '</a></p>';

        case 'image':
            $url = sanitize_block_url((string) ($block['url'] ?? ''));

            if ($url === null) {
                return '';
            }

            $html = '<figure class="block-image"><img src="' . e($url) . '" alt="' . e((string) ($block['alt'] ?? '')) . '" loading="lazy">';
            $caption = trim((string) ($block['caption'] ?? ''));

            if ($caption !== '') {
                $html .= '<figcaption>' . render_inline_markup($caption) . '</figcaption>';
            }

            return $html . '</figure>';

        default:
            return '';
    }
}

/** Renderiza la lista completa de bloques de una entrada. */
function render_blocks_html(array $blocks): string
{
    $html = '';

    foreach ($blocks as $block) {
        if (!is_array($block)) {
            continue;
        }

        $rendered = render_block_html($block);

        if ($rendered !== '') {
            $html .= $rendered . "\n";
        }
    }

    return $html;
}

/**
 * Valida y limpia los bloques que llegan del editor (JSON del cliente).
 * Nunca confía en la forma/tipos que manda el navegador: cada campo se
 * vuelve a comprobar y recortar aquí, server-side, antes de que toque
 * el serializador o el renderer.
 */
function sanitize_blocks_input(array $rawBlocks): array
{
    $clean = [];

    foreach (array_slice($rawBlocks, 0, BLOCK_MAX_COUNT) as $rawBlock) {
        if (!is_array($rawBlock)) {
            continue;
        }

        $type = (string) ($rawBlock['type'] ?? '');

        if (!in_array($type, BLOCK_TYPES, true)) {
            continue;
        }

        switch ($type) {
            case 'heading':
            case 'paragraph':
            case 'highlight':
            case 'quote':
                $text = mb_substr(trim((string) ($rawBlock['text'] ?? '')), 0, BLOCK_TEXT_MAX);

                if ($text === '') {
                    break;
                }

                $clean[] = ['type' => $type, 'text' => $text];
                break;

            case 'list':
                $style = ($rawBlock['style'] ?? '') === 'ordered' ? 'ordered' : 'unordered';
                $items = [];

                foreach (array_slice((array) ($rawBlock['items'] ?? []), 0, BLOCK_LIST_ITEMS_MAX) as $item) {
                    $item = mb_substr(trim((string) $item), 0, BLOCK_LIST_ITEM_MAX);

                    if ($item !== '') {
                        $items[] = $item;
                    }
                }

                if (empty($items)) {
                    break;
                }

                $clean[] = ['type' => 'list', 'style' => $style, 'items' => $items];
                break;

            case 'divider':
                $clean[] = ['type' => 'divider'];
                break;

            case 'link':
                $url = sanitize_block_url((string) ($rawBlock['url'] ?? ''));
                $text = mb_substr(trim((string) ($rawBlock['text'] ?? '')), 0, BLOCK_SHORT_MAX);

                if ($url === null || $text === '') {
                    break;
                }

                $clean[] = ['type' => 'link', 'text' => $text, 'url' => $url];
                break;

            case 'image':
                $url = sanitize_block_url((string) ($rawBlock['url'] ?? ''));

                if ($url === null) {
                    break;
                }

                $clean[] = [
                    'type' => 'image',
                    'url' => $url,
                    'alt' => mb_substr(trim((string) ($rawBlock['alt'] ?? '')), 0, BLOCK_SHORT_MAX),
                    'caption' => mb_substr(trim((string) ($rawBlock['caption'] ?? '')), 0, BLOCK_SHORT_MAX),
                ];
                break;
        }
    }

    return $clean;
}
