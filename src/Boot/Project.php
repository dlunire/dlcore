<?php

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

    public static function run(): void {
        self::include_contants_dir();
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
        $includes  = glob($path);
        
        if (!\is_array($includes)) {
            return;
        }

        foreach ($includes as $include) {
            if (!\file_exists($include)) {
                continue;
            }

            include $include;
        }
    }

    /**
     * Include el directorio donde se definirán las rutas. Esto permite tener
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
    private static function include_contants_dir(): void {
        self::auto_include_dir("/app/constants");
    }

    /**
     * Incluye el directorio donde se incluirán los archivos que permitirán definir todos los 
     * helpers globales de la aplicación.
     *
     * @return void
     */ 
    private static function include_helper_dir(): void {
        self::auto_include_dir("/app/helpers");
    }
}