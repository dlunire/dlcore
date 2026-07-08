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
     * Este método constituye el punto de entrada principal del framework y es
     * responsable de preparar el entorno de ejecución antes de transferir el
     * control al motor de enrutamiento.
     *
     * Durante su ejecución se realizan, en el siguiente orden, las operaciones
     * fundamentales de inicialización:
     *
     * 1. Registro de los dominios autorizados para la aplicación.
     * 2. Inicialización del sistema de autorizaciones.
     * 3. Carga de las constantes globales definidas por la aplicación.
     * 4. Carga de los helpers reutilizables.
     * 5. Carga opcional del directorio de rutas (`routes/`).
     * 6. Ejecución del motor de enrutamiento.
     *
     * La carga automática del directorio `routes/` puede habilitarse o
     * deshabilitarse mediante el parámetro `$autoload_routes`. Cuando se
     * establece en `false`, la responsabilidad de registrar las rutas recae
     * completamente sobre el desarrollador antes de invocar este método o desde
     * cualquier otro punto del proceso de inicialización.
     *
     * Esta flexibilidad permite implementar procesos de bootstrap
     * personalizados, arquitecturas modulares, sistemas de plugins,
     * aplicaciones modulares por dominio o cualquier otro mecanismo de registro manual
     * de rutas.
     *
     * Este método no devuelve ningún valor y produce efectos colaterales sobre
     * el estado global de la aplicación durante su proceso de inicialización.
     *
     * @param bool $autoload_routes Indica si debe cargarse automáticamente el
     *                              contenido del directorio `routes/` antes de
     *                              ejecutar el motor de enrutamiento. El valor
     *                              predeterminado es `true`.
     *
     * @return void
     */
    public static function run(bool $autoload_routes = true): void {

        Authorizations::register_domain([
            "localhost"
        ]);

        Authorizations::init();

        self::include_constants_dir();
        self::include_helper_dir();

        if ($autoload_routes) {
            self::include_routes_dir();
        }

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
