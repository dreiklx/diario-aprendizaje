<?php

/**
 * Controladores del editor privado. Separados de api/index.php porque,
 * a diferencia de las demás rutas (solo lectura de datos locales), esta
 * lógica habla con servicios externos (GitHub) y maneja autenticación —
 * merece su propio archivo. Cada función renderiza su propia respuesta
 * y termina la petición: no devuelven datos hacia arriba.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/github.php';
require_once __DIR__ . '/entries_editor.php';
require_once __DIR__ . '/comments.php';

function handle_editor_home(array $course): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        handle_editor_login_submit($course);

        return;
    }

    if (!is_editor_authenticated()) {
        render_page('editor-login', [
            'course' => $course,
            'pageTitle' => 'Editor · Diario',
            'pageDescription' => null,
            'private' => true,
            'loginError' => null,
            'next' => sanitize_editor_next_path($_GET['next'] ?? null),
        ]);

        return;
    }

    render_page('editor-weeks', [
        'course' => $course,
        'entries' => get_entries(),
        'pageTitle' => 'Editor · Diario',
        'pageDescription' => null,
        'private' => true,
    ]);
}

function handle_editor_login_submit(array $course): void
{
    $password = (string) ($_POST['password'] ?? '');
    $next = sanitize_editor_next_path($_POST['next'] ?? null);

    if ($password !== '' && verify_editor_password($password)) {
        set_editor_cookie(issue_editor_session_token());
        header('Location: ' . $next, true, 303);

        return;
    }

    // Retraso deliberado: el propio bcrypt de password_verify() ya cuesta
    // ~100-300ms, pero sumamos un poco más para desalentar fuerza bruta
    // sin necesitar un almacén de intentos (no hay estado entre peticiones).
    usleep(400_000);

    render_page('editor-login', [
        'course' => $course,
        'pageTitle' => 'Editor · Diario',
        'pageDescription' => null,
        'private' => true,
        'loginError' => 'Contraseña incorrecta.',
        'next' => $next,
    ]);
}

function handle_editor_logout(): void
{
    clear_editor_cookie();
    header('Location: /editar', true, 303);
}

function handle_editor_week(array $course, int $week): void
{
    require_editor_auth();

    if ($week < 1 || $week > (int) $course['total_weeks']) {
        header('Location: /editar', true, 303);

        return;
    }

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        handle_editor_week_save($course, $week);

        return;
    }

    handle_editor_week_form($course, $week, null, null, null);
}

function handle_editor_week_form(array $course, int $week, ?string $success, ?string $error, ?array $formValues): void
{
    try {
        $file = github_get_entries_file();
        $parsed = parse_entries_source($file['content']);
        $entry = find_entry_by_week($parsed['entries'], $week);
    } catch (GitHubApiException|EntriesParseException $e) {
        render_page('editor-week', [
            'course' => $course,
            'pageTitle' => 'Editor · Semana ' . $week,
            'pageDescription' => null,
            'private' => true,
            'entry' => ['week' => $week, 'week_start' => date('Y-m-d'), 'class_date' => date('Y-m-d')],
            'sha' => '',
            'csrf' => editor_csrf_token(),
            'successMessage' => null,
            'errorMessage' => 'No se pudo cargar la entrada desde GitHub: ' . $e->getMessage(),
            'formValues' => [],
        ]);

        return;
    }

    if ($entry === null) {
        header('Location: /editar', true, 303);

        return;
    }

    render_page('editor-week', [
        'course' => $course,
        'pageTitle' => 'Editor · Semana ' . $week,
        'pageDescription' => null,
        'private' => true,
        'entry' => $entry,
        'sha' => $file['sha'],
        'csrf' => editor_csrf_token(),
        'successMessage' => $success,
        'errorMessage' => $error,
        'formValues' => $formValues ?? $entry,
    ]);
}

function handle_editor_week_save(array $course, int $week): void
{
    if (!verify_editor_csrf((string) ($_POST['csrf'] ?? ''))) {
        handle_editor_week_form($course, $week, null, 'La sesión expiró o la petición no es válida. Volvé a intentarlo.', null);

        return;
    }

    $sha = (string) ($_POST['sha'] ?? '');
    $title = (string) ($_POST['title'] ?? '');
    $theme = (string) ($_POST['theme'] ?? '');

    if (mb_strlen($title) > 160 || mb_strlen($theme) > 200) {
        handle_editor_week_form($course, $week, null, 'El título o el tema son demasiado largos.', null);

        return;
    }

    $rawBlocks = json_decode((string) ($_POST['blocks_json'] ?? '[]'), true);

    if (!is_array($rawBlocks)) {
        handle_editor_week_form($course, $week, null, 'El contenido enviado no es válido. Recargá la página e intentá de nuevo.', null);

        return;
    }

    // Nunca confiamos en la forma que manda el navegador: se vuelve a
    // validar y recortar cada bloque server-side antes de guardar nada.
    $sanitizedBlocks = sanitize_blocks_input($rawBlocks);

    $formValues = ['title' => $title, 'theme' => $theme, 'blocks' => $sanitizedBlocks];

    try {
        $file = github_get_entries_file();
        $parsed = parse_entries_source($file['content']);

        if (find_entry_by_week($parsed['entries'], $week) === null) {
            handle_editor_week_form($course, $week, null, 'Esa semana ya no existe en el archivo.', $formValues);

            return;
        }

        $updatedEntries = apply_entry_edit($parsed['entries'], $week, $title, $theme, $sanitizedBlocks);
        $newSource = $parsed['preamble'] . format_entries_body($updatedEntries);

        $result = github_update_entries_file(
            $newSource,
            $sha,
            'Actualiza la reflexión de la semana ' . $week
        );
    } catch (GitHubApiException|EntriesParseException $e) {
        handle_editor_week_form($course, $week, null, 'No se pudo guardar: ' . $e->getMessage(), $formValues);

        return;
    }

    if ($result['conflict']) {
        handle_editor_week_form(
            $course,
            $week,
            null,
            'La entrada cambió en GitHub desde que abriste este formulario. Recargá la página y volvé a editar para no perder el otro cambio.',
            $formValues
        );

        return;
    }

    if (!$result['ok']) {
        handle_editor_week_form($course, $week, null, 'GitHub rechazó el cambio (código ' . $result['status'] . ').', $formValues);

        return;
    }

    handle_editor_week_form(
        $course,
        $week,
        'Cambios enviados correctamente. Vercel está actualizando el sitio — en menos de un minuto debería verse en /semana/' . $week . '.',
        null,
        null
    );
}

/**
 * Moderación del foro público: elimina un comentario por id. Reutiliza
 * exactamente la autenticación y el CSRF del editor privado — el
 * contenido que borra es público, pero borrarlo es una acción privada
 * (ver CLAUDE.md, sección 15). No hay edición de comentarios, solo
 * eliminación: nadie más que Derek puede tocar el foro, y ni él puede
 * cambiar lo que alguien más escribió, solo quitarlo.
 */
function handle_comment_delete(array $course, int $week): void
{
    require_editor_auth();

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !verify_editor_csrf((string) ($_POST['csrf'] ?? ''))) {
        header('Location: /semana/' . $week, true, 303);

        return;
    }

    $commentId = (string) ($_POST['comment_id'] ?? '');

    if ($commentId !== '') {
        try {
            $file = github_get_entries_file();
            $parsed = parse_entries_source($file['content']);

            if (find_entry_by_week($parsed['entries'], $week) !== null) {
                $updatedEntries = remove_comment_from_entry($parsed['entries'], $week, $commentId);
                $newSource = $parsed['preamble'] . format_entries_body($updatedEntries);
                github_update_entries_file($newSource, $file['sha'], 'Elimina un comentario — semana ' . $week);
            }
        } catch (GitHubApiException|EntriesParseException $e) {
            // No hay un formulario que volver a mostrar con el error: se
            // vuelve simplemente a /semana/N. Si falló, el comentario
            // sigue ahí y se puede reintentar.
        }
    }

    header('Location: /semana/' . $week, true, 303);
}
