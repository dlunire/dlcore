<?php

ini_set('display_errors', 1);

/**
 * Tiempo de expiración de la sesión expresado en segundos.
 * 
 * @var int $sessionExpire
 */

use DLCore\Boot\Project;

$sessionExpirte = time() + 1300;

session_set_cookie_params($sessionExpirte);
session_start();

require_once dirname(__DIR__, 1) . "/vendor/autoload.php";

Project::run();