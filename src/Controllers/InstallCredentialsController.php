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

declare(strict_types=1);

namespace DLCore\Controllers;

use DLCore\Auth\EncryptedCredentials;
use DLCore\Core\BaseController;

/**
 * Controlador de instalación las credenciales de acceso a la base de datos.
 * 
 * @package DLCore\Controllers
 * 
 * @author David E Luna M <info@dlunire.dev>
 * @copyright (c) 2026 David E Luna M
 * @license AGPL-3.0 license
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