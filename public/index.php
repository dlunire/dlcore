<?php

/**
 * DLUnire
 * Copyright (C) 2026 David E Luna M
 *
 * Operando bajo el establecimiento de comercio "DLUnire",
 * NIT 700551569-1, matrícula mercantil Nº 10007069
 * (matrícula mercantil personal Nº 10007068).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this program. If not, see
 * <https://www.gnu.org/licenses/>.
 */

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