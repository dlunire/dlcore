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
 * URLException
 *
 * Se lanza cuando se detecta un error relacionado con URLs,
 * como URLs inválidas, vacías, mal formadas o con esquemas
 * no permitidos según el contexto de ejecución.
 *
 * @package DLCore\Exceptions
 * @license AGPL-3.0 license
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
