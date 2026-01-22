<?php

declare(strict_types=1);

use DLRoute\Requests\DLRoute;

DLRoute::get('/routes', function() {
    return [
        "status" => "Ruta de prueba"
    ];
});