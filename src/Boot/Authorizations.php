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

namespace DLCore\Boot;

use DLCore\Exceptions\AuthorizationException;
use DLRoute\Server\DLServer;
use DLCore\Config\Environment as Env;

class Authorizations implements AuthorizationsInterface {

    /**
     * Dominios registrados que serán permitidos
     *
     * @var array $domains
     */
    private static array $domains = [];

    public static function register_domain(array $domains): void {
        static::$domains = $domains;
    }

    public static function init(): void {

        /**
         * Devuelve el dominio de origen que hace la petición
         * 
         * @var string
         */
        $origin = self::get_origin();

        foreach (self::$domains as $domain) {
            if (!\is_string($domain)) {
                continue;
            }

            $domain = trim($domain);

            if (empty($domain)) {
                continue;
            }

            $pattern = "/((http:\/{2}|https:\/{2}){$domain}\b)(:[0-9]{3,4})?/i";

            if (preg_match($pattern, $origin)) {
                self::allow_origin($origin);
                break;
            }
        }
    }

    /**
     * Permite autorizar el dominio origen
     *
     * @return void
     */
    private static function allow_origin(string $origin): void {
        header("Access-Control-Allow-Origin: {$origin}");
        header("Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Access-Control-Allow-Credentials: true");

        if (DLServer::get_method() === "OPTIONS") {
            http_response_code(200);
            exit;
        }

        self::validate_token($origin);
    }

    /**
     * Devuelve el dominio desde donde se hace petición
     *
     * @return string
     */
    private static function get_origin(): string {

        /**
         * Origen HTTP
         * 
         * @var string $origin
         */
        $origin = $_SERVER['HTTP_ORIGIN'] ?? null;

        return $origin ?? '';
    }

    /**
     * Devuelve el token de autorización
     *
     * @return string
     */
    private static function get_authorization_token(): string {
        $token = "";

        if (\array_key_exists('HTTP_AUTHORIZATION', $_SERVER)) {
            $token = $_SERVER['HTTP_AUTHORIZATION'];
        }

        return trim($token);
    }


    /**
     * Valida el token de autorización para permitir o denegar el acceso basado en el origen de la solicitud.
     *
     * @param string $origin El origen de la solicitud HTTP.
     * @return void
     */
    private static function validate_token(string $origin): void {
        $origin = trim($origin);
        /**
         * Instancia del objeto Env para acceder a variables de entorno.
         * 
         * @var Env $env
         */
        $env = Env::get_instance();

        /**
         * Token de autorización en formato 'Bearer'.
         * 
         * @var string $token
         */
        $token = $env->get_env_value('DL_TOKEN');
        $token = trim($token);

        if (empty($token)) {
            return;
        }

        if (!empty($origin) && "Bearer {$token}" !== self::get_authorization_token()) {
            throw new AuthorizationException("No tienes para realizar esta petición", 403);
        }
    }
}
