<?php

declare(strict_types=1);

/**
 * Copyright (c) 2025 David E Luna M
 * Licensed under the MIT License. See LICENSE file for details.
 */

namespace DLCore\Exceptions;

use RuntimeException;

/**
 * UrlException
 *
 * Se lanza cuando se detecta un error relacionado con URLs,
 * como URLs inválidas, vacías, mal formadas o con esquemas
 * no permitidos según el contexto de ejecución.
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
final class URLException extends RuntimeException {
    /**
     * Constructor de UrlException.
     *
     * @param string $message Mensaje descriptivo del error de URL.
     * @param int $code Código HTTP asociado (por defecto 400).
     * @param \Throwable|null $previous Excepción previa, si existe.
     */
    public function __construct(string $message = 'URL inválida.', int $code = 400, ?\Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
