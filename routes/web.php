<?php

declare(strict_types=1);

use DLCore\Compilers\DLMarkdown;
use DLCore\Controllers\FileController;
use DLCore\Core\Output\View;
use DLCore\Tests\Usuarios;
use DLRoute\Core\Routing\Router;
use DLRoute\Requests\DLRoute;

/** @var callable(string=): string $route */
$route = Router::to(...);

/**
 * Nonce CSP por petición (hex, apto para atributo HTML y cabecera).
 */
function dlcore_csp_nonce(): string
{
    return bin2hex(random_bytes(32));
}

/**
 * Política CSP de la página de bienvenida.
 *
 * `script-src` / `style-src` solo con nonce (sin `'self'`): evita el aviso del
 * evaluador CSP sobre JSONP/Angular/archivos subidos en el mismo origen; los
 * recursos propios llevan `nonce="…"` en la plantilla.
 */
function dlcore_welcome_csp(string $nonce): string
{
    return implode('; ', [
        "default-src 'self'",
        "script-src 'nonce-{$nonce}'",
        "style-src 'nonce-{$nonce}' https://fonts.googleapis.com",
        "font-src 'self' https://fonts.gstatic.com",
        "img-src 'self' data:",
        "connect-src 'self'",
        "object-src 'none'",
        "base-uri 'self'",
        "form-action 'self'",
        "frame-ancestors 'none'",
        'upgrade-insecure-requests',
    ]);
}

/**
 * Envía Content-Security-Policy (cabecera HTTP; `frame-ancestors` no aplica en meta).
 */
function dlcore_send_welcome_csp(string $csp): void
{
    header("Content-Security-Policy: {$csp}");
}

DLRoute::get(uri: '/', controller: function () use ($route): string {
    $token = dlcore_csp_nonce();
    $csp = dlcore_welcome_csp($token);
    dlcore_send_welcome_csp($csp);

    return View::get('welcome', [
        'route' => $route,
        'token' => $token,
        'csp' => $csp,
    ]);
});

DLRoute::get('/docs/licencia-comercial', function () use ($route): string {
    $file = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'LICENCIA-COMERCIAL.md';

    if (!is_readable($file)) {
        http_response_code(404);

        return View::get('docs-licencia', [
            'route' => $route,
            'content' => '<p>Documento no encontrado.</p>',
        ]);
    }

    $content = DLMarkdown::stringMarkdown((string) file_get_contents($file));

    return View::get('docs-licencia', [
        'route' => $route,
        'content' => (string) $content,
    ]);
});

DLRoute::get('/politica-datos', function () use ($route): string {
    $file = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'POLITICA-DATOS.md';

    if (!is_readable($file)) {
        http_response_code(404);

        return View::get('docs-politica-datos', [
            'route' => $route,
            'content' => '<p>Documento no encontrado.</p>',
        ]);
    }

    $content = DLMarkdown::stringMarkdown((string) file_get_contents($file));

    return View::get('docs-politica-datos', [
        'route' => $route,
        'content' => (string) $content,
    ]);
});

DLRoute::get("/test", fn() => ["status" => "ok"]);

DLRoute::get("/test-database", fn() => Usuarios::paginate(1, 2));

DLRoute::post('/file', [FileController::class, 'upload']);
