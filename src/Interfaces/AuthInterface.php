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

namespace DLCore\Interfaces;

use DLCore\Auth\DLAuthOptions;
use DLCore\Auth\DLCookie;
use DLCore\Auth\DLUser;

/**
 * Sistema de autenticación del sistema
 * 
 * @package DLCore\Interface
 * 
 * @author David E Luna M <info@dlunire.dev>
 * @copyright 2023 David E Luna M
 * @license AGPL-3.0 license
 */
interface AuthInterface {

    /**
     * Devuelve un token para evitar ataques por medio CSRF.
     * 
     * @return string
     */
    public function get_token(): string;

    /**
     * Devuelve un hash aleatorio.
     *
     * @return string
     */
    public function get_hash(): string;

    /**
     * Autentica al usuario en caso de que los datos sean correctos.
     *
     * @param DLUser $user Modelo relacionado a la tabla de usuarios del sistema.
     * @param array|DLAuthOptions $options Opcional. Opciones de autenticación.
     * @param DLCookie|null $cookie Opcional. Establece los parámetros de configuración y envío de la cookie.
     * @return bool Retorna `true` si la autenticación fue exitosa, `false` en caso contrario.
     */
    public function auth(DLUser $user, array|DLAuthOptions $options = [], ?DLCookie $cookie): bool;


    /**
     * Permite ejecutar acciones cuadno el usuario está autenticado
     *
     * @return void
     */
    public function logged(callable $callback): void;

    /**
     * Permite ejecutar acciones cuando el usuario no se encuentra autenticado
     *
     * @param callable $callback
     * @return void
     */
    public function not_logged(callable $callback): void;
}
