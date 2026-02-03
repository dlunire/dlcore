<?php

declare(strict_types=1);

namespace DLCore\Config;

use DLCore\Core\Time\DLTime;
use DLCore\Exceptions\FileNotFoundException;
use DLCore\Exceptions\InvalidDate;
use DLCore\Exceptions\InvalidPath;
use DLCore\Parsers\Slug\Path;
use DLRoute\Server\DLServer;
use DLStorage\Errors\ValueError;
use InvalidArgumentException;

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
 * @package      DLCore\Config
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
        $value =  $environment->get_env_value($varname);
        
        return $value;
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

        /** @var boolean $multitenant */
        $multitenant = self::get_boolean('multitenant');

        // var_dump($multitenant); exit;

        if ($file_path === null || trim($file_path) === '') {
            throw new InvalidPath(
                \sprintf("La ruta de archivo es requerida en «%s»", strtoupper(trim($varname)))
            );
        }

        /** @var non-empty-string $domain_for_filename */
        $domain_for_filename = Path::get_normalize_file(DLServer::get_host());

        /** @var non-empty-string $relative_path */
        $relative_path = "{$file_path}{$domain_for_filename}";

        /** @var non-empty-string $file_full_path */
        $file_full_path = Path::build_home_path($relative_path);

        Path::ensure_home_subdir($relative_path);

        /** @var non-empty-string $basename_hash */
        $basename_hash = hash('sha256', basename($file_full_path));

        /** @var non-empty-string $filename */
        $filename = $file_full_path . DIRECTORY_SEPARATOR . $basename_hash;

        self::rename_colliding_directory($filename);

        /** @var non-empty-string $entropy */
        $entropy = self::ensure_entropy_file($filename, 40);

        return $entropy;
    }

    /**
     * Obtiene el valor booleano de una variable definida en `dlunire.env.type`.
     *
     * Este método interpreta explícitamente el valor de una variable de entorno
     * como un booleano estricto, aceptando únicamente los literales:
     *
     * - `"true"`
     * - `"false"`
     *
     * Cualquier otro valor será considerado un error de tipo y provocará
     * una excepción, evitando ambigüedades semánticas propias de los entornos
     * dinámicos.
     *
     * La lectura del valor se realiza a través del sistema de entropía de DLUnire,
     * lo que garantiza que el origen del dato no proviene directamente del sistema
     * operativo, sino de la capa lógica controlada por el framework.
     *
     * @param non-empty-string $varname
     *        Nombre de la variable lógica definida en `dlunire.env.type`
     *        que se desea interpretar como booleano.
     *
     * @return bool
     *         Retorna `true` o `false` únicamente si el valor de la variable
     *         coincide exactamente con los literales permitidos.
     *
     * @throws ValueError
     *         Si el valor asociado a la variable no es un literal booleano válido.
     * 
     * @throws InvalidArgumentException
     *         Si el argumento contiene una cadena vacía.
     *
     * @internal
     *         Este método forma parte del mecanismo interno de tipado estricto
     *         del sistema de configuración de DLUnire y no debe ser utilizado
     *         directamente por código de usuario.
     */
    private static function get_boolean(string $varname): bool {
        /** @var string $new_varname */
        $new_varname = strtoupper(trim($varname));

        if ($new_varname === '') {
            throw new InvalidArgumentException("El campo 'varname' es requerido");
        }

        /** @var non-empty-string|null $value */
        $value = self::get_value($new_varname);

        var_dump($value); exit;

        if ($value !== "true" && $value !== "false") {
            throw new ValueError("Error de tipo en '{$varname}'");
        }

        return false;
    }

    /**
     * Renombra un directorio existente cuando su nombre colisiona con el nombre
     * de un archivo esperado.
     *
     * Si el path indicado existe y corresponde a un directorio, este método
     * intenta renombrarlo agregando un sufijo de respaldo basado en la fecha
     * actual. Si el path no existe o corresponde a un archivo regular, no se
     * realiza ninguna acción.
     *
     * Este método no valida la ruta ni asegura la existencia de directorios;
     * su única responsabilidad es resolver la colisión de nombre mediante
     * el renombrado del directorio.
     *
     * @param string $filename Ruta cuyo nombre puede colisionar con un archivo.
     *
     * @throws InvalidPath Si no es posible renombrar el directorio existente
     *                     que colisiona con el nombre del archivo.
     */
    private static function rename_colliding_directory(string $filename): void {
        /** @var non-empty-string $date */
        $date = DLTime::now_for_filename();

        if (!\file_exists($filename) || !\is_dir($filename)) {
            return;
        }

        /** @var boolean $renamed */
        $renamed = rename(from: $filename, to: "{$filename}-{$date}.backup");

        if (!$renamed && \file_exists($filename)) {
            throw new InvalidPath(
                "No fue posible renombrar el directorio existente que colisiona con el nombre del archivo"
            );
        }
    }

    /**
     * Garantiza la existencia de un archivo de entropía con una longitud mínima
     * de bytes criptográficamente seguros.
     *
     * Si el archivo ya existe y contiene la cantidad requerida de entropía,
     * no se modifica su contenido. En caso contrario, el archivo es creado
     * e inicializado con nuevos bytes aleatorios.
     *
     * Este método asume que la ruta ha sido validada previamente y que el
     * directorio de destino existe y es escribible.
     *
     * @param string $filename Ruta del archivo que almacenará la llave de entropía.
     * @param int    $length   Cantidad mínima de bytes de entropía requerida.
     * @return non-empty-string
     *
     * @throws InvalidPath Si el directorio contenedro del archivo de entropía archivo
     *                     no pueden ser creado o escrito.
     */
    private static function ensure_entropy_file(string $filename, int $length): string {
        /** @var non-empty-string $entropy */
        $entropy = random_bytes($length);

        /** @var int|bool $created */
        $operation_result = false;

        try {
            $operation_result = true;
            return self::require_entropy_bytes($filename, $length);
        } catch (FileNotFoundException $error) {
            if (!\is_dir(dirname($filename))) {
                throw new InvalidPath("No fue posible crear el directorio donde se persistirá la llave de entropía");
            }

            $operation_result = @file_put_contents($filename, $entropy);
        }

        if ($operation_result === false) {
            throw new InvalidPath("No fue posible crear el archivo con la llave de entropía");
        }

        return $entropy;
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