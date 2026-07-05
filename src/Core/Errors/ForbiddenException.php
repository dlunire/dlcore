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

namespace DLCore\Core\Errors;

use DLRoute\Requests\DLOutput;
use RuntimeException;
use Throwable;

final class ForbiddenException extends RuntimeException {
    /**
     * Inicializa una excepción de acceso prohibido.
     *
     * @param string $message Mensaje descriptivo del error (opcional).
     * @param int $code Código de estado HTTP asociado (opcional, por defecto 403).
     * @param Throwable|null $previous Excepción encadenada previa (opcional).
     */
    public function __construct(string $message = "Acceso restringido", int $code = 403, ?Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Genera una respuesta HTTP con el código 403 y devuelve el mensaje de error en formato JSON.
     */
    public function render(): void {
        header('Content-Type: application/json; charset=utf-8', true, $this->getCode());
        echo DLOutput::get_json([
            'status' => false,
            'error' => true,
            'message' => $this->getMessage(),
            'code' => $this->getCode()
        ], true);

        exit;
    }
}
