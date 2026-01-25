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

namespace DLCore\Boot;

use DLCore\Parsers\Slug\Path;
use DLRoute\Requests\DLRoute;

/**
 * Maneja el punto de entrada de la aplicación
 * 
 * @package DLCore\Boot
 * 
 * @version v0.0.1 (release)
 * @author David E Luna M <dlunireframework@gmail.com>
 * @copyright (c) 2026 David E Luna M
 * @license MIT
 */
class Project {
    /**
     * Ejecuta el proceso de inicialización y arranque de la aplicación.
     *
     * Este método actúa como punto de entrada del núcleo del framework. Su
     * responsabilidad es preparar el entorno de ejecución cargando, en un
     * orden bien definido, los siguientes componentes:
     *
     * 1. Directorio de constantes globales de la aplicación.
     * 2. Directorio de helpers reutilizables.
     * 3. Definiciones de rutas del sistema.
     *
     * Una vez cargados estos recursos, delega el control al motor de ruteo
     * para resolver y ejecutar la ruta correspondiente a la petición actual.
     *
     * Este método no devuelve ningún valor y su ejecución tiene efectos
     * colaterales sobre el estado global de la aplicación.
     *
     * @return void
     */
    public static function run(): void {
        self::include_constants_dir();
        self::include_helper_dir();
        self::include_routes_dir();

        DLRoute::execute();
    }


    /**
     * Permite establecer la lógica donde se asegurará la creación de los directorios de archivos
     * auto-incluidos en los directorios seleccionados.
     * 
     * Si los directorios no existen, se crearán la primera vez que se ejecute la aplicación. El objetivo
     * es reducir errores humanos en directorios no opinables.
     *
     * @param string $dir Directorio donde se incluirán archivos globales de forma automática.
     * @return void
     */
    private static function auto_include_dir(string $dir): void {

        Path::ensure_dir($dir);

        /** @var non-empty-string $path */
        $path = Path::resolve("{$dir}/*.php");

        /** @var array|bool $includes */
        $includes = glob($path);

        if (!\is_array($includes)) {
            return;
        }

        foreach ($includes as $include) {
            if (!\file_exists($include)) {
                continue;
            }

            require_once $include;
        }
    }

    /**
     * Incluye el directorio donde se definirán las rutas. Esto permite tener
     * oportunidad de definir rutas semánticas.
     *
     * @return void
     */
    private static function include_routes_dir(): void {
        self::auto_include_dir("/routes");
    }

    /**
     * Incluye el directorio donde se crearán los archivos PHP que permitirán las constantes
     * globales de la aplicación
     *
     * @return void
     */
    private static function include_constants_dir(): void {
        self::auto_include_dir("/app/Constants");
    }

    /**
     * Incluye el directorio donde se incluirán los archivos que permitirán definir todos los 
     * helpers globales de la aplicación.
     *
     * @return void
     */
    private static function include_helper_dir(): void {
        self::auto_include_dir("/app/Helpers");
    }
}