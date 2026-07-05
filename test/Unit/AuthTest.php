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
session_start();

use DLCore\Auth\DLAuth;
use PHPUnit\Framework\TestCase;


class AuthTest extends Testcase {
    private DLAuth $auth;

    public function setup(): void {
        $this->auth = DLAuth::get_instance();
    }

    public function test_auth(): void {
        $string = $this->auth->get_token();
        $this->assertNotEmpty($string, 'La cadena se encuentra vacía');
    }

    public function test_true(): void {
        $this->assertTrue(true);
    }
}
