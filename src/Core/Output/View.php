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
 * @author David E Luna M <info@dlunire.dev>
 * @copyright 2025 David E Luna M
 * @license AGPL-3.0 license
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
