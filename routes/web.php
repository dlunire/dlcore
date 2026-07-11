<?php

declare(strict_types=1);

use DLCore\Compilers\DLMarkdown;
use DLCore\Controllers\FileController;
use DLCore\Core\Output\View;
use DLCore\Tests\Usuarios;
use DLRoute\Requests\DLRoute;

DLRoute::get(uri: '/', controller: fn() => View::get('welcome'));

DLRoute::get('/docs/licencia-comercial', function (): string {
    $file = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'LICENCIA-COMERCIAL.md';

    if (!is_readable($file)) {
        http_response_code(404);

        return View::get('docs-licencia', [
            'content' => '<p>Documento no encontrado.</p>',
        ]);
    }

    $content = DLMarkdown::stringMarkdown((string) file_get_contents($file));

    return View::get('docs-licencia', [
        'content' => (string) $content,
    ]);
});

DLRoute::get('/politica-datos', function (): string {
    $file = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'docs' . DIRECTORY_SEPARATOR . 'POLITICA-DATOS.md';

    if (!is_readable($file)) {
        http_response_code(404);

        return View::get('docs-politica-datos', [
            'content' => '<p>Documento no encontrado.</p>',
        ]);
    }

    $content = DLMarkdown::stringMarkdown((string) file_get_contents($file));

    return View::get('docs-politica-datos', [
        'content' => (string) $content,
    ]);
});

DLRoute::get("/test", fn() => ["status" => "ok"]);

DLRoute::get("/test-database", fn() => Usuarios::paginate(1, 3));

DLRoute::post('/file', [FileController::class, 'upload']);

