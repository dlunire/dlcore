# 13 — Credenciales cifradas y DLStorage

El capítulo [02-variables-entorno.md](02-variables-entorno.md) cubre `.env.type` para configuración tipada. El [10-bootstrap-operacion.md](10-bootstrap-operacion.md) menciona un flujo alternativo: **contenedores binarios `.dlstorage`** donde los secretos no quedan en texto plano en disco. Aquí verás cómo encajan `EntropyValue`, `EncryptedCredentials` y el paquete `dlunire/dlstorage`.

## Cuándo usar cada enfoque

| Enfoque | Ideal para | Dónde vive el secreto |
|---------|------------|------------------------|
| **`.env.type`** | Desarrollo, CI, despliegues con gestor de secretos | Archivo fuera de git en el servidor |
| **`.dlstorage` + entropía** | Instalación guiada, credenciales de BD que el operador configura una vez | Payload ofuscado en el proyecto; llave en `$HOME` del usuario PHP |

La mayoría de proyectos bastan con `.env.type`. El contenedor cifrado entra cuando quieres un **bootloader de instalación** (`DATABASE: boolean = true`) y separar el material de desbloqueo del archivo portable.

## Variables en `.env.type`

DLCore declara estas variables en `dlunire.env.type`:

```dotenv
FILE_PATH: string = "/credentials"
DATABASE: boolean = true
MULTITENANT: boolean = true
```

| Variable | Rol |
|----------|-----|
| `FILE_PATH` | Directorio lógico (no es una ruta del SO) donde se resuelve la entropía y, habitualmente, el contenedor de credenciales |
| `DATABASE` | `true`: la aplicación puede ejecutar un flujo de instalación de credenciales de BD; `false`: no consulta ni persiste credenciales de base de datos |
| `MULTITENANT` | Indica intención SaaS: una base de datos por dominio. La ruta de entropía ya incorpora el host normalizado del servidor |

> Si cambias `FILE_PATH`, el bootloader puede volver a ejecutarse para el nuevo contexto. Al regresar al valor original, se reutiliza lo ya persistido ([comentarios en `dlunire.env.type`](../../dlunire.env.type)).

## Arquitectura: dos piezas separadas

El diseño **no guarda la llave dentro del contenedor**. Hay dos recursos distintos:

```
┌─────────────────────────────────────────────────────────────┐
│  Entropía (llave de desbloqueo)                             │
│  $HOME/.dlunire/{FILE_PATH}{dominio}/{sha256(basename)}     │
│  Gestionada por EntropyValue::get_key_entropy()             │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼ misma entropía al leer/escribir
┌─────────────────────────────────────────────────────────────┐
│  Contenedor .dlstorage (payload ofuscado)                   │
│  {raíz_proyecto}/{FILE_PATH}/database.dlstorage             │
│  Gestionado por EncryptedCredentials → SaveData             │
└─────────────────────────────────────────────────────────────┘
```

- **`EntropyValue`** — crea, lee o regenera la llave en el directorio home del usuario que ejecuta PHP.
- **`EncryptedCredentials`** — fachada que une entropía + persistencia DLStorage (`save_data` / `read_storage_data`).
- **`dlunire/dlstorage`** — formato binario con firma `DLStorage`, versión y payload codificado.

DLCore ya declara la dependencia:

```bash
composer require dlunire/dlcore   # incluye dlunire/dlstorage ^0.1.3
```

## Entropía persistente

El trait `DLCore\Config\EntropyValue` define `get_key_entropy()`. En la práctica lo invocas a través de `EncryptedCredentials` (la fachada que lo incorpora):

```php
use DLCore\Auth\EncryptedCredentials;

$vault = new EncryptedCredentials();
$entropy = $vault->get_key_entropy('file_path');
```

El argumento es el **nombre** de la variable de entorno (`file_path` → `FILE_PATH`). El método:

1. Lee `FILE_PATH` desde `.env.type` (lanza `InvalidPath` si falta o está vacía).
2. Normaliza el host actual con `DLServer::get_host()` y lo concatena a la ruta lógica.
3. Resuelve `$HOME/.dlunire/{FILE_PATH}{dominio}/` vía `Path::build_home_path()`.
4. Crea el subdirectorio si no existe (`Path::ensure_home_subdir()`).
5. Persiste o reutiliza un archivo cuyo nombre es `hash('sha256', basename($ruta))` con **40 bytes** aleatorios (`random_bytes`).

Ejemplo de ruta resultante (Linux):

```text
/home/deploy/.dlunire/credentialsmi-dominio.com/a3f8…{64 hex}
```

La entropía **no es portable por sí sola** entre servidores con distinto `$HOME` o dominio, aunque el `.dlstorage` sí puede copiarse.

## Guardar y leer credenciales

`EncryptedCredentials` extiende `DLStorage\Storage\SaveData` y usa el trait `EntropyValue`:

```php
use DLCore\Auth\EncryptedCredentials;

$vault = new EncryptedCredentials();
$entropy = $vault->get_key_entropy('file_path');

$payload = json_encode([
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'user'     => 'dlunire',
    'password' => 'secreto-de-instalacion',
    'database' => 'dlunire_app',
], JSON_THROW_ON_ERROR);

// storage: false → bajo la raíz del proyecto, no en storage/
$vault->save_data('credentials/database', $payload, $entropy, storage: false);
```

Lectura con la **misma** entropía:

```php
$entropy = $vault->get_key_entropy('file_path');

$raw = $vault->read_storage_data('credentials/database', $entropy, storage: false);
$credentials = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
```

| Parámetro | Significado |
|-----------|-------------|
| `$filename` | Ruta relativa **sin** extensión `.dlstorage` |
| `$entropy` | Llave obtenida con `get_key_entropy()`; debe coincidir con la de escritura |
| `$storage` | `true` (defecto): `{raíz}/storage/…`; `false`: `{raíz}/…` directamente |

Si la entropía no coincide, `read_storage_data()` devuelve basura o falla en la decodificación. Si el archivo no existe, lanza `DLStorage\Errors\StorageException` (código 404).

## Endpoint de instalación

DLCore incluye `InstallCredentialsController`, pensado para el primer arranque cuando `DATABASE` es `true`:

```php
<?php
use DLCore\Controllers\InstallCredentialsController;
use DLRoute\Requests\DLRoute;

DLRoute::get('/install/credentials', [InstallCredentialsController::class, 'install']);
```

Respuesta JSON:

```json
{
    "status": true,
    "entropy": "a1b2c3…"
}
```

El campo `entropy` es la llave en **hexadecimal** (`bin2hex`). Un frontend de instalación puede mostrarla al operador o usarla en el mismo request para crear el contenedor, según el flujo que definas en tu skeleton.

> El controlador **solo devuelve la entropía**; la persistencia del payload (formulario → `save_data`) queda en tu capa de aplicación o bootloader.

## DLStorage de bajo nivel

Para datos que no son credenciales de BD, puedes usar `DLStorage\Storage\Storage` directamente:

```php
use DLStorage\Storage\Storage;

$storage = new Storage(
    filename: 'usuarios',
    entropy: $entropy
);

$storage->generate(json_encode(['id' => 1, 'nombre' => 'Ana']));
$contenido = $storage->readfile();
```

Por defecto escribe en `{raíz}/storage/usuarios.dlstorage`. Consulta la referencia completa en [Storage.md](https://github.com/dlunire/dlstorage/blob/master/doc/Storage.md) (monorepo local: `Libraries/dlstorage/doc/Storage.md`).

Estructura interna del archivo (vía `SaveData`):

```text
[firma 9B][tamaño_cabecera 4B][versión][tamaño_payload 4B][payload codificado]
```

## Seguridad y limitaciones

1. **Separación entropía / payload** — quien obtenga solo el `.dlstorage` no puede leer el contenido sin la llave en `$HOME`. Quien controle ambos, sí.
2. **Ofuscación, no cifrado fuerte** — DLStorage transforma bytes con entropía (MTB). No sustituye a AES-256 ni a un gestor de secretos empresarial. Trátalo como capa de **defensa en profundidad**, no como HSM.
3. **Permisos** — el usuario PHP debe poder escribir en `$HOME/.dlunire/`. Restringe lectura del home en hosts compartidos.
4. **No versionar secretos** — excluye `.dlstorage` con credenciales reales del repositorio; la entropía tampoco debe copiarse a git.
5. **Misma entropía** — documenta el procedimiento de migración si cambias de servidor: copia contenedor **y** archivo de entropía, o reinstala credenciales.
6. **Producción** — combina con `DL_PRODUCTION: boolean = true`, HTTPS y el checklist del capítulo 10.

## Errores habituales

| Síntoma | Causa probable | Excepción |
|---------|----------------|-----------|
| «La ruta de archivo es requerida en «FILE_PATH»» | Variable ausente en `.env.type` | `InvalidPath` |
| «Asegúrese de tener los permisos necesarios…» | `$HOME` no escribible por PHP | `InvalidPath` (403) |
| «El archivo «…» no existe» | Contenedor no creado o ruta/`storage` incorrectos | `StorageException` (404) |
| «no es un archivo DLStorage» | Archivo corrupto o no es `.dlstorage` | `StorageException` (500) |
| Contenido ilegible tras leer | Entropía distinta a la de escritura | Fallo en decodificación |

## Flujo completo de instalación

```
GET /install/credentials
    └── InstallCredentialsController::install()
            ├── EncryptedCredentials::get_key_entropy('file_path')
            └── JSON { status, entropy }

POST /install/credentials   (tu implementación)
    └── Recibe host, user, password, database
            ├── get_key_entropy('file_path')
            ├── save_data('credentials/database', json, $entropy, storage: false)
            └── Opcional: actualizar .env.type o marcar instalación completa

Arranque posterior
    └── get_key_entropy() + read_storage_data()
            └── Construir DSN / PDO con el JSON recuperado
```

## Relación con otros capítulos

| Tema | Capítulo |
|------|----------|
| Variables `.env.type` y `DLConfig` | [02-variables-entorno.md](02-variables-entorno.md) |
| PDO y modelos | [03-modelos-orm.md](03-modelos-orm.md), [09-consultas-sql.md](09-consultas-sql.md) |
| Controladores y validación del formulario de instalación | [04-controladores.md](04-controladores.md) |
| Bootstrap, `composer configure`, checklist producción | [10-bootstrap-operacion.md](10-bootstrap-operacion.md) |
| `Path::build_home_path()` y utilidades de ruta | [10-bootstrap-operacion.md](10-bootstrap-operacion.md) |
| Excepciones `InvalidPath`, `FileNotFoundException` | [11-excepciones-pruebas.md](11-excepciones-pruebas.md) |
| Referencia DLStorage | [Storage.md](https://github.com/dlunire/dlstorage/blob/master/doc/Storage.md) |

## Siguiente paso

Caché de compilación en `.build/`, invalidación SHA-1 y operación en producción en [14-cache-vistas.md](14-cache-vistas.md).