<?php

declare(strict_types=1);

/**
 * Copyright (c) 2025 David E Luna M
 * Licensed under the MIT License. See LICENSE file for details.
 */

namespace DLCore\Exceptions;

use RuntimeException;

/**
 * InvalidTypeException
 *
 * Se lanza cuando se detecta un error de tipo en tiempo de ejecución,
 * como una variable con tipo no esperado, estructura mal formada,
 * o datos no conformes al contrato tipado.
 *
 * @package DLCore\Exceptions
 * @version v0.0.1
 * @license MIT
 * @author David E Luna M
 * @copyright Copyright (c) 2025 David E Luna M
 *
 * @property-read int $code Código HTTP asociado, por defecto 400
 * @property-read string $message Mensaje de la excepción
 */
final class InvalidTypeException extends RuntimeException {
    /**
     * Constructor de InvalidTypeException.
     *
     * @param string $message Mensaje descriptivo del error de tipo.
     * @param int $code Código HTTP asociado (por defecto 400).
     * @param \Throwable|null $previous Excepción previa, si existe.
     */
    public function __construct(string $message = 'Tipo de dato inválido.', int $code = 400, ?\Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}