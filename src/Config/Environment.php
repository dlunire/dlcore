<?php

namespace DLCore\Config;

use TypeError;

/**
 * Carga todas las variables de entorno
 * 
 * @package DLCore\Config;
 * 
 * @version 1.0.0 (release)
 * @author David E Luna M <davidlunamontilla@gmail.com>
 * @copyright 2023 David E Luna M
 * @license MIT
 */
final class Environment {
    use DLConfig;

    /**
     * Instancia de clase
     *
     * @var self|null
     */
    private static ?self $instance = null;

    /**
     * Credenciales críticas de las variables de entorno
     *
     * @var Credentials|null
     */
    private ?Credentials $credentials = null;

    /**
     * Variables de entorno
     *
     * @var array|null
     */
    private ?array $environment = null;

    public function __construct() {
        $this->parse_file();

        /**
         * Credenciales como objeto
         * 
         * @var object $environment
         */
        $environment = $this->get_environments_as_object();

        $this->environment = (array) $environment;

        $this->credentials = Credentials::get_instance(
            $environment
        );
    }

    /**
     * Devuelve una instanciade clase
     *
     * @return self
     */
    public static function get_instance(): self {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Devuelve las credenciales de las variables de entorno
     *
     * @return Credentials
     * 
     * @throws TypeError
     */
    public function get_credentials(): Credentials {
        if (!($this->credentials instanceof Credentials)) {
            throw new TypeError("Debes instanciar `Envorinment`");
        }

        return $this->credentials;
    }

    /**
     * Obtiene el valor de una variable de entorno. Si no existe la variable de entorno, entonces,
     * devolverá un valor nulo.
     *
     * @param string $varname El nombre de la variable de entorno que se desea obtener.
     * @return string|null
     */
    public function get_env_value(string $varname): ?string {
        /**
         * Valor de la variable de entorno
         * 
         * @var non-empty-string|null $value
         */
        $value = null;

        /** @var boolean $varname_exists */
        $varname_exists = \array_key_exists($varname, $this->environment) &&
            \array_key_exists('value', $this->environment[$varname]);

        if ($varname_exists) {
            $value = $this->environment[$varname]['value'];
        }

        return \is_string($value) ? trim($value) : null;
    }

    /**
     * Alias de `$this->get_env_value`.
     * 
     * Devuelve el valor de la variable de entorno.
     *
     * @param string $varname Nombre de la variable de entorno a ser consultada
     * @return string|null
     */
    public function get(string $varname): ?string {
        return $this->get_env_value($varname);
    }
}
