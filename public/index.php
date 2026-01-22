<?php

ini_set('display_errors', 1);

/**
 * Tiempo de expiración de la sesión expresado en segundos.
 * 
 * @var int $sessionExpire
 */

use DLCore\Boot\Project;
use DLRoute\Requests\DLRoute;
use DLCore\Core\Output\View;
use DLCore\Parsers\Slug\Path;
use DLRoute\Server\DLServer;

$sessionExpirte = time() + 1300;

session_set_cookie_params($sessionExpirte);
session_start();

include dirname(__DIR__, 1) . "/vendor/autoload.php";

Project::run();

DLRoute::get(uri: '/', controller: function () {
    return View::get();
});

DLRoute::get('/ciencia', function () {
    return $_SERVER;
});

DLRoute::get(uri: '/ports', controller: fn() => [
    "port" => DLServer::get_port(),
    "local_port" => DLServer::get_local_port(),
    "url_base" => DLServer::get_base_url()
], mime_type: 'application/json');

DLRoute::get('/david', function () {
    return [];
});

DLRoute::get('/dir', function () {

    return [
        "one-resolve" => Path::resolve("/mi/ruta"),
        "resolve" => Path::resolve('/sandbox/../../etc/passwd algo por aquí.md'),
        "resolve_filename" => Path::resolve_filename('/sandbox/../../etc/passwd algo por aquí.md'),
        "get_filename" => Path::get_filename("David Eduardo", true),
        "routes" => DLRoute::get_routes()
    ];
});

DLRoute::execute();
