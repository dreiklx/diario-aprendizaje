<?php

/**
 * Motor de plantillas mínimo. PHP mismo es el lenguaje de plantillas:
 * estas funciones sólo resuelven rutas de archivo y escapado, sin
 * introducir una sintaxis propia.
 */

/** Escapa texto para salida HTML segura. Usar SIEMPRE con datos dinámicos. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * URL de un asset estático con un parámetro de versión basado en su
 * fecha de modificación, para invalidar la caché del navegador sin
 * necesidad de renombrar archivos a mano.
 */
function asset_url(string $path): string
{
    $fsPath = __DIR__ . '/../../assets/' . ltrim($path, '/');
    $version = is_file($fsPath) ? filemtime($fsPath) : time();

    return '/assets/' . ltrim($path, '/') . '?v=' . $version;
}

/**
 * Incluye una plantilla en su propio ámbito de variables y devuelve el
 * HTML generado como string.
 */
function render_partial(string $template, array $data = []): string
{
    $path = __DIR__ . '/../templates/' . $template . '.php';

    if (!is_file($path)) {
        throw new RuntimeException("Plantilla no encontrada: {$template}");
    }

    extract($data, EXTR_SKIP);
    ob_start();
    require $path;

    return ob_get_clean();
}

/**
 * Renderiza una página completa dentro del layout general y envía la
 * salida final.
 */
function render_page(string $template, array $data = []): void
{
    $content = render_partial('pages/' . $template, $data);

    extract($data, EXTR_SKIP);
    require __DIR__ . '/../templates/layout.php';
}
