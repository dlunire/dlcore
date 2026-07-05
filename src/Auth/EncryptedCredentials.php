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

namespace DLCore\Auth;

use DLCore\Config\EntropyValue;
use DLStorage\Storage\SaveData;

/**
 * Class EncriptedCredentials
 *
 * Representa un contenedor de **credenciales cifradas persistentes**
 * asociado a una llave de entropía gestionada por el entorno de ejecución.
 *
 * Esta clase **no implementa lógica criptográfica directa** ni define
 * algoritmos de cifrado. Su responsabilidad se limita a:
 *
 * - Integrar la gestión de entropía persistente mediante `EntropyValue`.
 * - Delegar el cifrado, descifrado y persistencia segura del payload
 *   al mecanismo proporcionado por `SaveData`.
 *
 * En términos arquitectónicos, esta clase actúa como:
 * - Punto de unión entre el **material criptográfico raíz** (entropía)
 *   y el **contenedor binario cifrado**.
 * - Fachada semántica para el almacenamiento seguro de credenciales
 *   sensibles (por ejemplo, acceso a base de datos).
 *
 * Propiedades clave del diseño:
 * - Las credenciales **no existen en texto plano persistente**.
 * - El acceso al contenido cifrado está condicionado a la existencia
 *   de una llave de entropía válida en el entorno.
 * - El contenedor cifrado es portable, pero **no utilizable sin la entropía**.
 *
 * Esta separación garantiza:
 * - Aislamiento entre código, datos cifrados y secreto criptográfico.
 * - Reducción de superficie de ataque por filtración de configuración.
 * - Inicialización segura controlada por el estado del entorno.
 *
 * @package     DLCore\Auth
 * @license     AGPL-3.0 license
 * @author      David E Luna M <info@dlunire.dev>
 * @copyright   Copyright (c) 2025 David E Luna M
 *
 * @see \DLCore\Config\EntropyValue
 * @see \DLStorage\Storage\SaveData
 */
final class EncryptedCredentials extends SaveData {
    use EntropyValue;
}
