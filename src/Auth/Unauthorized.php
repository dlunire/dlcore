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

namespace DLCore\Auth;

use DLRoute\Server\DLServer;

/**
 * Envía un mensaje de error con códigos de estados.
 * 
 * @package DLCore\Auth
 * 
 * @author David E Luna M <info@dlunire.dev>
 * @copyright 2023 David E Luna M
 * @license AGPL-3.0 license
 */
final class Unauthorized {

    /**
     * Salida que indica que el usuario no se encuentra autorizado para realizar peticiones
     * en las rutas marcadas como autenticadas si el usuario no se encuentra autenticado.
     *
     * @return array
     */
    public function unauthorized(): array {

        return [
            "code" => 401,
            "error" => "No se encuentra autorizado para acceder a esta ruta.",
            "route" => DLServer::get_route(),
            "ip" => DLServer::get_ipaddress()
        ];
    }

    /**
     * Rutas a las que no se les tienen permitido acceder a los usuarios que se encuentran autenticados
     *
     * @return array
     */
    public function forbidden(): array {

        return [
            "code" => 403,
            "error" => "Prohibido el acceso a esta ruta.",
            "route" => DLServer::get_route(),
            "ip" => DLServer::get_ipaddress()
        ];
    }
}
