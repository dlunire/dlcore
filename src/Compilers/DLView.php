<?php

namespace DLCore\Compilers;

use DLCore\Exceptions\InvalidPath;
use DLCore\Parsers\Slug\Path;
use DLRoute\Server\DLServer;
use Exception;

/**
 * Parsea las plantillas ubicadas en el directorio `resources` con
 * la ayuda de la clase `DLTemplate` y crea archivos PHP listos para
 * ejecutar.
 * 
 * @package DLCore
 * 
 * @author David E Luna M <davidlunamontilla@gmail.com>
 * @license MIT
 * @version v1.0.0
 */
class DLView {

    /**
     * Lista de variables súperglobales de PHP para evitar sean sobrescritas
     * 
     * @var non-empty-array
     */
    private const GLOBAL_VARNAME = [
        'GLOBALS' => "Error: El identificador GLOBALS está reservado por PHP para el acceso al estado global de la aplicación y no puede ser redefinido en una vista",
        '_SERVER' => "Error: El identificador _SERVER es una superglobal de PHP que expone información del entorno de ejecución y no puede ser sobrescrita desde el motor de plantillas",
        '_GET' => "Error: El identificador _GET está reservado para el acceso a parámetros HTTP y no puede ser redefinido en el contexto de una vista.",
        '_POST' => "Error: El identificador _POST es una superglobal de PHP utilizada para datos enviados por formularios y no puede ser sobrescrita en una plantilla.",
        '_FILES' => "Error: El identificador _FILES está reservado para la gestión de archivos subidos y no puede ser redefinido desde una vista.",
        '_COOKIE' => "Error: El identificador _COOKIE es una superglobal de PHP asociada al manejo de cookies y no puede ser sobrescrita por el motor de plantillas.",
        '_SESSION' => "Error: El identificador _SESSION está reservado para la gestión de sesión y no puede ser redefinido en el contexto de una vista.",
        '_REQUEST' => "Error: El identificador _REQUEST es una superglobal que agrega datos de entrada del entorno HTTP y no puede ser sobrescrita desde una plantilla.",
        '_ENV' => "Error: El identificador _ENV está reservado para variables de entorno del sistema y no puede ser redefinido por el motor de plantillas.",
    ];

    /**
     * Variables de control de entorno
     * 
     * @var non-empty-array
     */
    private const ENVIRONMENT_CONTROL = [
        'argc' => "Error: El identificador argc es una variable de control del entorno de ejecución (CLI) y no puede ser utilizada como variable de vista.",
        'argv' => "Error: El identificador argv es una variable interna del entorno de ejecución utilizada para argumentos de línea de comandos y no puede ser redefinida en una plantilla.",
    ];

    /**
     * Convenciones internas que no deben tocarser
     */
    private const INTERNAL_CONVENTIONS = [
        'http_response_header' => "Error: El identificador http_response_header es una convención interna de PHP para el manejo de cabeceras HTTP y no debe ser sobrescrita desde una vista.",
        'php_errormsg' => "Error: El identificador php_errormsg es una variable interna utilizada por PHP para el manejo de errores y no puede ser redefinida por el motor de plantillas.",
    ];

    /**
     * Instancia de la clase DLView
     *
     * @var self|null
     */
    private static ?self $instance = NULL;

    /**
     * Indica si deben almacenarse archivos que se utilizarán como caché. Si es `false`
     * no se cachea.
     *
     * @var boolean $cache
     */
    private static bool $cache = true;

    protected function __construct() {
    }

    /**
     * Valida que el nombre asignado a la variable destinado al motor de plantilla no sobrescriba 
     * a las variables súperglobales de PHP
     *
     * @param string $varname Nombre de variable a ser analizada
     * @return void
     * 
     * @throws Exception
     */
    private static function validate_varname(string $varname): void {
        $varname = trim($varname);

        /** @var non-empty-string|null $message */
        $message = self::get_varname_message($varname);

        if (\is_string($message)) {
            throw new Exception($message);
        }
    }

    /**
     * Devuelve el mensaje de error asociados a los nombres de variables súperglobales de PHP. Si el nombre
     * de variable no pertenece a PHP, entonces, devolverá un valor nulo.
     *
     * @param string $varname Nombre de variable
     * @return string|null
     */
    private static function get_varname_message(string $varname): ?string {
        return self::GLOBAL_VARNAME[$varname]
            ?? self::ENVIRONMENT_CONTROL[$varname]
            ?? self::INTERNAL_CONVENTIONS[$varname]
            ?? null;
    }

    public static function getInstance(): self {
        if (!self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Encuentra las vistas y las procesa
     *
     * @param string $view
     * @return string
     */
    public static function template(string $view, array $data = []): string {

        Path::ensure_dir("/resources");

        /**
         * Archivo de plantillas.
         * 
         * @var string
         */
        $filename = Path::resolve("/resources/{$view}.template.html");

        if (!file_exists($filename)) {
            echo self::set_message("no existe", $filename);
            http_response_code(404);
            exit(1);
        }

        /**
         * Contenido de la plantilla que será procesada.
         * 
         * @var string|false
         */
        $string_template = file_get_contents($filename);

        if ($string_template === FALSE) {
            $string_template = "";
        }

        $string_template = DLTemplate::parse_directive($string_template, $data);

        /**
         * Código compilado devuelto
         * 
         * @var string
         */
        $code = DLTemplate::build($string_template);
        $code = preg_replace('/\s+/', ' ', $code);
        $code = preg_replace('/(?<=\>)\s+(?=\<)/', '', $code);

        return $code;
    }

    /**
     * Permite cargar la vista
     *
     * @param string $view Vista a ser analizada y convertida a código PHP.
     * @param array $varnames Permitirá definir las variables dentro el motor de plantillas desde el controlador
     * @return void
     * 
     * @throws Exception
     * @throws InvalidPath
     */
    public static function load(string $view, array $varnames = []): void {
        /** @var string $build_name */
        $build_name = ".build";

        foreach ($varnames as $varname => $value) {
            if (!\is_string($varname)) {
                throw new Exception("El identificador de la variable debe ser una cadena válida.");
            }

            if (trim($varname) === '') {
                throw new Exception("El identificador de la variable no puede estar vacío.");
            }

            if (!\preg_match('/^[a-z_][a-z0-9_]*$/i', $varname)) {
                throw new Exception("El identificador de la variable no cumple con la gramática de PHP.");
            }

            self::validate_varname($varname);

            ${$varname} = $value;
        }

        /** @var string $template_file */
        $template_file = Path::get_normalize_file($view, true);

        /** @var string $filename */
        $filename = Path::resolve("/{$build_name}{$template_file}.php");

        Path::ensure_container_dir("/{$build_name}{$template_file}");

        /** @var string $string_template */
        $string_template = self::template(view: $template_file);
        $string_template = self::trim_quote($string_template);

        if (!\file_exists($filename)) {
            file_put_contents($filename, $string_template);
        }

        /** @var non-empty-string $hash_file */
        $hash_file = hash_file('sha1', $filename);

        /** @var non-empty-string $hash_view */
        $hash_view = hash('sha1', $string_template);

        if ($hash_file !== $hash_view) {
            file_put_contents($filename, $string_template);
        }

        if (\file_exists($filename)) {
            include $filename;
        }
    }

    /**
     * Devuelve un mensaje formateado.
     *
     * @param string $message
     * @param string $template
     * @return string
     */
    private static function set_message(string $message, string $template): string {
        $styles = "style=\"font-family: 'Open Sans', sans-serif, arial; font-weight: normal; padding: 20px; width: calc(100% - 20px); border-radius: 5px; background-color: #d00000; color: white; margin: 30px auto; max-width: 1024px\"";

        $message = "<style>:root {background-color: #333333}</style><h3 {$styles}>La plantilla <strong style=\"padding: 10px\">{$template}</strong> {$message}</h3>\n\n";
        return $message;
    }

    /**
     * Elimina las comillas
     *
     * @param string $string
     * @return string
     */
    public static function trim_quote(string $string): string {
        $string = trim($string, "\"\'\`");
        return $string;
    }

    /**
     * Desactiva el uso de caché del motor de plantillas
     *
     * @return void
     */
    public function disable_cache(): void {
        self::$cache = false;
    }
}
