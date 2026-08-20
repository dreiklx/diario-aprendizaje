<?php

/**
 * Envío público de un comentario al foro de una entrada. A propósito NO
 * vive en editor_actions.php: esta es una acción pública, sin
 * autenticación, mientras que editor_actions.php es exclusivamente la
 * lógica del editor privado (ver CLAUDE.md, sección 15 — no mezclar más
 * de lo necesario). Comparte con el editor privado el cliente de GitHub
 * y el serializador de entries.php, nunca la autenticación.
 */

require_once __DIR__ . '/github.php';
require_once __DIR__ . '/entries_editor.php';
require_once __DIR__ . '/comments.php';
require_once __DIR__ . '/blocks.php';

/**
 * @return array{success:bool, errors:array<int,string>, name:string, content:string, entry?:array, marker?:string}
 */
function handle_comment_submit(int $week, array $localEntry): array
{
    $rawName = (string) ($_POST['name'] ?? '');
    $rawContent = (string) ($_POST['content'] ?? '');

    if (!entry_has_content($localEntry)) {
        return ['success' => false, 'errors' => ['Esta semana todavía no tiene una reflexión publicada.'], 'name' => $rawName, 'content' => $rawContent];
    }

    // Honeypot: un campo fuera de pantalla que ningún visitante real llena.
    if (trim((string) ($_POST['comments_hp'] ?? '')) !== '') {
        return ['success' => false, 'errors' => ['No se pudo publicar el comentario.'], 'name' => $rawName, 'content' => $rawContent];
    }

    if (!verify_comment_form_token((string) ($_POST['form_token'] ?? ''))) {
        return ['success' => false, 'errors' => ['El formulario expiró. Recargá la página e intentá de nuevo.'], 'name' => $rawName, 'content' => $rawContent];
    }

    $cooldown = comment_cooldown_seconds_remaining();

    if ($cooldown > 0) {
        return ['success' => false, 'errors' => ["Esperá unos segundos antes de comentar de nuevo ({$cooldown}s)."], 'name' => $rawName, 'content' => $rawContent];
    }

    $name = sanitize_comment_name($rawName);
    $content = sanitize_comment_content($rawContent);
    $errors = [];

    if ($name === '') {
        $errors[] = 'El nombre es obligatorio.';
    } elseif (mb_strlen($name) > COMMENT_NAME_MAX) {
        $errors[] = 'El nombre es demasiado largo (máximo ' . COMMENT_NAME_MAX . ' caracteres).';
    }

    if ($content === '') {
        $errors[] = 'El comentario es obligatorio.';
    } elseif (mb_strlen($content) > COMMENT_TEXT_MAX) {
        $errors[] = 'El comentario es demasiado largo (máximo ' . COMMENT_TEXT_MAX . ' caracteres).';
    }

    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors, 'name' => $rawName, 'content' => $rawContent];
    }

    $comment = [
        'id' => new_comment_id(),
        'name' => $name,
        'content' => $content,
        'created_at' => comment_timestamp_now(),
    ];

    // Los comentarios son un append puro: a diferencia de editar una
    // reflexión (donde un 409 significa "alguien más está editando, no
    // sobrescribas su trabajo"), dos comentarios concurrentes de
    // personas distintas no se pisan entre sí — reintentar con el sha
    // más reciente es seguro y evita mostrarle un error de concurrencia
    // a un visitante que solo quería comentar.
    for ($attempt = 0; $attempt < 3; $attempt++) {
        try {
            $file = github_get_entries_file();
            $parsed = parse_entries_source($file['content']);
            $freshEntry = find_entry_by_week($parsed['entries'], $week);

            if ($freshEntry === null) {
                return ['success' => false, 'errors' => ['Esa semana ya no existe.'], 'name' => $rawName, 'content' => $rawContent];
            }

            if (comment_count($freshEntry) >= COMMENT_MAX_COUNT_PER_ENTRY) {
                return ['success' => false, 'errors' => ['Esta semana ya alcanzó el máximo de comentarios.'], 'name' => $rawName, 'content' => $rawContent];
            }

            $updatedEntries = append_comment_to_entry($parsed['entries'], $week, $comment);
            $newSource = $parsed['preamble'] . format_entries_body($updatedEntries);
            $result = github_update_entries_file($newSource, $file['sha'], 'Nuevo comentario de "' . $name . '" — semana ' . $week);

            if ($result['conflict']) {
                continue;
            }

            if (!$result['ok']) {
                return ['success' => false, 'errors' => ['No se pudo publicar el comentario (código ' . $result['status'] . ').'], 'name' => $rawName, 'content' => $rawContent];
            }

            set_comment_cooldown_cookie();

            return [
                'success' => true,
                'errors' => [],
                'name' => '',
                'content' => '',
                'entry' => find_entry_by_week($updatedEntries, $week),
                'marker' => $comment['id'],
            ];
        } catch (GitHubApiException|EntriesParseException $e) {
            return ['success' => false, 'errors' => ['No se pudo publicar el comentario: ' . $e->getMessage()], 'name' => $rawName, 'content' => $rawContent];
        }
    }

    return ['success' => false, 'errors' => ['Hubo mucha actividad al mismo tiempo. Intentá de nuevo en unos segundos.'], 'name' => $rawName, 'content' => $rawContent];
}
