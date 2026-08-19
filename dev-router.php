<?php

/**
 * Router para el servidor embebido de PHP (uso local únicamente).
 *
 * Reproduce el comportamiento de Vercel en producción: si la ruta
 * pedida coincide con un archivo estático real (por ejemplo algo bajo
 * /assets), se sirve tal cual; en cualquier otro caso, la petición pasa
 * al front controller en api/index.php.
 *
 * Uso: php -S localhost:8000 dev-router.php
 * Ver CLAUDE.md, sección "Cómo ejecutar localmente".
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $path;

if ($path !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/api/index.php';
