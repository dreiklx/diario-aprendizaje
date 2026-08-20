<?php

/**
 * Modelo del foro público de comentarios de una entrada. Cada comentario
 * es texto plano simple (nombre + contenido + fecha generada por el
 * servidor) — sin marcado, sin HTML, sin cuentas. Ver CLAUDE.md,
 * "Comentarios".
 *
 * REGLA DE SEGURIDAD CENTRAL DE ESTE ARCHIVO
 * -------------------------------------------
 * El contenido de un comentario nunca es HTML ni admite el marcado
 * mínimo de los bloques (**negrita**, etc.) — es texto llano. Se
 * escapa con e() y solo después se aplica nl2br() para los saltos de
 * línea; esa es la única transformación permitida. Nunca imprimas
 * $comment['name']/['content'] sin pasar por e() primero.
 */

require_once __DIR__ . '/auth.php'; // reutiliza editor_session_secret() para firmar el token anti-bot y la cookie de cooldown — mecanismo aparte del login, misma clave HMAC, sin relación con la sesión del editor.
require_once __DIR__ . '/dates.php'; // format_month_abbr()

const COMMENT_NAME_MAX = 80;
const COMMENT_TEXT_MAX = 2000;
const COMMENT_MAX_COUNT_PER_ENTRY = 300;

// Token de formulario: firma el momento en que se sirvió el formulario.
// Un envío más rápido que COMMENT_FORM_TOKEN_MIN_AGE casi seguro es un
// bot que llenó y mandó el formulario en milisegundos, no una persona.
const COMMENT_FORM_TOKEN_MIN_AGE = 2;
const COMMENT_FORM_TOKEN_MAX_AGE = 6 * 60 * 60;

// Cooldown entre publicaciones, guardado como cookie firmada — sin
// estado en el servidor. No es a prueba de balas (borrar la cookie lo
// evita), pero es fricción razonable sin necesitar almacenamiento.
const COMMENT_COOLDOWN_COOKIE = 'last_comment_at';
const COMMENT_COOLDOWN_SECONDS = 20;

/** @return array<int, array{id:string, name:string, content:string, created_at:string}> */
function entry_comments(array $entry): array
{
    return array_values((array) ($entry['comments'] ?? []));
}

function comment_count(array $entry): int
{
    return count(entry_comments($entry));
}

function new_comment_id(): string
{
    return bin2hex(random_bytes(8));
}

/** Hora de Costa Rica (UTC-6 todo el año, sin horario de verano). */
function now_costa_rica(): DateTimeImmutable
{
    return new DateTimeImmutable('now', new DateTimeZone('America/Costa_Rica'));
}

/** Marca de tiempo ISO 8601 con offset, generada siempre en el servidor. */
function comment_timestamp_now(): string
{
    return now_costa_rica()->format(DATE_ATOM);
}

/** "19 AGO 2026 · 5:42 PM" — mismo tratamiento editorial de fecha que el resto del sitio (ver dates.php). */
function format_comment_timestamp(string $isoDateTime): string
{
    try {
        $dt = new DateTimeImmutable($isoDateTime);
    } catch (Exception $e) {
        return '';
    }

    $dt = $dt->setTimezone(new DateTimeZone('America/Costa_Rica'));

    return sprintf(
        '%d %s %s · %s',
        (int) $dt->format('j'),
        format_month_abbr((int) $dt->format('n')),
        $dt->format('Y'),
        $dt->format('g:i A')
    );
}

/** Recorta espacios/saltos de línea a un solo espacio — un nombre es una sola línea. */
function sanitize_comment_name(string $raw): string
{
    $value = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $raw);

    return trim((string) preg_replace('/\s+/', ' ', $value));
}

/** Preserva saltos de línea (es lo único que distingue párrafos en un comentario). */
function sanitize_comment_content(string $raw): string
{
    return trim(str_replace(["\r\n", "\r"], "\n", $raw));
}

/** Texto plano únicamente: escapa primero, nl2br() después. Nunca HTML/Markdown del comentario. */
function render_comment_content_html(string $content): string
{
    return nl2br(e($content));
}

// — Anti-spam: token de formulario firmado (sin almacenamiento) —

function issue_comment_form_token(): string
{
    $issuedAt = (string) time();

    return $issuedAt . '.' . hash_hmac('sha256', 'comment-form:' . $issuedAt, editor_session_secret());
}

function verify_comment_form_token(string $token): bool
{
    if (!str_contains($token, '.')) {
        return false;
    }

    [$issuedAt, $signature] = explode('.', $token, 2);

    if (!ctype_digit($issuedAt)) {
        return false;
    }

    if (!hash_equals(hash_hmac('sha256', 'comment-form:' . $issuedAt, editor_session_secret()), $signature)) {
        return false;
    }

    $age = time() - (int) $issuedAt;

    return $age >= COMMENT_FORM_TOKEN_MIN_AGE && $age <= COMMENT_FORM_TOKEN_MAX_AGE;
}

// — Anti-spam: cooldown entre publicaciones —

function comment_cooldown_seconds_remaining(): int
{
    $raw = (string) ($_COOKIE[COMMENT_COOLDOWN_COOKIE] ?? '');

    if (!str_contains($raw, '.')) {
        return 0;
    }

    [$ts, $signature] = explode('.', $raw, 2);

    if (!ctype_digit($ts) || !hash_equals(hash_hmac('sha256', 'cooldown:' . $ts, editor_session_secret()), $signature)) {
        return 0;
    }

    return max(0, COMMENT_COOLDOWN_SECONDS - (time() - (int) $ts));
}

function set_comment_cooldown_cookie(): void
{
    $ts = (string) time();

    setcookie(COMMENT_COOLDOWN_COOKIE, $ts . '.' . hash_hmac('sha256', 'cooldown:' . $ts, editor_session_secret()), [
        'expires' => time() + COMMENT_COOLDOWN_SECONDS,
        'path' => '/',
        'secure' => is_https_request(),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
}
