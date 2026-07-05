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

namespace DLCore\Boot;

/**
 * Contrato base para la gestión de autorizaciones a nivel de dominio.
 *
 * Esta interfaz define el comportamiento mínimo que deben implementar
 * los sistemas de autorización responsables de controlar el acceso
 * a la aplicación en función del origen de las peticiones (por ejemplo,
 * políticas tipo CORS, listas blancas de dominios, o reglas equivalentes).
 *
 * La responsabilidad de las implementaciones concretas es:
 * - Registrar dominios explícitamente autorizados.
 * - Inicializar y aplicar las reglas de autorización definidas.
 *
 * Esta interfaz forma parte del proceso de arranque (bootstrapping)
 * del núcleo del framework.
 *
 * @package DLCore\Boot
 *
 *
 * @author David E Luna M <info@dlunire.dev>
 *
 * @copyright (c) 2026 David E Luna M
 * @license AGPL-3.0 license
 */
interface AuthorizationsInterface {
    /**
     * Registra los dominios autorizados para realizar peticiones a la aplicación.
     *
     * Este método debe almacenar o configurar la lista de dominios
     * desde los cuales se aceptarán solicitudes entrantes.
     *
     * La interpretación de esta lista (comparación exacta, comodines,
     * subdominios, esquemas, etc.) queda bajo la responsabilidad de la
     * implementación concreta.
     *
     * @param array<string> $domains
     *     Lista de dominios permitidos (por ejemplo: example.com, api.example.com).
     *
     * @return void
     */
    public static function register_domain(array $domains): void;

    /**
     * Inicializa el sistema de autorizaciones.
     *
     * Este método debe ejecutar toda la lógica necesaria para activar
     * el control de accesos previamente configurado, tales como:
     * - Aplicación de políticas de autorización.
     * - Registro de cabeceras de seguridad.
     * - Validaciones previas al enrutamiento.
     *
     * Debe invocarse durante la fase de arranque de la aplicación,
     * antes de procesar cualquier petición entrante.
     *
     * @return void
     */
    public static function init(): void;
}
