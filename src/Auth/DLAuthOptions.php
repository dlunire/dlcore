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

use Exception;

/**
 * Define las opciones de autenticación, permitiendo configurar los nombres de campos clave de la tabla de usuarios.
 *
 * @package DLCore\Auth
 * 
 * @license AGPL-3.0 license
 * 
 * @method void set_username_field(string $field) Define el nombre del campo que representa el usuario.
 * @method void set_password_field(string $field) Define el nombre del campo que representa la contraseña.
 * @method void set_token_field(string $field) Define el nombre del campo donde se almacena el token de autenticación.
 * @method string get_username_field() Obtiene el nombre del campo que representa el usuario.
 * @method string get_password_field() Obtiene el nombre del campo que representa la contraseña.
 * @method string get_token_field() Obtiene el nombre del campo que representa el token de autenticación.
 * 
 * @property string $username_field Nombre del campo en la tabla de usuarios que representa el usuario.
 * @property string $password_field Nombre del campo en la tabla de usuarios que representa la contraseña.
 * @property string $token_field Nombre del campo en la tabla de usuarios donde se almacena el token de autenticación.
 */
final class DLAuthOptions {

    /**
     * Nombre del campo de usuario de la tabla de usuarios
     *
     * @var string $username_field
     */
    private string $username_field = 'username';

    /**
     * Nombre del campo de contraseña de la tabla usuario
     *
     * @var string $password_field
     */
    private string $password_field = 'password';

    /**
     * Nombre de columna o campo de la tabla usuarios donde se almacenará el token
     *
     * @var string
     */
    private string $token_field = 'token';

    public function __construct() {
    }

    /**
     * Estable el nombre del campo de usuario de la tabla de usuarios
     *
     * @param string $field Nombre de campo a definir
     * @return void
     * 
     * @throws Exception
     */
    public function set_username_field(string $field): void {
        $field = trim($field);

        if (empty($field)) {
            throw new Exception('El nombre del campo de usuario no puede estar vacío', 500);
        }

        $this->username_field = $field;
    }

    /**
     * Establece el nombre del campo contraseña de la tabla usuarios
     *
     * @param string $field Nombre del campo de contraseña de la tabla de usuarios.
     * @return void
     * 
     * @throws Exception
     */
    public function set_password_field(string $field): void {
        $field = trim($field);

        if (empty($field)) {
            throw new Exception('El nombre del campo de contraseña es requerido. No puede estar vacío', 500);
        }

        $this->password_field = $field;
    }

    /**
     * Establece el nombre del campo donde se almacenará el token de referencia de autenticación de la tabla de usuarios.
     *
     * @param string $field Nombre del campo de tokens de la tabla de usuarios.
     * @return void
     * 
     * @throws Exception
     */
    public function set_token_field(string $field): void {
        $field = trim($field);

        if (empty($field)) {
            throw new Exception("El nombre del campo de tokens es requerido. No puede estar vacío", 500);
        }

        $this->token_field = $field;
    }

    /**
     * Devuelve el nombre del campo de usuario de la tabla de usuarios.
     *
     * @return string
     */
    public function get_username_field(): string {
        return $this->username_field;
    }

    /**
     * Devuelve el nombre del campo de contraseña de la tabla de usuarios.
     *
     * @return string
     */
    public function get_password_field(): string {
        return $this->password_field;
    }

    /**
     * Devuelve el nombre del campo donde se almacenará el token de referencia de la tabla de usuarios.
     *
     * @return string
     */
    public function get_token_field(): string {
        return $this->token_field;
    }
}
