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

namespace DLCore\Core\Data\DTO;

/**
 * Representa un rango de valores utilizado en consultas SQL con BETWEEN.
 *
 * Esta clase es una inyección de dependencia para el método `between`
 * del constructor de consultas en DLCore.
 *
 * @author David E Luna M.
 * @since 25 de febrero de 2025
 * @link https://github.com/dlunire/dlcore/blob/main/src/Core/Data/Values/ValueRange.php
 */
final class ValueRange {

    /**
     * Rango de valores para cláusula BETWEEN en consultas SQL.
     *
     * @param string $from Valor inicial (inclusive)
     * @param string $to   Valor final (inclusive)
     */
    public function __construct(
        public readonly string $from,
        public readonly string $to,
    ) {
        $this->from = trim($from);
        $this->to = trim($to);
    }
}
