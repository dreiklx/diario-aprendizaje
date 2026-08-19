<?php

/**
 * Autenticación del editor privado (/editar). No hay sesiones de PHP:
 * una función serverless de Vercel no garantiza el mismo disco/proceso
 * entre peticiones, así que session.save_path (basado en archivos) no
 * es fiable aquí. En su lugar, la sesión es una cookie firmada
 * (HMAC-SHA256) que el propio navegador conserva; el servidor la
 * verifica en cada petición sin guardar nada él mismo.
 *
 * La contraseña NUNCA se guarda en texto plano: solo su hash bcrypt
 * (EDITOR_PASSWORD_HASH) vive como variable de entorno en Vercel.
 */

const EDITOR_COOKIE = 'editor_session';
const EDITOR_SESSION_TTL = 4 * 60 * 60; // 4 horas

function is_https_request(): bool
{
    if (($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    return ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

function base64_url_encode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function base64_url_decode(string $value): string
{
    return (string) base64_decode(strtr($value, '-_', '+/'), true);
}

function editor_session_secret(): string
{
    return (string) getenv('SESSION_SECRET');
}

/** Compara una contraseña contra el hash bcrypt guardado en el entorno. */
function verify_editor_password(string $password): bool
{
    $hash = (string) getenv('EDITOR_PASSWORD_HASH');

    if ($hash === '') {
        return false;
    }

    return password_verify($password, $hash);
}

function sign_payload(string $payload): string
{
    return hash_hmac('sha256', $payload, editor_session_secret());
}

/** Crea el valor de cookie de una sesión válida por EDITOR_SESSION_TTL. */
function issue_editor_session_token(): string
{
    $payload = base64_url_encode((string) json_encode(['exp' => time() + EDITOR_SESSION_TTL]));

    return $payload . '.' . sign_payload($payload);
}

function set_editor_cookie(string $token): void
{
    setcookie(EDITOR_COOKIE, $token, [
        'expires' => time() + EDITOR_SESSION_TTL,
        // Antes restringida a /editar: alcanzaba porque nada fuera de esa
        // ruta miraba la sesión. El link discreto de "Editar comentario"
        // en /semana/N necesita saber si hay sesión activa en una ruta
        // pública, así que la cookie tiene que llegar a todo el sitio.
        'path' => '/',
        'secure' => is_https_request(),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}

function clear_editor_cookie(): void
{
    setcookie(EDITOR_COOKIE, '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => is_https_request(),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}

/** true solo si la cookie existe, su firma es válida y no expiró. */
function is_editor_authenticated(): bool
{
    $token = $_COOKIE[EDITOR_COOKIE] ?? '';

    if ($token === '' || !str_contains($token, '.')) {
        return false;
    }

    [$payload, $signature] = explode('.', $token, 2);

    if (!hash_equals(sign_payload($payload), $signature)) {
        return false;
    }

    $data = json_decode(base64_url_decode($payload), true);

    return is_array($data) && isset($data['exp']) && (int) $data['exp'] > time();
}

function require_editor_auth(): void
{
    if (!is_editor_authenticated()) {
        $next = (string) ($_SERVER['REQUEST_URI'] ?? '/editar');
        header('Location: /editar?next=' . rawurlencode($next));
        exit;
    }
}

/**
 * Solo acepta rutas propias del editor como destino de "volver después
 * de iniciar sesión" — nunca una URL externa (protección básica contra
 * open redirect vía el parámetro next).
 */
function sanitize_editor_next_path(?string $next): string
{
    if ($next === null || $next === '') {
        return '/editar';
    }

    return preg_match('#^/editar(/semana/\d+)?$#', $next) ? $next : '/editar';
}

/**
 * Token CSRF derivado de la propia cookie de sesión (sin almacenamiento
 * aparte): solo alguien que ya posea la cookie válida puede reproducirlo.
 * Se usa en el formulario de guardado, la única acción destructiva.
 */
function editor_csrf_token(): string
{
    $token = $_COOKIE[EDITOR_COOKIE] ?? '';

    return substr(hash_hmac('sha256', 'csrf:' . $token, editor_session_secret()), 0, 32);
}

function verify_editor_csrf(string $submitted): bool
{
    return $submitted !== '' && hash_equals(editor_csrf_token(), $submitted);
}
