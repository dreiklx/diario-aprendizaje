<?php

/**
 * Cliente mínimo de la API de contenidos de GitHub. Solo dos
 * operaciones, ambas limitadas a un único archivo del repositorio
 * (ver GITHUB_ENTRIES_PATH) — el editor no tiene forma de tocar
 * ningún otro archivo, ni aunque quisiera: la ruta está fija aquí,
 * no llega como parámetro desde el navegador.
 *
 * El token vive únicamente en la variable de entorno GITHUB_TOKEN
 * (server-side). Nunca se envía al cliente ni se escribe en logs.
 */

const GITHUB_API = 'https://api.github.com';
const GITHUB_REPO = 'dreiklx/diario-aprendizaje';
const GITHUB_BRANCH = 'master';
const GITHUB_ENTRIES_PATH = 'api/data/entries.php';

class GitHubApiException extends RuntimeException
{
}

function github_token(): string
{
    return (string) getenv('GITHUB_TOKEN');
}

/**
 * @return array{status:int, body:?array}
 */
function github_request(string $method, string $path, ?array $body = null): array
{
    $headers = [
        'Authorization: Bearer ' . github_token(),
        'Accept: application/vnd.github+json',
        'X-GitHub-Api-Version: 2022-11-28',
        'User-Agent: diario-aprendizaje-editor',
    ];

    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
    }

    $options = [
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $headers),
            'content' => $body !== null ? json_encode($body) : '',
            'ignore_errors' => true,
            'timeout' => 15,
        ],
    ];

    $result = @file_get_contents(GITHUB_API . $path, false, stream_context_create($options));

    $status = 0;
    foreach ($http_response_header ?? [] as $header) {
        if (preg_match('#^HTTP/\S+\s+(\d+)#', $header, $matches)) {
            $status = (int) $matches[1];
        }
    }

    if ($result === false) {
        throw new GitHubApiException('No se pudo contactar a GitHub.');
    }

    $decoded = json_decode($result, true);

    return ['status' => $status, 'body' => is_array($decoded) ? $decoded : null];
}

/**
 * @return array{content:string, sha:string}
 */
function github_get_entries_file(): array
{
    $response = github_request(
        'GET',
        '/repos/' . GITHUB_REPO . '/contents/' . GITHUB_ENTRIES_PATH . '?ref=' . GITHUB_BRANCH
    );

    if ($response['status'] !== 200 || !isset($response['body']['content'], $response['body']['sha'])) {
        throw new GitHubApiException('No se pudo leer entries.php desde GitHub (código ' . $response['status'] . ').');
    }

    $content = base64_decode((string) $response['body']['content']);

    if ($content === false) {
        throw new GitHubApiException('El contenido recibido de GitHub no es válido.');
    }

    return ['content' => $content, 'sha' => (string) $response['body']['sha']];
}

/**
 * Actualiza entries.php con control de concurrencia optimista: si $sha
 * ya no coincide con la versión actual en GitHub (alguien más lo
 * modificó desde que se cargó el formulario), GitHub responde 409 y no
 * escribe nada — así nunca se sobrescribe un cambio ajeno en silencio.
 *
 * @return array{ok:bool, conflict:bool, status:int}
 */
function github_update_entries_file(string $newContent, string $sha, string $commitMessage): array
{
    $response = github_request('PUT', '/repos/' . GITHUB_REPO . '/contents/' . GITHUB_ENTRIES_PATH, [
        'message' => $commitMessage,
        'content' => base64_encode($newContent),
        'sha' => $sha,
        'branch' => GITHUB_BRANCH,
    ]);

    return [
        'ok' => in_array($response['status'], [200, 201], true),
        'conflict' => $response['status'] === 409,
        'status' => $response['status'],
    ];
}
