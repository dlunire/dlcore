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

namespace DLCore\Config;

use TypeError;

/**
 * Carga todas las variables de entorno
 * 
 * @package DLCore\Config;
 * 
 * @author David E Luna M <info@dlunire.dev>
 * @copyright 2023 David E Luna M
 * @license AGPL-3.0 license
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
