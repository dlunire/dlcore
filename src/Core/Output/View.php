<?php

declare(strict_types=1);

namespace DLCore\Core\Output;

use DLCore\Compilers\DLView;
use Exception;

/**
 * Clase View
 * 
 * Se encarga de la gestión de vistas en formato HTML dentro del sistema.
 * Extiende de DLView para aprovechar su funcionalidad de compilación y renderizado.
 * 
 * @package DLCore\Core\Output
 * @author David E Luna M <davidlunamontilla@gmail.com>
 * @copyright 2025 David E Luna M
 * @license MIT
 */
final class View extends DLView {

    /**
     * Constructor de la clase View.
     * 
     * Inicializa la vista heredando la configuración y métodos de DLView.
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Obtiene el contenido renderizado de una vista en formato HTML.
     * 
     * @param string $view Nombre o ruta de la vista o plantilla
     *     - La ruta por defecto es `welcome`.
     *     - La estructura de rutas puede utilizar barras diagonales `/` o puntos `.` para la separación.
     * 
     * @param array $varnames Permite definir el nombre de las variables dentro del motor de plantillas. No debes
     *              definir nombres de variables súperglobales propias de PHP, porque lanzará una excepción con
     *              el error específico.
     * 
     * @return string Contenido HTML generado a partir de la vista.
     * 
     * @throws Exception
     */
    public static function get(string $view = 'welcome', array $varnames = []): string {
        new self();

        ob_start();
        self::load($view, $varnames);

        /**
         * Contenido obtenido de la vista tras la carga y renderizado.
         * 
         * @var string
         */
        $content = (string) ob_get_clean();

        return trim($content);
    }
}
