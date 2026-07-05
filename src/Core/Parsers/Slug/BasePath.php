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

namespace DLCore\Core\Parsers\Slug;

use DLCore\Exceptions\InvalidPath;

/**
 * Permite normalizar las rutas o nombre de archivos para evitar errores con caracteres inesperados, mientras
 * que al mismo tiempo se busca portabilidad.
 * 
 * Esto no busca ser un parser en el sentido estricto, pero sí establecer algunas reglas formales básicas
 * para tener un comportamiento de las rutas.
 *
 * **Nota:** debes tener en cuenta que los 
 * 
 * @package DLCore\Core\Parsers\Slug
 * 
 * @author David E Luna M <info@dlunire.dev>
 * @copyright (c) 2026 David E Luna M
 * @license AGPL-3.0 license
 */
abstract class BasePath {

    /**
     * Lista de claves para tener acceso al HOME del sistema operativo.
     * 
     * @var non-empty-string[]
     */
    protected const HOMES = [
        'HOME', 'USERPROFILE', 'HOMEDRIVE', 'HOMEPATH', 'APPDATA'
    ];

    /**
     * Normaliza los separadores de directorio al sistema operativo donde está
     * corriendo su aplicación
     * 
     * **Nota:** cuando se toma el punto como separador de directorio con `$dot_separator = true`
     * estamos preparando este método para ser utilizado en vistas que tienen separadores de 
     * directorio de esta forma:
     * 
     * ```
     * <?php
     * ...
     * return view('directorio.subdirectorio.archivo')
     * ```
     * 
     * Donde la función `view` agrega automáticamente la extensión del archivo al final. Este diseño
     * es completamente intencional.
     *
     * @param string $path Ruta a ser normalizada
     * @param boolean $dot_separator Indica si se desea tomar el punto como separador de directorio.
     * @param boolean $collapse [Opcional] Indica si se deben colapsar o no los caracteres en uno si el valor es `true`.
     *                          El valor por defecto es `false`: no colapsa.
     * @return string
     * 
     * @throws InvalidPath
     */
    private static function normalize_separator(string $path, bool $dot_separator, bool $collapse = false): string {
        /** @var string $current_path */
        $current_path = trim(
            string: $path,
            characters: $dot_separator ? '\/\\.' : '\/\\'
        );

        if ($current_path === '') {
            return DIRECTORY_SEPARATOR;
        }

        /** @var non-empty-string $pattern */
        $pattern = $dot_separator
            ? '/[\/\\\.]+/'
            : '/[\/\\\]+/';

        /** @var array|string|null $value */
        $value = preg_replace(
            $pattern,
            DIRECTORY_SEPARATOR,
            $current_path
        );

        if (!\is_string($value)) {
            throw new InvalidPath("No se pudo normalizar la ruta. Resultado inválido después de reemplazar separadores.");
        }

        return trim($collapse ? self::get_collapsed($value) : $value);
    }

    /**
     * Devuelve la ruta con separadores normalizados
     *
     * @param string $path Ruta a ser normalizada
     * @param boolean $dot_separator [Opcional] Indica si el punto (.) se utilizará como separador de directorio.
     * @param boolean $collapse [Opcional] Indica si los caracteres seleccionados a colapsar lo hacen, según 
     *                          vaya evolucionando los caracteres seleccionados para ese propósito. El valor por
     *                          defecto es `false`: no colapsa.
     * @return string
     * 
     * @throws InvalidPath
     */
    public static function get_normalize_path(string $path, bool $dot_separator = false, bool $collapse = false): string {
        return DIRECTORY_SEPARATOR . self::normalize_separator($path, $dot_separator, $collapse);
    }

    /**
     * Devuelve le nombre del archivo normalizado. Si el archivo incluye su ruta, entonces,
     * Se hace con la intención de asegurar portabilidad en diferentes sistemas operativos.
     * 
     * 
     *
     * @param string $filename Nombre de archivo a ser normalizado.
     * @param boolean $collapse Indica si los caracteres seleccionados por el manejador de rutas deben ser colapsados
     *                          o no. Es decir, si vale `true` deben colapsar, caso contrario, valdrá `false`.
     * @return string
     */
    protected static function normalize_filename(string $filename, bool $collapse): string {
        /** @var non-empty-string $pattern */
        $pattern = "/[^a-zá-ź0-9._-]+/i";

        /** @var non-empty-string $file_path */
        $file_path = self::normalize_separator($filename, false, $collapse);

        /** @var non-empty-string[] $parts_path */
        $parts_path = explode(DIRECTORY_SEPARATOR, $file_path);

        /** @var non-empty-string[] $buffer */
        $buffer = [];

        foreach ($parts_path as $part) {
            $value = preg_replace(
                pattern: $pattern,
                replacement: '-',
                subject: $part
            );

            $value = preg_replace("/[.]+/", '.', $value ?? '');

            if (!\is_string($value) || trim($value) === '') {
                continue;
            }

            $buffer[] = $value;
        }

        return DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $buffer);
    }

    /**
     * Colapsa múltiples puntos en uno solo
     *
     * @param string $input Entrada a ser analizada
     * @return string
     */
    private static function get_collapsed_dot(string $input): string {
        if (trim($input) === '')
            return '';

        /** @var array|string|null $value */
        $value = preg_replace("/[.]+/", '.', $input);
        if (!\is_string($value) || trim($value) === '')
            return '';

        return $value;
    }

    /**
     * Colapsa dos o más guiones en uno solo.
     *
     * @param string $input Entrada a ser analizada
     * @return string
     */
    private static function get_collapsed_dash(string $input): string {
        if (trim($input) === '')
            return '';

        $value = preg_replace('/[-]+/', '-', $input);
        if (!\is_string($value) || trim($value) === '')
            return '';

        return $value;
    }

    /**
     * Permite devolver caracteres colapsados.
     *
     * @param string $input Entrada a ser analizada
     * @return string
     */
    private static function get_collapsed(string $input): string {
        /** @var string $value */
        $value = self::get_collapsed_dot($input);
        $value = self::get_collapsed_dash($value);

        return $value;
    }
}