<?php

namespace DLCore\Config;

use DLCore\Parsers\Slug\Path;
use DLRoute\Requests\DLOutput;
use DLRoute\Routes\RouteDebugger;
use DLRoute\Server\DLServer;

/**
 * Crea los `logs` del sistema.
 * 
 * @package DLCore\Config
 * 
 * @version 1.0.0 (release)
 * @author David E Luna M <davidlunamontilla@gmail.com>
 * @copyright 2023 David E Luna M
 * @license MIT
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
