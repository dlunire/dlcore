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

namespace DLCore\Parsers\Slug;

use DLCore\Exceptions\InvalidPath;
use DLRoute\Server\DLServer;

/**
 * Permite resolver la ruta absoluta del sistema
 * 
 * @package DLCore\Parsers\Slug
 * 
 * @version v0.0.1 (release)
 * @author David E Luna M <dlunireframework@gmail.com>
 * @copyright (c) 2026 David E Luna M
 * @license MIT
 */
final class Path extends BasePath {

    /**
     * Devuelve una ruta resuelta absoluta con separadores normalizados en función del sistema operativo
     * en la que esté corriendo el script o la aplicación escrita.
     *
     * @param string $path Ruta a ser analizada y procesada.
     * @param boolean $eval_filename Indica vamos a normalizar el nombre de un archivo, incluyendo, su ruta.
     * @param boolean $dot_separator Indica si se tomará el punto (.) como separador de directorio.
     * @param boolean $collapse Indica si los caracteres seleccionados para colapsar lo hacen. Para indicar que 
     *                          deben colapsar debe valer `true`, caso contrario, no lo hará.
     * @return string
     */
    private static function get_resolve(string $path, bool $eval_filename, bool $dot_separator, bool $collapse): string {
        /** @var non-empty-string $root */
        $root = DLServer::get_document_root();

        /** @var non-empty-string $normalized_path */
        $normalized_path = self::get_normalize_path($path, $dot_separator, $collapse);

        /** @var non-empty-string $filename */
        $resolve_path = $eval_filename
            ? self::normalize_filename($normalized_path, $collapse)
            : $normalized_path;

        /** @var non-empty-string $filename */
        $filename = "{$root}{$resolve_path}";

        return $filename;
    }

    /**
     * Devuelve el nombre del archivo normalizado, donde se toma en cuenta el punto como separador si
     * se requiren para las vistas de plantillas
     *
     * @param string $filename Nombre de archivo a ser normalizado.
     * @param boolean $dot_separator
     *          [Opcional] Indica que debe tomarse el punto (.) como separador de directorio si vale `true`.
     *          Tome en cuenta que el valor por defecto es `false`, es decir, se trata como parte
     *          del nombre de archivo.
     * 
     * @param boolean $collapse
     *          [Opcional] Indica si deben colapsarse los caracteres en uno. Aquellos seleccionados por el núcleo
     *          del manejador de rutas.
     * 
     * @return string
     */
    public static function get_normalize_file(string $filename, bool $dot_separator = false, bool $collapse = false): string {
        /** @var non-empty-string $filename */
        $filename = self::get_normalize_path($filename, $dot_separator, $collapse);

        return $filename;
    }

    /**
     * Resuelve la ruta absoluta del sistema operativo
     *
     * @param string $path Ruta a ser convertida en absoluta
     * @param boolean $dot_separator [Opcional] Indica si los puntos deben tomarse como separadores. El valor
     *                               por defecto es `false`, es decir, que los puntos no son separadores por defecto.
     * 
     * @param boolean $collapse [Opcional] Indica si deben colapsar los caracteres seleccionados para
     *                          dicho propósito. El valor por defecto es `false`, es decir, sin colapsar.
     * @return string
     */
    public static function resolve(string $path, bool $dot_separator = false, bool $collapse = false): string {
        return self::get_resolve($path, false, $dot_separator, $collapse);
    }

    /**
     * Devuelve la ruta absoluta del archivo con los nombres normalizados.
     *
     * @param string $path Ruta a ser analizada
     * @param boolean $dot_separator [Opcional] Indica si el punto (.) se tomará como separador de directorio. El
     *                               valor por defecto es `false`, es decir, que no se tratan como separadores por defecto.
     * 
     * @param boolean $collapse [Opcional] Indica si los caracteres seleccionados previamente en la arquitectura del manejador
     *                          de ruta deben colapsarse en un solo carácter. Los caracteres seleccionados para tal fin no
     *                          colapsan por defecto, es decir, que valen `false`.
     * @return string
     */
    public static function resolve_filename(string $path, bool $dot_separator = false, bool $collapse = false): string {
        return self::get_resolve($path, true, $dot_separator, $collapse);
    }

    /**
     * Devuelve el nombre del archivo normalizado. Incluye ruta normalizada.
     *
     * @param string $filename Nombre del archivo a ser normalizado, incluyendo su ruta completa
     * @param boolean $lowercase [Opcional] Indica si el nombre del archivo debe ser convertida a minúscula o no. El valor
     *                           por defecto es `false`. Para convertirlo a minúscula, debe valer `true`.
     * 
     * @param boolean $collapse [Opcional] Indica si deben colapsar los caracteres seleccionados por el núcleo 
     *                          que manejan las rutas. El valor por defecto es `false`. Si desea colapsarlo en un 
     *                          colo carácter, debe pasar el valor `true` en el parámetro `$collapse`.
     * @return string
     */
    public static function get_filename(string $filename, bool $lowercase = false, bool $collapse = false): string {

        $filename = trim($filename);

        if ($lowercase) {
            $filename = strtolower($filename);
        }

        /**
         * Valor preprocesado de la ruta o nombre de archivo. Debe evaluarse más adelante, si por alguna
         * razón desconocida (aunque improbable) el valor obtenido no es una cadena de texto.
         * 
         * Se requiere que el tipo de dato sea completamente predecible.
         * 
         * @var array|string|null
         */
        $value = preg_replace("/^\.[\/\\\]+/", '', $filename);

        if (!\is_string($value)) {
            $value = '';
        }

        $value = DIRECTORY_SEPARATOR . $value;

        return self::normalize_filename($value, $collapse);
    }

    /**
     * Garantiza la existencia de un directorio válido en el sistema de archivos.
     *
     * Este método normaliza el estado del sistema de archivos asegurando que la
     * ruta indicada exista y sea un directorio. Si el path ya existe y es un
     * directorio, la ejecución finaliza inmediatamente sin efectos secundarios.
     *
     * Si la ruta existe pero corresponde a un archivo regular, el método:
     *  - Respalda su contenido en un archivo con extensión `.backup`
     *  - Elimina el archivo original
     *  - Crea el directorio correspondiente en la misma ruta
     *
     * La creación del directorio solo se intenta cuando el estado del sistema
     * requiere una mutación (ruta inexistente o inválida). En ese caso, se exige
     * que el directorio raíz de la aplicación sea escribible.
     *
     * Este método impone invariantes del framework y no negocia estados inválidos
     * del sistema de archivos.
     *
     * @param string  $path
     *        Ruta lógica o relativa a resolver y normalizar como directorio.
     *
     * @param bool    $dot_separator
     *        Indica si se deben interpretar separadores por punto (`.`) durante
     *        la resolución de la ruta.
     *
     * @param bool    $collapse
     *        Indica si se deben colapsar segmentos redundantes del path durante
     *        la resolución.
     *
     * @throws InvalidPath
     *         Si el directorio raíz de la aplicación no es escribible y se requiere
     *         crear o normalizar la ruta solicitada.
     *
     * @return void
     *
     * @internal
     * Este método forma parte del núcleo de normalización del sistema de archivos
     * y asume control total sobre la estructura esperada del entorno.
     */
    public static function ensure_dir(string $path, bool $dot_separator = false, bool $collapse = false): void {
        /** @var int $old_mask */
        $old_mask = umask(0);

        /** @var non-empty-string $dir */
        $dir = self::resolve($path, $dot_separator, $collapse);

        /** @var non-empty-string $normalized_path */
        $normalized_path = self::get_normalize_path($path, $dot_separator, $collapse);

        if (\file_exists($dir) && \is_dir($dir)) {
            return;
        }

        /** @var non-empty-string $root */
        $root = DLServer::get_document_root();

        if (!\is_writable($root)) {
            umask($old_mask);

            throw new InvalidPath(
                "Asegúrese de establecer los permisos necesarios para crear el directorio «{$normalized_path}» o créelo manualmente"
            );
        }

        if (\file_exists($dir)) {

            /** @var string $content */
            $content = file_get_contents($dir);

            if ($content !== FALSE) {
                file_put_contents("{$dir}.backup", $content);
            }

            unlink($dir);
        }

        mkdir($dir, 0775, true);
        umask($old_mask);
    }

    /**
     * Garantiza la existencia del directorio contenedor de una ruta dada.
     *
     * Este método se comporta de forma análoga a {@see self::ensure_dir}, con la diferencia
     * de que no opera directamente sobre la ruta final, sino sobre su directorio contenedor.
     * Es especialmente útil cuando la ruta representa un archivo que aún no existe.
     *
     * Si el directorio contenedor ya existe y es válido, la operación es idempotente.
     * En caso contrario, se aplican las mismas reglas de validación, permisos y creación
     * definidas por {@see self::ensure_dir}.
     *
     * @param string $path Ruta del archivo o directorio cuyo contenedor debe garantizarse.
     * @param bool $dot_separator [Opcional] Indica si el carácter punto (.) debe tratarse
     *                           como separador de directorios. Por defecto es `false`.
     * @param bool $collapse [Opcional] Indica si los separadores definidos en el núcleo del
     *                  manejador de rutas deben colapsarse en uno solo. Por defecto es `false`.
     *
     * @return void
     *
     * @throws InvalidPath Si el directorio raíz no posee permisos de escritura suficientes
     *                     o si la ruta no puede resolverse de forma segura.
     */
    public static function ensure_container_dir(string $path, bool $dot_separator = false, bool $collapse = false): void {
        /** @var string $dir */
        $dir = dirname($path);
        self::ensure_dir($dir, $dot_separator, $collapse);
    }

    /**
     * Devuelve el directorio HOME del usuario o del entorno de ejecución.
     *
     * Este método resuelve la ruta base asociada al usuario del sistema
     * o al contexto de ejecución actual (CLI, servidor web, contenedor, etc.),
     * independientemente del sistema operativo subyacente.
     *
     * La ruta devuelta **no representa el document root de la aplicación**,
     * sino el directorio personal del usuario que ejecuta el proceso
     * (por ejemplo: `/home/usuario` en sistemas Unix-like o
     * `C:\Users\Usuario` en Windows).
     *
     * Este valor es utilizado como punto de anclaje para:
     * - Configuración local no versionada
     * - Almacenamiento de credenciales de usuario
     * - Cachés de desarrollo
     * - Datos dependientes del entorno y no del proyecto
     *
     * El método abstrae las diferencias entre plataformas (Linux, macOS, Windows)
     * y evita el uso directo de variables de entorno dispersas en el código
     * de dominio.
     *
     * @param string $scope_dir Directorio alternativo que se utilizará dentro de la aplicación, en el caso
     *               de que no sea posible determinar la proporcionada por el sistema operativo.
     * 
     * @return string Ruta absoluta del directorio HOME normalizada para el sistema operativo.
     *
     * @throws InvalidPath Si no es posible determinar el directorio HOME del entorno de ejecución
     *                     actual, además de no poder crearse un directorio para un `/.home` alternativo.
     */
    public static function get_home_dir(string $scope_dir = "/dlunire"): string {
        /** @var non-empty-string|null $home_dir */
        $home_dir = null;

        /** @var non-empty-string $current_scope_dir */
        $current_scope_dir = "/.home/{$scope_dir}";

        foreach (static::HOMES as $home) {
            $home_dir = getenv($home, true);
            if (\is_string($home_dir) && trim($home_dir) !== '')
                break;
        }

        if (!\is_string($home_dir)) {
            self::ensure_dir($current_scope_dir);
            $home_dir = self::resolve($current_scope_dir);
        }

        return $home_dir;
    }

    /**
     * Asegura, siempre que sea posible, el directorio donde se guardarán los archivos con la llave de entropía
     * que se encargará de cifrar o decifrar las credenciales almacenadas en contenedores binarios.
     *
     * @param string $path Ruta relativa del directorio donde se encuentra la llave de entropía.
     * @return void
     * 
     * @throws InvalidPath
     */
    public static function ensure_home_subdir(string $path = "/"): void {
        /** @var non-empty-string $entropy_dir */
        $entropy_dir = self::build_home_path($path);

        /** @var non-empty-string $home */
        $home = self::get_home_dir();

        if (!is_writable($home)) {
            throw new InvalidPath("Asegúrese de tener los permisos necesarios para crear el directorio «{$path}»", 403);
        }

        if (file_exists($entropy_dir) && is_dir($entropy_dir)) {
            return;
        }

        if (file_exists($entropy_dir)) {
            /** @var string $file_content */
            $file_content = file_get_contents($entropy_dir);

            $file_content !== false
                ? file_put_contents("{$entropy_dir}.backup", $file_content)
                : null;

            unlink($entropy_dir);
        }

        mkdir($entropy_dir, 0755, true);
    }

    /**
     * Devuelve una ruta absoluta normalizada, compuesta por el directorio `$HOME`
     * y una ruta relativa.
     *
     * La ruta resultante puede referenciar un archivo o un directorio; esta función
     * no valida la existencia ni el tipo del recurso, ni interactúa con el sistema
     * de archivos.
     *
     * @param string $path Ruta relativa (archivo o directorio).
     * @return non-empty-string Ruta absoluta normalizada.
     *
     * @throws InvalidPath Si no es posible resolver el directorio `$HOME` debido a
     *         restricciones del entorno o permisos insuficientes.
     */
    public static function build_home_path(string $path): string {
        /** @var non-empty-string $home */
        $home = self::get_home_dir();

        /** @var non-empty-string $current_path */
        $current_path = self::get_normalize_path("/.dlunire/{$path}");

        /** @var non-empty-string $full_path */
        $full_path = "{$home}{$current_path}";

        return $full_path;
    }
}