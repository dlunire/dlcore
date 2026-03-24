<?php

declare(strict_types=1);

use DLCore\Core\Output\View;
use DLRoute\Requests\DLRoute;

DLRoute::get(uri: '/', controller: fn() => View::get());