<?php

declare(strict_types=1);

/**
 * Copyright (c) 2026 David E Luna M
 * Licensed under the MIT License. See LICENSE file for details.
 */

namespace DLCore\Exceptions;

use RuntimeException;

/**
 * AuthorizationException
 *
 * Se lanza cuando una petición intenta acceder a un recurso
 * para el cual no posee los permisos necesarios o cuando
 * el acceso está explícitamente prohibido por las reglas
 * de autorización del sistema.
 *
 * Esta excepción cubre escenarios como:
 * - Acceso no autorizado (401 Unauthorized).
 * - Acceso prohibido aun estando autenticado (403 Forbidden).
 * - Violación de políticas de autorización internas.
 *
 * @package DLCore\Exceptions
 *
 * @version v0.0.1
 *
 * @license MIT
 *
 * @author David E Luna M
 *
 * @copyright Copyright (c) 2025 David E Luna M
 *
 * @property-read int $code Código HTTP asociado (401 o 403)
 * @property-read string $message Mensaje descriptivo del error
 */
final class AuthorizationException extends RuntimeException {
    /**
     * Constructor de AuthorizationException.
     *
     * @param string $message
     *     Mensaje descriptivo del error de autorización.
     *
     * @param int $code
     *     Código HTTP asociado:
     *     - 401: No autorizado (no autenticado).
     *     - 403: Prohibido (autenticado pero sin permisos).
     *
     * @param \Throwable|null $previous
     *     Excepción previa, si existe.
     */
    public function __construct(
        string $message = 'Acceso no autorizado.',
        int $code = 403,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
