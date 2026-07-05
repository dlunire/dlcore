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

namespace DLCore\Config;

use DLCore\Core\Parsers\Slug\Path;
use DLRoute\Requests\DLOutput;

/**
 * Crea los `logs` del sistema.
 * 
 * @package DLCore\Config
 * 
 * @author David E Luna M <info@dlunire.dev>
 * @copyright 2023 David E Luna M
 * @license AGPL-3.0 license
 */
final class Logs {

    /**
     * Almacena los logs del sistema
     *
     * @param string $filename Archivo a ser creado en los logs del sistema.
     * @param mixed $data Datos a ser almacenados.
     * @return void
     */
    public static function save(string $filename, mixed $data): void {
        /** @var non-empty-string $file */
        $file = "/logs/{$filename}";

        Path::ensure_container_dir($file);

        /**
         * Log de destino
         * 
         * @var string
         */
        $filename = Path::resolve($file);

        if (\is_object($data) || \is_array($data)) {
            $data = DLOutput::get_json($data, true);
        }

        file_put_contents($filename, $data);
    }
}
