<?php

declare(strict_types=1);

use DLCore\Core\Output\View;
use DLCore\Core\Time\DLTime;
use DLCore\Database\Model;
use DLCore\Parsers\Slug\Path;
use DLRoute\Requests\DLRoute;
use DLRoute\Server\DLServer;

DLRoute::get(uri: '/', controller: function () {
    return View::get();
});

DLRoute::get('/routes', function () {
    return [
        "status" => "Una prueba para Leroy",
        "local_port" => DLServer::get_local_port(),
        "port" => DLServer::get_port(),
        "route" => DLServer::get_route(),
        "dir" => DLServer::get_dir(),
        "uri" => DLServer::get_uri(),
        "host" => DLServer::get_host(),
        "http_host" => DLServer::get_http_host(),
        "url_base" => DLServer::get_base_url(),
        "ip" => DLServer::get_ipaddress(),
        "resolve" => Path::resolve("/.home/\\\\\\////david///"),
        "home" => Path::get_home_dir()
    ];
});

# Lo que existía antes.


DLRoute::get('/ciencia', function () {
    return $_SERVER;
});

DLRoute::get(uri: '/ports', controller: fn() => [
    "port" => DLServer::get_port(),
    "local_port" => DLServer::get_local_port(),
    "url_base" => DLServer::get_base_url()
], mime_type: 'application/json');

DLRoute::get('/david', function () {
    return [0];
});

DLRoute::get('/dir', function () {

    try {
        $data = [
            "one-resolve" => Path::resolve("/mi/ruta"),
            "resolve" => Path::resolve('/sandbox/../../etc/passwd algo por aquí.md'),
            "resolve_filename" => Path::resolve_filename('/sandbox/../../etc/passwd algo por aquí.md'),
            "get_filename" => Path::get_filename("David Eduardo", true),
            "routes" => DLRoute::get_routes(),
            "home" => Path::build_home_path("/ciencia"),
            "test" => dirname("/ciencia"),
            "date" => DLTime::now_for_filename()
        ];
    } catch (Exception $error) {
        return [
            "message" => $error->getMessage(),
            "code" => $error->getCode(),
            "details" => $error->getTrace()
        ];
    }

    return $data;
});

final class Filenames extends Model {
}

DLRoute::get('/test', function () {
    return Filenames::get();
});