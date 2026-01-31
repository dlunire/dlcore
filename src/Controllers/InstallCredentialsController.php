<?php

declare(strict_types=1);

namespace DLCore\Controllers;

use DLCore\Auth\EncriptedCredentials;
use DLCore\Core\BaseController;
use Exception;

/**
 * Controlador de instalación las credenciales de acceso a la base de datos.
 * 
 * @package DLCore\Controllers
 * 
 * @version v0.0.1 (release)
 * @author David E Luna M <dlunireframework@gmail.com>
 * @copyright (c) 2026 David E Luna M
 * @license MIT
 */
final class InstallCredentialsController extends BaseController {

    public function install() {
        $install = new EncriptedCredentials();

        return [
            "status" => true,
            "entropy" => $install->get_key_entropy('file_path')
        ];
    }
}