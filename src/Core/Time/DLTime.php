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

namespace DLCore\Core\Time;

use DateTimeImmutable;
use DateTimeZone;
use DLCore\Exceptions\InvalidDate;

/**
 * Copyright (c) 2025 David E Luna M
 * Licensed under the MIT License. See LICENSE file for details.
 *
 * @package DLUnire\Core\Time
 * 
 * @author  David E Luna M
 * @copyright (c) 2026 David E Luna M
 * @license AGPL-3.0 license
 *
 * Primitiva temporal centralizada del framework.
 *
 * Proporciona acceso consistente, inmutable y de alta precisión al tiempo,
 * sin introducir dependencias externas ni acoplamientos innecesarios.
 */
final class DLTime {
    /**
     * Devuelve el instante actual como DateTimeImmutable.
     *
     * @param DateTimeZone|null $timezone Zona horaria opcional (por defecto UTC).
     * @return DateTimeImmutable
     */
    public static function now(?DateTimeZone $timezone = null): DateTimeImmutable {
        return new DateTimeImmutable(
            'now',
            $timezone ?? new DateTimeZone('UTC')
        );
    }

    /**
     * Devuelve la fecha y hora actual con precisión de microsegundos,
     * en formato ISO extendido.
     *
     * Ejemplo: 2026-01-29 14:32:10.123456
     *
     * @param DateTimeZone|null $timezone
     * @return non-empty-string
     */
    public static function now_string(?DateTimeZone $timezone = null): string {
        return self::now($timezone)->format('Y-m-d H:i:s.u');
    }

    /**
     * Devuelve una representación temporal segura para su uso en nombres de archivo
     * o rutas, derivada de la fecha y hora actuales.
     *
     * La fecha se serializa reemplazando caracteres no seguros para el sistema de
     * archivos (espacios, dos puntos, puntos) por guiones. Este método no valida ni
     * interpreta semánticamente la fecha; únicamente transforma su representación
     * textual.
     *
     * @param DateTimeZone|null $timezone Zona horaria opcional.
     * @return non-empty-string Cadena segura para nombres de archivo.
     *
     * @throws InvalidDate Si no es posible generar una representación válida.
     */

    public static function now_for_filename(?Datetimezone $timezone = null): string {
        /** @var non-empty-string $date */
        $date = self::now_string($timezone);

        /** @var array|string|null $path_date */
        $path_date = preg_replace("/[\s:.]+/", "-", $date);

        if (!\is_string($path_date) || trim($path_date) === '') {
            throw new InvalidDate("Imposible hacer la conversión a formato de ruta de archivos");
        }

        return trim($path_date);
    }

    /**
     * Devuelve el timestamp UNIX con microsegundos como string,
     * evitando pérdida de precisión por casting a float.
     *
     * Ejemplo: 1706548330.123456
     *
     * @return non-empty-string
     */
    public static function unix_microtime(): string {
        return self::now()->format('U.u');
    }

    /**
     * Devuelve el instante actual en UTC explícitamente.
     *
     * @return DateTimeImmutable
     */
    public static function utc(): DateTimeImmutable {
        return self::now(new DateTimeZone('UTC'));
    }
}
