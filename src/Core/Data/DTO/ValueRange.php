<?php

declare(strict_types=1);

namespace DLCore\Core\Data\DTO;

/**
 * Representa un rango de valores utilizado en consultas SQL con BETWEEN.
 *
 * Esta clase es una inyección de dependencia para el método `between`
 * del constructor de consultas en DLCore.
 *
 * @author David E Luna M.
 * @license MIT
 * @version 1.0.0
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
