<?php

ini_set('display_errors', 1);

/**
 * Tiempo de expiración de la sesión expresado en segundos.
 * 
 * @var int $sessionExpire
 */

use DLCore\Boot\Project;

/** @var int $session_expire Duración de 1 año */
$session_expire = time() + 3600 * 24 * 30 * 12;

/** @var non-empty-string $session_name */
$session_name = "PHPSSID_" . hash('SHA256', $_SERVER['DOCUMENT_ROOT']);

session_name($session_name);
session_set_cookie_params($session_expire);
session_start();

require_once dirname(__DIR__, 1) . DIRECTORY_SEPARATOR . "vendor" . DIRECTORY_SEPARATOR . "autoload.php";

Project::run();