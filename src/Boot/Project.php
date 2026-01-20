<?php

declare(strict_types=1);

namespace DLCore\Boot;

use DLCore\Parsers\Slug\Path;

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
    }

    private static function auto_include_dir(string $dir): void {

        Path::ensure_dir($dir);
        
        /** @var non-empty-string $path */
        $path = Path::resolve($dir);

        /** @var array|bool $includes */
        $includes  = glob("{$path}/*.php");
        
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
     * Include el directorio donde estarán las constantes
     *
     * @return void
     */
    private static function include_contants_dir(): void {
        self::auto_include_dir("/app/helper");
    }

    private static function include_helper_dir(): void {
        self::auto_include_dir("/app/constanst");
    }
}