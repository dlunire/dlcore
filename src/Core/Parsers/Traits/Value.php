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

namespace DLCore\Core\Parsers\Traits;

/**
 * Permite manipular valores a partir de un array u objetos (objetos en el futuro) para consultarlos 
 * más tardes, pero su objetivo principal es normalizarlos.
 * 
 * No solamente permite extraer valores, sino evaluar rango de valores permitidos.
 * 
 * @package DLCore\Core\Parsers\Traits
 * 
 * @author Davi E Luna M <info@dlunire.dev>
 * @copyright (c) 2026 David Luna M
 * @license AGPL-3.0 license
 */
trait Value {

    /**
     * Array a ser consultado
     *
     * @var array|null $data
     */
    protected array $data;

    protected function set_array(?array $data = null): void {
        $this->data = $data;
    }

    /**
     * Devuelve el valor a partir del índice o clave de un array.
     *
     * @param mixed $key Clave o índice del array. Si la clave no existe, devolverá `null`.
     * @return mixed
     */
    private function get_value(mixed $key): mixed {
        return $this->data[$key] ?? null;
    }

    private function is_numeric(mixed $input): bool {
        return \is_numeric($input);
    }

    /**
     * Valida el array como una dirección IP.
     *
     * @return boolean
     */
    protected function is_ipaddress_v4(): bool {

        for ($index = 0; $index < 4; ++$index) {
            /** @var mixed $value */
            $value = $this->get_value($index);

            if (!$this->is_numeric($value)) return false;

            /** @var int $int_number */
            $int_number = \intval($value);

            if ($int_number < 0 || $int_number > 255) return false;
        }

        return true;
    }

    /**
     * Limpia los valores previamente existentes
     *
     * @return void
     */
    protected function clean(): void {
        $this->set_array();
    }
}
