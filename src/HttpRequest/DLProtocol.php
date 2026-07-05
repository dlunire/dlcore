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

namespace DLCore\HttpRequest;

/**
 * @package DLCore\HttpRequest
 * @author David E Luna <info@dlunire.dev>
 * @copyright (c) 2020 - David E Luna M
 * @license AGPL-3.0 license
 */

class DLProtocol extends DLHost {
    /**
     * @var array $hostnames - List of hostnames
     */
    private array $hostnames;

    /**
     * @param array $hostnames
     */
    public function __construct(array $hostnames = []) {

        if (count($hostnames) > 0) {
            foreach ($hostnames as $host) {
                $this->hostnames[] = $host;
            }
        }
    }

    /**
     * Forza a utilizar el protocolo HTTPS
     * @return void
     */
    public function https(): void {
        $isHTTPS = $this->isHTTPS();
        $hostname = $this->getHostname();

        foreach ($this->hostnames as $host) {
            if (!$isHTTPS && $hostname === $host) {
                header("Location: https://{$host}");
                exit;
            }
        }
    }
}
