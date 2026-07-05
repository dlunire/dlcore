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

namespace DLCore\Database;

final class ParseSQL {

    /**
     * Consulta SQL completa
     *
     * @var string
     */
    private string $query;

    public function __construct(string $query) {
        $this->query = trim($query);
    }

    public function extract_where(): string {

        /** @var string $pattern */
        $pattern = "/where(.*)/i";

        /** @var bool $found */
        $found = \boolval(preg_match($pattern, $this->query, $matches));

        /** @var string $query */
        $query = "";

        if ($found) {
            $query = $matches[0];
        }

        $query = preg_replace("/WHERE\s+/", "", $query);

        return $query;
    }

    /**
     * Extrae parte de la consulta por grupos
     *
     * @return array
     */
    public function extract_group(): array {
        /** @var string $query */
        $query = $this->extract_where();

        /** @var string[] $parts */
        $parts = explode(" OR ", $query);

        foreach ($parts as &$part) {
            if (!\is_string($part)) {
                continue;
            }

            $part = trim($part);
        }

        return $parts;
    }

    public function get_query(): string {
        return $this->extract_where();
    }
}
