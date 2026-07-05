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

use DLCore\Config\Credentials;
use DLCore\Config\DLEnvironment;
use PHPUnit\Framework\TestCase;

class DLVarsTest extends TestCase {

    use DLEnvironment;

    private ?Credentials $credentials = null;

    public function setup(): void {
        $this->credentials = $this->get_credentials();
    }

    public function test_production(): void {
        $value = $this->credentials->is_production();
        $this->assertIsBool($value);
    }

    public function test_database_host(): void {
        $value = $this->credentials->get_host();
        $this->assertIsString($value);
    }

    public function test_database_port(): void {
        $value = $this->credentials->get_port();
        $this->assertIsInt($value);
    }

    public function test_mail_port(): void {
        $value = $this->credentials->get_mail_port();
        $this->assertIsInt($value);
    }
}
