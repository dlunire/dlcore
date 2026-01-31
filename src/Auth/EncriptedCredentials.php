<?php

declare(strict_types=1);

namespace DLCore\Auth;

use DLCore\Config\EntropyValue;
use DLStorage\Storage\SaveData;

/**
 * Permite almacenar credenciales cifradas, pero ésta clase no busca cifrar las credenciales, 
 * ya que de esa tarea se encarga `SaveData`.
 * 
 * @package DLCore\Auth
 */
final class EncriptedCredentials extends SaveData {
    use EntropyValue;
}