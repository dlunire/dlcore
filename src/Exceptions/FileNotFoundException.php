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
 * FileNotFoundException
 *
 * Se lanza cuando se intenta acceder a un archivo que no existe en el sistema
 * de archivos o cuya ruta, aun siendo válida y normalizada, no apunta a un
 * recurso existente.
 *
 * Esta excepción **nunca debe ser ignorada**: su propósito es eliminar
 * completamente los errores silenciosos relacionados con archivos ausentes.
 *
 * Casos típicos:
 * - Inclusión de archivos requeridos que no existen
 * - Acceso a recursos esperados tras una normalización correcta
 * - Lectura/escritura de archivos inexistentes
 *
 * @package DLCore\Exceptions
 * @version v0.0.1
 * @license AGPL-3.0 license
 * @author David E Luna M
 * @copyright Copyright (c) 2026 David E Luna M
 */
final class FileNotFoundException extends RuntimeException {
    /**
     * @param string          $message  Mensaje descriptivo (opcional)
     * @param int             $code     Código HTTP (404 por defecto)
     * @param \Throwable|null $previous Excepción previa (encadenamiento)
     */
    public function __construct(
        string $message = 'El archivo solicitado no existe o no puede ser localizado.',
        int $code = 404,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
