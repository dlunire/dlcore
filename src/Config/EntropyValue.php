<?php

declare(strict_types=1);

namespace DLCore\Config;

use DLCore\Core\Time\DLTime;
use DLCore\Exceptions\FileNotFoundException;
use DLCore\Exceptions\InvalidDate;
use DLCore\Exceptions\InvalidPath;
use DLCore\Parsers\Slug\Path;

/**
 * Trait EntropyValue
 *
 * Proporciona la gestión de la **llave de entropía criptográfica** utilizada
 * como material secreto primario para desbloquear contenedores binarios cifrados.
 *
 * Este trait **NO maneja contenido criptográfico cifrado**, ni representa datos
 * protegidos. Su única responsabilidad es administrar la **entropía persistente**
 * que actúa como llave raíz (o semilla directa de derivación) para habilitar
 * el acceso a estructuras criptográficas externas.
 *
 * En términos formales, la entropía aquí gestionada:
 * - Representa **material criptográfico sensible**
 * - Actúa como **llave de desbloqueo** de contenedores cifrados
 * - Debe mantenerse **separada del payload cifrado**
 * - Puede ser persistida, recuperada o regenerada de forma controlada
 *
 * Este diseño permite:
 * - Aislamiento entre contenido cifrado y secreto criptográfico
 * - Portabilidad de contenedores sin exponer la llave
 * - Rotación o invalidación de llaves sin alterar el formato del contenedor
 *
 * El trait integra el entorno de ejecución mediante `DLEnvironment`,
 * garantizando que la entropía se gestione dentro de un contexto controlado
 * por la infraestructura del framework.
 *
 * @version     v0.0.1
 * @package     Cryptography\Entropy
 * @license     MIT
 * @author      David E Luna M
 * @copyright   Copyright (c) 2025 David E Luna M
 *
 * @see DLEnvironment
 */
trait EntropyValue {
    use DLEnvironment;

    /**
     * Recupera el valor de una variable de entorno sin aplicar validación ni
     * transformación adicional.
     *
     * Este método actúa como un acceso controlado al sistema de entorno utilizado
     * por DLCore. El valor retornado representa una ruta relativa previamente
     * normalizada por el motor de rutas, por lo que es independiente del sistema
     * operativo.
     *
     * Si la variable no existe o no está definida, se devuelve `null`.
     * 
     *
     * @param string $varname Nombre de la variable de entorno a consultar.
     *
     * @return string|null Valor de la variable de entorno, o `null` si no existe
     *                     o no está definida.
     */
    private static function get_value(string $varname): ?string {
        /** @var Environment */
        $environment = Environment::get_instance();
        return $environment->get($varname);
    }

    /**
     * Determina y garantiza la existencia de una llave de entropía persistente asociada
     * a una ruta configurada mediante una variable de entorno.
     *
     * Este método implementa una política de resolución de estado:
     * - Obtiene una ruta relativa desde una variable de entorno.
     * - Garantiza la existencia del directorio base dentro del `$HOME`.
     * - Resuelve la ruta absoluta del archivo de entropía.
     * - Si el archivo no existe, está vacío o el estado previo es inconsistente,
     *   genera una nueva llave de entropía y la persiste.
     * - Si el archivo existe y contiene una llave válida, reutiliza dicho valor.
     *
     * En caso de detectar un estado inesperado (por ejemplo, un directorio donde se
     * esperaba un archivo), el método respalda el recurso existente y restablece
     * el estado esperado generando una nueva llave.
     *
     * Este método puede crear directorios, escribir archivos y generar valores
     * aleatorios como efectos colaterales.
     *
     * @param string $varname Nombre de la variable de entorno que define la ruta
     *                        relativa del archivo de entropía. No distingue entre
     *                        mayúsculas y minúsculas.
     *
     * @return non-empty-string Llave de entropía válida y persistente.
     *
     * @throws InvalidPath Si la variable de entorno no está definida, está vacía o
     *                     no es posible resolver o preparar la ruta de almacenamiento.
     * @throws InvalidDate Si no es posible generar una representación temporal válida
     *                     utilizada para respaldos.
     */
    private static function determine_entropy_value(string $varname): string {
        /** @var non-empty-string|null $file_path */
        $file_path = self::get_value(strtoupper(trim($varname)));

        if ($file_path === null || trim($file_path) === '') {
            throw new InvalidPath(
                \sprintf("La ruta de archivo es requerida en «%s»", strtoupper(trim($varname)))
            );
        }

        /** @var non-empty-string $file_full_path */
        $file_full_path = Path::build_home_path($file_path);

        Path::ensure_home_subdir($file_full_path);

        /** @var non-empty-string $basename_hash */
        $basename_hash = hash('sha256', basename($file_full_path));

        /** @var non-empty-string $filename */
        $filename = $file_full_path . DIRECTORY_SEPARATOR . $basename_hash;


        /** @var non-empty-string $date */
        $date = DLTime::now_for_filename();

        /** @var non-empty-string $entropy_value */
        $entropy_value = bin2hex(random_bytes(20));

        if (\is_dir($filename)) {
            rename($filename, "{$filename}-{$date}.backup");
            file_put_contents($filename, $entropy_value);
            return $entropy_value;
        }

        /** @var string $entropy_value_from_filename */
        $entropy_value_from_filename = file_get_contents($filename);

        if (!\is_string($entropy_value_from_filename) || trim($entropy_value_from_filename) === '') {
            file_put_contents($filename, $entropy_value);
            return $entropy_value;
        }

        return $entropy_value_from_filename;
    }

    private static function ensure_file_writing(string $filename): void {
        /** @var non-empty-string $entropy_value */
        $entropy_value = bin2hex(string: random_bytes(length: 100));

        /** @var non-empty-string $date */
        $date = DLTime::now_for_filename();

        /** @var string */
        $renamed = false;

        if (\file_exists($filename) && \is_dir($filename)) {
            rename(from: $filename, to: "{$filename}-{$date}.backup");
        }
    }

    /**
     * Obtiene una fuente de entropía persistente y garantiza la existencia
     * de un flujo de bytes no vacío.
     *
     * Este método valida únicamente:
     * - Que el archivo exista.
     * - Que no sea un directorio.
     * - Que sea legible.
     * - Que contenga al menos un byte.
     *
     * No valida formato, imprimibilidad, codificación ni calidad
     * criptográfica de la entropía. El contenido es tratado como
     * material binario crudo y opaco.
     *
     * El método nunca retorna valores vacíos ni falsos: ante cualquier
     * incumplimiento del contrato, lanza una excepción explícita.
     *
     * @param string $filename Ruta del archivo que actúa como fuente de entropía.
     *
     * @return non-empty-string Raw entropy bytes (flujo binario no vacío).
     *
     * @throws FileNotFoundException
     *         Si el archivo no existe, es un directorio, no es legible
     *         o no contiene bytes de entropía.
     */
    private static function require_entropy_bytes(string $filename, int $length = 20): string {
        /** @var non-empty-string|null $content */
        $content = null;

        if (!\file_exists($filename)) {
            throw new FileNotFoundException();
        }

        if (\is_dir($filename)) {
            throw new FileNotFoundException("El archivo que intentas consultar es un directorio");
        }

        /** @var bool|non-empty-string $content */
        $content = file_get_contents($filename);

        if ($content === false || \strlen($content) === 0) {
            throw new FileNotFoundException("No se pudo obtener la llave de entropía del archivo a consultar");
        }

        if (\strlen($content) < $length) {
            throw new FileNotFoundException("La llave de entropía debe contar al menos, con {$length} bytes");
        }

        return $content;
    }

    /**
     * Obtiene una llave de entropía persistente asociada a una ruta configurada
     * mediante una variable de entorno.
     *
     * Este método actúa como punto de acceso público a la política interna de
     * resolución de llaves de entropía. Si la llave no existe, está vacía o el
     * estado previo es inconsistente, se garantiza la generación y persistencia
     * de una nueva llave válida.
     *
     * La lógica de creación, validación, respaldo y persistencia es delegada
     * completamente al mecanismo interno correspondiente.
     *
     * @param string $varname Nombre de la variable de entorno que define la ruta
     *                        relativa del archivo de entropía. No distingue entre
     *                        mayúsculas y minúsculas.
     *
     * @return non-empty-string Llave de entropía válida.
     *
     * @throws InvalidPath Si la ruta no está configurada o no puede resolverse.
     * @throws InvalidDate Si no es posible generar información temporal requerida
     *                     durante el proceso.
     */
    public static function get_key_entropy(string $varname = 'file_path'): string {
        return self::determine_entropy_value($varname);
    }
}