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

/**
 * Excepción personalizada para argumentos inválidos.
 *
 * Esta excepción es lanzada cuando se proporciona un argumento no válido
 * en una función o método. Extiende de `RuntimeException` y permite generar
 * una respuesta HTTP en formato JSON.
 */
final class InvalidArgumentException extends RuntimeException {
    /**
     * Inicializa una excepción de argumento inválido.
     *
     * @param string $message Mensaje descriptivo del error (opcional, por defecto "Argumento inválido").
     * @param int $code Código de estado HTTP asociado (opcional, por defecto 400 Bad Request).
     * @param Throwable|null $previous Excepción encadenada previa (opcional).
     */
    public function __construct(string $message = "Argumento inválido", int $code = 400, ?Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Genera una respuesta HTTP con el código de estado correspondiente y devuelve el mensaje de error en formato JSON.
     *
     * Esta función envía una cabecera `Content-Type: application/json`, establece el código de estado HTTP
     * de la respuesta y devuelve un mensaje estructurado en JSON con detalles del error.
     *
     * @return void
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
