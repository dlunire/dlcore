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

namespace DLCore\Compilers;

use DLRoute\Server\DLServer;

/**
 * Permite parsear archivos Markdown con la ayuda de una 
 * biblioteca.
 * 
 * @package DLCore\Compilers
 * 
 * @author David E Luna M <info@dlunire.dev>
 * @license AGPL-3.0 license
 */
class DLMarkdown {
    /**
     * Instancia de la clase DLMarkdown
     *
     * @var self|null
     */
    private ?self $instance = NULL;

    private function __construct() {
    }

    /**
     * Devuelve una instancia
     *
     * @return self
     */
    public static function getInstance(): self {
        if (!self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Parsea un archivo escrito con la sintaxis de Markdown
     * con la ayuda de una clase y método externo.
     *
     * @param string $view
     * @return string
     */
    public static function parse(string $view): string {
        $root = DLServer::get_document_root();

        $pattern = "/\./";
        $markdown = "";

        $view = trim($view);
        $view = trim($view, ".");
        $view = preg_replace($pattern, DIRECTORY_SEPARATOR, $view);

        /**
         * Obtiene la ruta del archivo Markdown que se va a parsear
         * en documentos HTML.
         * 
         * @var string $filename
         */
        $filename = "{$root}/resources/{$view}.md";

        if (!file_exists($filename)) {
            return $markdown;
        }

        /**
         * Contenido del archivo Markdown previamente seleccionado.
         * 
         * @var string $markdown
         */
        $markdown = (string) file_get_contents($filename) ?? '';
        $markdown = self::stringMarkdown($markdown);

        return $markdown;
    }

    /**
     * Parsea contenido markdown con la ayuda de la clase `GithubFlavoredMarkdownConverter`.
     *
     * @param string $stringMarkdown
     * @return string
     */
    public static function stringMarkdown(string $stringMarkdown): string {
        $exists = class_exists('League\CommonMark\GithubFlavoredMarkdownConverter') &&
            method_exists('League\CommonMark\GithubFlavoredMarkdownConverter', 'convert');

        if ($exists) {
            $converter = new \League\CommonMark\GithubFlavoredMarkdownConverter([
                'html_input' => 'strip',
                'allow_unsafe_link' => false
            ]);

            $stringMarkdown = $converter->convert($stringMarkdown);
        }

        if (!$exists) {
            $stringMarkdown = "<p>Procesa a instalar la herramienta que falta mediante la siguiente línea:</p>";
            $stringMarkdown .= "<pre style=\"padding: 10px; border-radius: 5px; background-color: #00000050\">composer require league/commonmark</pre>";
        }

        return $stringMarkdown;
    }
}
