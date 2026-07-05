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
 * InvalidPathFormatException
 *
 * Se lanza cuando una ruta (path) tiene un formato inválido o no puede ser normalizada
 * correctamente, por ejemplo:
 * - Separadores mixtos no manejables
 * - Múltiples separadores consecutivos no válidos
 * - Caracteres inesperados después de la normalización
 * - Ruta vacía o con formato que no representa una ruta válida
 *
 * Uso típico: durante la normalización de rutas de archivos/subidas/descargas.
 *
 * @package DLCore\Exceptions
 * @license AGPL-3.0 license
 * @author David E Luna M
 * @copyright Copyright (c) 2025 David E Luna M
 */
final class InvalidPath extends RuntimeException {
    /**
     * @param string          $message  Mensaje descriptivo (opcional)
     * @param int             $code     Código HTTP (400 por defecto)
     * @param \Throwable|null $previous Excepción previa (encadenamiento)
     */
    public function __construct(
        string $message = 'La ruta tiene un formato inválido o no se pudo normalizar correctamente.',
        int $code = 400,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}