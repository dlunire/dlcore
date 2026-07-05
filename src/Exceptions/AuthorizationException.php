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
 * @license AGPL-3.0 license
 * @author David E Luna M
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
