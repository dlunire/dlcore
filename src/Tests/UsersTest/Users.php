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

namespace DLCore\Tests\UsersTest;

use DLCore\Auth\DLAuth;
use DLCore\Auth\DLUser;

class Users extends DLUser {
    // protected static ?string $table = "ciencia";

    public function capture_credentials(): bool {

        /**
         * Autenticación del usuario
         * 
         * @var DLAuth
         */
        $auth = DLAuth::get_instance();

        $this->set_username(
            $this->get_required('username')
        );

        $this->set_password(
            $this->get_required('password')
        );

        return $auth->auth($this, [
            "username_field" => 'username',
            "password_field" => 'password',
            "token_field" => 'token'
        ]);
    }
}
