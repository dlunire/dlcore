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
 * InvalidDate
 *
 * Se lanza cuando una fecha u hora no es válida, no puede ser interpretada
 * correctamente o viola las reglas esperadas del sistema temporal.
 *
 * Ejemplos de uso:
 * - Formato de fecha inválido
 * - Fecha imposible (2026-02-30)
 * - Timestamp fuera de rango
 * - Fallo al normalizar una fecha/hora
 * - Zona horaria inválida o no soportada
 *
 * Uso típico: parsing, normalización y validación de fechas/horas.
 *
 * @package DLCore\Exceptions
 * @version v0.0.1
 * @license MIT
 * @author David E Luna M
 * @copyright Copyright (c) 2026 David E Luna M
 */
final class InvalidDate extends RuntimeException {
    /**
     * @param string          $message  Mensaje descriptivo (opcional)
     * @param int             $code     Código HTTP (400 por defecto)
     * @param \Throwable|null $previous Excepción previa (encadenamiento)
     */
    public function __construct(
        string $message = 'La fecha u hora proporcionada no es válida o no pudo ser interpretada correctamente.',
        int $code = 400,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
