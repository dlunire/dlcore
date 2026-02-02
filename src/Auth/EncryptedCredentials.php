<?php

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
 * @version     v0.0.1 (release)
 * @package     DLCore\Auth
 * @license     MIT
 * @author      David E Luna M
 * @copyright   Copyright (c) 2025 David E Luna M
 *
 * @see \DLCore\Config\EntropyValue
 * @see \DLStorage\Storage\SaveData
 */
final class EncryptedCredentials extends SaveData {
    use EntropyValue;
}
