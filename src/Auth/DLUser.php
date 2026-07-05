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

use DLCore\Database\Model;

/**
 * Procesa el usuario.
 * 
 * @package DLCore\Auth
 * 
 * @author David E Luna M <info@dlunire.dev>
 * @copyright 2023 David E Luna M
 * @license AGPL-3.0 license
 */
abstract class DLUser extends Model {

    /**
     * Token de usuario
     *
     * @var string|null
     */
    private ?string $token_user = null;

    /**
     * Usuario de la aplicación
     *
     * @var string|null
     */
    private ?string $username = null;

    /**
     * Contraseña de la aplicación.
     *
     * @var string|null
     */
    private ?string $password_hash = null;

    /**
     * Devuelve el token del usuario
     *
     * @return string|null
     */
    public function get_token(): ?string {
        return $this->token_user;
    }

    /**
     * Establece el token de autenticación del usuario
     *
     * @param string $token
     * @return void
     */
    public function set_token_user(string $token): void {
        $this->token_user = $token;
    }

    /**
     * Establece el nombre de usuario de la aplicación.
     *
     * @param string $username Nombre de usuario
     * @return void
     */
    protected function set_username(string $username): void {
        $this->username = trim($username);
    }

    /**
     * Establece la contraseña enviada por el usuario
     *
     * @param string $password
     * @return void
     */
    protected function set_password(string $password): void {
        $this->password = trim($password);
    }

    /**
     * Establece el hash de la contraseña almacenada en la base de datos
     *
     * @param string $password_hash
     * @return void
     */
    public function set_password_hash(string $password_hash): void {
        $this->password_hash = $password_hash;
    }

    /**
     * Devuelve el usuario de la aplicación
     *
     * @return string|null
     */
    public function get_username(): ?string {
        return $this->username;
    }

    /**
     * Devuelve el hash de la contraseña
     *
     * @return string|null
     */
    public function get_password_hash(): ?string {
        return $this->password_hash;
    }

    /**
     * Devuelve la contraseña enviada por el usuario
     *
     * @return string|null
     */
    public function get_password(): ?string {
        return $this->password;
    }
}
