<?php

/**
 * Enrutador mínimo basado en la ruta de la petición. No hay dependencias
 * externas: sólo coincidencia de patrones simples sobre el path.
 *
 * Devuelve un arreglo ['page' => string, 'params' => array] que
 * api/index.php usa para decidir qué plantilla renderizar.
 */
function resolve_route(string $requestUri): array
{
    $path = parse_url($requestUri, PHP_URL_PATH) ?? '/';
    $path = rawurldecode($path);
    $path = '/' . trim($path, '/');

    if ($path === '/') {
        return ['page' => 'home', 'params' => []];
    }

    if ($path === '/curso') {
        return ['page' => 'course', 'params' => []];
    }

    if (preg_match('#^/semana/(\d+)$#', $path, $matches)) {
        return ['page' => 'week', 'params' => ['number' => (int) $matches[1]]];
    }

    return ['page' => 'not-found', 'params' => []];
}
