<?php

/**
 * Copyright (c) 2026 David E Luna M
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @license MIT
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
 * @license MIT
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
