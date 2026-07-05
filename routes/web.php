<?php

declare(strict_types=1);

use DLCore\Core\Output\View;
use DLCore\Tests\Usuarios;
use DLRoute\Requests\DLRoute;

DLRoute::get(uri: '/', controller: fn() => View::get());

DLRoute::get("/test", fn() => ["status" => "ok"]);

DLRoute::get("/test-database", fn () => Usuarios::paginate(1, 3 ));