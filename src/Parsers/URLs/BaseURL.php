<?php

declare(strict_types=1);

namespace DLCore\Parsers\URLs;

use DLCore\Exceptions\URLException;
use DLCore\Parsers\Traits\Value;

/**
 * Estructura estándar de una URL (RFC 3986 + WHATWG URL Standard, 2026).
 *
 * **Formato general:**
 * 
 * ```bash
 * scheme://[userinfo@]host[:port]/path[?query][#fragment]
 * ```
 * 
 * @property string      $scheme     Protocolo de la URL. Obligatorio en URLs absolutas.
 *                                   Debe coincidir exactamente con la lista blanca SCHEMES.
 *                                   Caracteres permitidos sin codificar: a-z A-Z 0-9 + - .
 *                                   Regla: primera letra obligatoria, termina siempre en ':'.
 *                                   Ejemplos: http, https, rtmp, icecast.
 *                                   Case-insensitive (se normaliza a minúsculas).
 *
 * @property string|null $userinfo   Información de autenticación (usuario:contraseña@).
 *                                   Caracteres permitidos sin codificar: a-z A-Z 0-9 - . _ ~ ! $ & ' ( ) * + , ; = : @
 *                                   Muy desaconsejado por seguridad (nunca usar contraseñas visibles).
 *                                   Opcional. Si existe, aparece antes del host.
 *
 * @property string      $host       Nombre del host o dominio (obligatorio si hay authority).
 *                                   Caracteres permitidos sin codificar:
 *                                   - Dominios: a-z A-Z 0-9 - . _ ~
 *                                   - IPv4: 0-9 .
 *                                   - IPv6: 0-9 a-f A-F : (siempre entre corchetes [])
 *                                   Case-insensitive (se normaliza a minúsculas).
 *                                   Ejemplos: example.com, 192.168.1.1, [2001:db8::1]
 *
 * @property int|null    $port       Puerto de conexión.
 *                                   Caracteres permitidos: solo dígitos 0-9.
 *                                   Rango válido: 1 a 65535.
 *                                   Opcional. Se omite si es el puerto predeterminado del esquema
 *                                   (80 para http, 443 para https, etc.).
 *
 * @property string      $path       Ruta del recurso en el servidor.
 *                                   Caracteres permitidos sin codificar: a-z A-Z 0-9 - . _ ~ ! $ & ' ( ) * + , ; = : @ /
 *                                   Segmentos separados por /.
 *                                   Opcional. Valor por defecto: '/'.
 *                                   Soporta rutas parametrizadas como /user/{id}/edit.
 *
 * @property string|null $query      Parámetros de consulta.
 *                                   Caracteres permitidos sin codificar: a-z A-Z 0-9 - . _ ~ ! $ & ' ( ) * + , ; = : @ / ?
 *                                   Empieza siempre con ?.
 *                                   Opcional. Formato típico: key=value&otro=2.
 *
 * @property string|null $fragment   Identificador de fragmento (ancla).
 *                                   Caracteres permitidos sin codificar: a-z A-Z 0-9 - . _ ~ ! $ & ' ( ) * + , ; = : @ / ? #
 *                                   Empieza siempre con #.
 *                                   Opcional. Solo tiene significado en el cliente (navegador), no se envía al servidor.
 *
 * **Regla general:**
 * - Cualquier carácter fuera de los permitidos en cada componente debe estar percent-encoded (%HH en UTF-8).
 * - El parser valida estrictamente contra SCHEMES y rechaza cualquier esquema no listado.
 * 
 * @package DLCore\Parsers
 * 
 * @author David E Luna M <dlunireframework@gmail.com>
 * @copyright (c) 2026 - David E Luna M
 * @license MIT
 */
abstract class BaseURL {
    use Value;

    /**
     * Protocolo del la URL
     *
     * @var string $scheme
     */
    protected readonly string $scheme;

    /**
     * Información de autenticación (userinfo).
     * 
     * Formato: usuario[:contraseña]@
     * 
     * Ejemplos válidos:
     * - usuario@example.com                  → solo usuario
     * - miusuario:mipass123@api.ejemplo.com  → usuario + contraseña básica
     * - user_name-with.dots+symbols!@ftp.net → caracteres especiales permitidos
     * - admin:pass:con@dos:puntos@servidor   → contraseña con : y @
     * - usuario%20con%20espacios:contrase%C3%B1a@dominio → espacios y acentos codificados
     * 
     * Caracteres permitidos sin codificar: a-z A-Z 0-9 - . _ ~ ! $ & ' ( ) * + , ; = : @
     * 
     * NOTA IMPORTANTE (2026):
     * - **Nunca** incluir contraseñas en texto plano.
     * - Riesgo alto: visible en logs, historial del navegador, proxies y herramientas de depuración.
     * - Recomendación: usar tokens Bearer en headers o autenticación moderna (OAuth, JWT).
     * - En producción: rechazar userinfo con contraseña (lanzar excepción si se detecta ':').
     */
    protected readonly ?string $userinfo;

    /**
     * URL original a ser parseada.
     *
     * @var string $url
     */
    protected readonly string $url;

    /**
     * Nombre de host o dominio de la URL
     *
     * @var string $host
     */
    protected readonly string $host;

    /**
     * Ruta de la URL
     *
     * @var string|null $path
     */
    protected readonly ?string $path;

    /**
     * Parámetros de la URL
     *
     * @var string|null $query
     */
    protected readonly ?string $query;

    /**
     * Nunca se envía al servidor, es semántica del cliente y empieza con  `#`.
     *
     * @var string|null $fragment
     */
    protected readonly ?string $fragment;

    protected const SCHEMES = [
        # Web estándar (los más importantes)
        'http',
        'https',

        # Transferencia de archivos
        'ftp',
        'sftp',

        # Archivos locales
        'file',

        # Streaming y multimedia (muy relevantes para tu radio)
        'rtmp',
        'rtsp',
        'mms',
        'icecast',      # Icecast/Shoutcast streams
        'hls',          # HTTP Live Streaming (m3u8)
        'dash',         # MPEG-DASH
        'udp',          # UDP para streaming (ej: multicast)

        # Mensajería y contacto
        'mailto',
        'tel',
        'sms',

        # Datos embebidos (útil para imágenes pequeñas, JSON, etc.)
        'data',

        # WebSockets (para chat en vivo, actualizaciones en tiempo real)
        'ws',
        'wss',

        # Otros protocolos modernos/usados frecuentemente
        'git',
        'ssh',
        'ldap',
        'ldaps',
        'ipfs',         # InterPlanetary File System (descentralizado)
        'ipns',
        'magnet',       # Torrents
        'bitcoin',
        'ethereum',
        'coap',         # Constrained Application Protocol (IoT)
        'mqtt',         # Mensajería IoT

        # Protocolos menos comunes pero válidos
        'news',
        'nntp',
        'gopher',       # Nostalgia, pero aún existe
    ];

    public function __construct(string $url) {
        $this->url = strtolower(trim($url));
        $this->load_scheme();
        $this->load_userinfo();
    }

    /**
     * Carga el protocolo de la URL completa
     *
     * @return string
     * @throws URLException
     */
    private function load_scheme(): void {

        /** @var string[] $parts */
        $parts = explode(separator: ":", string: $this->url, limit: 2);

        /** @var string|null $scheme */
        $scheme = $parts[0] ?? null;

        if (\is_string($scheme)) {
            $scheme = trim($scheme);
        }

        if ($scheme === null) {
            throw new URLException("El protocolo no puede ser nulo");
        }

        if (empty($scheme)) {
            throw new URLException("La URL no contiene un protocolo válido", 422);
        }

        if (!\in_array($scheme, self::SCHEMES, true)) {
            throw new URLException("El protocolo seleccionado no existe");
        }

        $this->scheme = trim($scheme);
    }

    /**
     * Devuelve el protocolo o esquema de la URL previamente analizada.
     *
     * @return string
     */
    abstract public function get_escheme(): string;

    /**
     * Carga la información de usuario de la URL.
     *
     * @return void
     * @throws URLException
     */
    private function load_userinfo(): void {

        /**
         * Patrón de extracción de información del usuario. Si no existe un 
         * se almacenará un valor nulo en `$this->userinfo`.
         * 
         * @var string $extraction_pattern
         */
        $extraction_pattern = "/([a-z0-9\-._~!$&\'()*+,;=:@%]+)(?=@)/i";

        /**
         * Patrón de validación. Permite verificar que el escapado se cumpla correctamente
         * 
         * @var string $validation_pattern
         */
        $validation_pattern = "/%[0-9a-f]{2}/i";

        /** @var bool $found */
        $found = \boolval(
            preg_match($extraction_pattern, $this->url, $matches)
        );

        /** @var string $userinfo */
        $userinfo = $matches[0] ?? null;

        if (\is_string($userinfo)) {
            $userinfo = trim($userinfo);
        }

        if (!$found || $userinfo === null) {
            $this->userinfo = null;
            return;
        }

        /** @var boolean $is_valid */
        $is_valid = $this->is_valid_percent($userinfo);

        if (!$is_valid) {
            throw new URLException("La información de usuario contiene una secuencia de escape '%' inválida");
        }

        $this->userinfo = $userinfo;
    }

    /**
     * Valida si el escapado basado en porcentaje es válido o no, pero las reglas son estas:
     * - Si no existe ningun `%`, es `true`.
     * - Si existe `%`, pero no cumple con el formato `%[a-f0-9]{2}`, es `false`. Tendrá una flag (i)
     * para que no se distinga mayúsculas de minúsculas.
     *
     * @param string $input Entrada a ser analizada
     * @return boolean
     */
    private function is_valid_percent(string $input): bool {

        if (!str_contains($input, '%')) {
            return true;
        }

        /**
         * Patrón de validación. Permite verificar que el escapado se cumpla correctamente
         * 
         * @var string $validation_pattern
         */
        $validation_pattern = "/%(?![0-9a-f]{2})/i";

        return !preg_match($validation_pattern, $input);
    }

    /**
     * Devuelve la información de autenticación del usuario
     *
     * @return string|null
     */
    abstract public function get_userinfo(): ?string;

    /**
     * Carga el host o servidor contenido en la URL.
     *
     * @return void
     */
    private function load_host(): void {
        # Pendiente por escribir la lógica para el host
    }

    private function validate_ipv6(string $input): bool {

        return false;
    }

    /**
     * Valida si se trata de una dirección IPv4
     *
     * @param string $input Entrada a ser analizada
     * @return boolean
     */
    private function is_ipv4(string $input): bool {

        $input = trim($input);

        /** @var string $pattern */
        $pattern = "/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/";

        /** @var boolean $found */
        $found = \boolval(
            preg_match($pattern, $input)
        );

        if (!$found) return false;

        /** @var non-empty-string[] $parts */
        $parts = explode(".", $input, 4);

        $this->set_array($parts);
        
        return $this->is_ipaddress_v4();
    }

    public function validate_domain(string $input): bool {
        return false;
    }
}
