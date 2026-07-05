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

namespace DLCore\Boot;

use DLCore\Core\Parsers\Slug\Path;
use DLRoute\Requests\DLRoute;

/**
 * Maneja el punto de entrada de la aplicación
 * 
 * @package DLCore\Boot
 * 
 * @author David E Luna M <info@dlunire.dev>
 * @copyright (c) 2026 David E Luna M
 * @license AGPL-3.0 license
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

        Authorizations::register_domain([
            "localhost"
        ]);

        Authorizations::init();

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