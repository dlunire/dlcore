<?php

declare(strict_types=1);

namespace DLCore\Parsers;

use DLCore\Exceptions\URLException;

abstract class BaseURL {

    /**
     * Protocolo del la URL
     *
     * @var string $scheme
     */
    protected string $scheme;

    /**
     * Contiene la información del usuario en una URL
     *
     * @var string|null $userinfo
     */
    protected ?string $userinfo;

    /**
     * URL original a ser parseada.
     *
     * @var string $url
     */
    protected string $url;

    /**
     * Nombre de host o dominio de la URL
     *
     * @var string $host
     */
    protected string $host;

    /**
     * Ruta de la URL
     *
     * @var string|null $path
     */
    protected ?string $path;

    /**
     * Parámetros de la URL
     *
     * @var string|null $query
     */
    protected ?string $query;

    /**
     * Nunca se envía al servidor, es semántica del cliente y empieza con  `#`.
     *
     * @var string|null $fragment
     */
    protected ?string $fragment;

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
    }

    /**
     * Carga el protocolo de la URL completa
     *
     * @return string
     */
    private function load_scheme(): void {

        /** @var string[] $parts */
        $parts = explode(separator: ":", string: $this->url);

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

        if (!\in_array($scheme, self::SCHEMES)) {
            throw new URLException("El protocolo seleccionado no existe");
        }

        $this->scheme = trim($scheme);
    }
}
