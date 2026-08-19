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

    if ($password !== '' && verify_editor_password($password)) {
        set_editor_cookie(issue_editor_session_token());
        header('Location: /editar', true, 303);

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
    $submitted = [
        'title' => (string) ($_POST['title'] ?? ''),
        'theme' => (string) ($_POST['theme'] ?? ''),
        'reflexion' => (string) ($_POST['reflexion'] ?? ''),
        'aprendizaje' => (string) ($_POST['aprendizaje'] ?? ''),
        'cuestionamiento' => (string) ($_POST['cuestionamiento'] ?? ''),
        'aplicacion' => (string) ($_POST['aplicacion'] ?? ''),
    ];

    foreach ($submitted as $field => $value) {
        if (mb_strlen($value) > 4000) {
            handle_editor_week_form($course, $week, null, 'El campo "' . $field . '" es demasiado largo.', $submitted);

            return;
        }
    }

    try {
        $file = github_get_entries_file();
        $parsed = parse_entries_source($file['content']);

        if (find_entry_by_week($parsed['entries'], $week) === null) {
            handle_editor_week_form($course, $week, null, 'Esa semana ya no existe en el archivo.', $submitted);

            return;
        }

        $updatedEntries = apply_entry_edit($parsed['entries'], $week, $submitted);
        $newSource = $parsed['preamble'] . format_entries_body($updatedEntries);

        $result = github_update_entries_file(
            $newSource,
            $sha,
            'Actualiza la reflexión de la semana ' . $week
        );
    } catch (GitHubApiException|EntriesParseException $e) {
        handle_editor_week_form($course, $week, null, 'No se pudo guardar: ' . $e->getMessage(), $submitted);

        return;
    }

    if ($result['conflict']) {
        handle_editor_week_form(
            $course,
            $week,
            null,
            'La entrada cambió en GitHub desde que abriste este formulario. Recargá la página y volvé a editar para no perder el otro cambio.',
            $submitted
        );

        return;
    }

    if (!$result['ok']) {
        handle_editor_week_form($course, $week, null, 'GitHub rechazó el cambio (código ' . $result['status'] . ').', $submitted);

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
