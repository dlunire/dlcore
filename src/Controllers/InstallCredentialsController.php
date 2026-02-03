<?php

declare(strict_types=1);

namespace DLCore\Controllers;

use DLCore\Auth\EncryptedCredentials;
use DLCore\Core\BaseController;

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

    /**
     * Punto de entrada de instalación de credenciales de acceso a la base de datos
     *
     * @return array
     */
    public function install(): array {
        $install = new EncryptedCredentials();

        /** @var non-empty-string $entropy */
        $entropy = $install->get_key_entropy('file_path');
        
        return [
            "status" => true,
            "entropy" => bin2hex($entropy)
        ];
    }
}