<?php

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
 * @version v0.0.1 (release)
 * @author Davi E Luna M <dlunireframework@gmail.com>
 * @copyright (c) 2026 David Luna M
 * @license MIT
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
