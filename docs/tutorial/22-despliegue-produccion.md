# 22 — Despliegue en producción

Los capítulos anteriores cubren desarrollo local, configuración tipada y operación del framework. Aquí verás cómo **llevar un proyecto DLUnire a un servidor real**: requisitos, `DocumentRoot`, Apache/Nginx, variables de entorno, CORS, permisos y comprobaciones post-deploy.

> Esta guía asume el **skeleton DLUnire** (`composer create-project dlunire/dlunire tu-app`). Si integras solo DLCore, adapta el bootstrap (`boot/Project.php` o `DLCore\Boot\Project`) y el manejador de excepciones según tu proyecto.

## Panorama del despliegue

```
Servidor (Apache o Nginx + PHP-FPM)
    └── DocumentRoot → public/          ← único directorio expuesto
            └── index.php
                    ├── DLExceptionHandler (skeleton)
                    └── Boot\Project::run()
                            ├── Authorizations (CORS + DL_TOKEN)
                            ├── SystemCredentials (sesión)
                            ├── app/Helpers, app/Constants
                            └── routes/ → DLRoute::execute()

Raíz del proyecto (NO expuesta al HTTP)
    ├── .env.type          ← secretos; fuera de git
    ├── vendor/
    ├── app/, routes/, resources/
    ├── logs/              ← escribible por PHP
    └── .build/            ← caché de vistas; escribible
```

El diseño separa `public/` del resto del código. Nunca apuntes el virtual host a la raíz del repositorio: `vendor/`, `.env.type` y `app/` quedarían accesibles.

---

## Requisitos del servidor

| Requisito | Detalle |
|-----------|---------|
| PHP | **8.2+** ([`composer.json` de DLCore](../../composer.json)) |
| Extensiones | `pdo` + driver del motor (`pdo_mysql`, `pdo_pgsql`, `pdo_sqlite`), `mbstring`, `json`, `openssl`, `session` |
| Composer | En el servidor o en CI con artefacto desplegado |
| Servidor web | Apache con `mod_rewrite` **o** Nginx + PHP-FPM |
| Base de datos | MySQL/MariaDB, PostgreSQL o SQLite según `DL_DATABASE_DRIVE` ([09-consultas-sql.md](09-consultas-sql.md)) |

### Desarrollo local vs producción

| Entorno | Comando habitual | Uso |
|---------|------------------|-----|
| Desarrollo | `composer run dev` o `make server` | `php -S localhost:3000 -t public/` |
| Producción | Apache/Nginx | PHP-FPM o `mod_php`; sin servidor embebido |

El `Makefile` del skeleton incluye `make testing` (`vendor/bin/phpunit`) para validar antes del deploy ([11-excepciones-pruebas.md](11-excepciones-pruebas.md)).

---

## Checklist previo al deploy

1. **Copiar** `.env.type.example` → `.env.type` en el servidor (o inyectar secretos vía tu gestor).
2. **Activar producción:**

```envtype
DL_PRODUCTION: boolean = true
```

3. **Credenciales reales** de BD y SMTP ([02-variables-entorno.md](02-variables-entorno.md), [20-credentials-environment.md](20-credentials-environment.md)).
4. **Registrar dominios CORS** en `boot/Project.php` (no basta con `localhost`).
5. **Definir `DL_TOKEN`** si un frontend en otro origen consume la API ([10-bootstrap-operacion.md](10-bootstrap-operacion.md)).
6. **Instalar dependencias** sin dev:

```bash
composer install --no-dev --optimize-autoloader
```

7. **Permisos de escritura** en `logs/` y `.build/` (y rutas que uses con `Path::ensure_dir()`).
8. **Excluir del repositorio** `.env.type`, `logs/`, `.build/`, `vendor/` (`.gitignore` del skeleton ya lo contempla).

---

## Punto de entrada — `public/index.php`

El skeleton registra un manejador global de excepciones antes del bootstrap:

```php
<?php

use Boot\Project;
use Framework\Errors\DLExceptionHandler;

include dirname(__DIR__) . "/vendor/autoload.php";

set_exception_handler([DLExceptionHandler::class, 'handle']);

Project::run();
```

`DLExceptionHandler` consulta `DL_PRODUCTION`:

| Modo | Respuesta al cliente | Log en disco |
|------|----------------------|--------------|
| Producción (`true`) | `{ "status": false, "error": "Error {código}" }` | `/logs/exception.json` con traza completa |
| Desarrollo (`false`) | JSON con `details` y `trace` | Sin log automático |

Detalle de logs de infraestructura en [16-logs-avanzados.md](16-logs-avanzados.md).

---

## Apache

El skeleton incluye `public/.htaccess`:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^([a-zA-Z0-9-\/\.\@]+)?$ index.php [L,QSA]
```

### Virtual host de ejemplo

```apache
<VirtualHost *:443>
    ServerName app.midominio.com
    DocumentRoot /var/www/tu-app/public

    <Directory /var/www/tu-app/public>
        AllowOverride All
        Require all granted
    </Directory>

    # SSL, logs, etc.
</VirtualHost>
```

`AllowOverride All` es necesario para que `.htaccess` reescriba rutas hacia `index.php`. Los archivos estáticos existentes (`public/css/bundle.css`, imágenes) se sirven directamente sin pasar por el front controller.

### `Authorization` en Apache

Si usas `DL_TOKEN` con cabecera `Authorization: Bearer …`, algunos hostings no pasan `HTTP_AUTHORIZATION` a PHP. En ese caso, añade en `.htaccess` o en el virtual host:

```apache
RewriteCond %{HTTP:Authorization} .
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
```

---

## Nginx + PHP-FPM

No hay archivo Nginx en el repositorio; esta es la configuración equivalente al `.htaccess` del skeleton:

```nginx
server {
    listen 443 ssl http2;
    server_name app.midominio.com;

    root /var/www/tu-app/public;
    index index.php;

    # Archivos estáticos
    location ~* \.(css|js|png|jpg|jpeg|gif|svg|ico|woff2?)$ {
        try_files $uri =404;
        expires 7d;
    }

    # Front controller
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # Bloquear acceso a dotfiles (.env, etc.) por si el root estuviera mal configurado
    location ~ /\. {
        deny all;
    }
}
```

Ajusta la ruta del socket (`fastcgi_pass`) y la versión de PHP según tu distribución.

---

## Variables de entorno en el servidor

### Archivo `.env.type`

Debe residir en la **raíz del proyecto** (un nivel por encima de `public/`), no dentro de `public/`. DLCore lo resuelve vía `Path` ([17-path-avanzado.md](17-path-avanzado.md)).

Plantilla mínima de producción:

```envtype
DL_PRODUCTION: boolean = true
DL_LIFETIME: integer = 3800

DL_DATABASE_HOST: string = "127.0.0.1"
DL_DATABASE_PORT: integer = 3306
DL_DATABASE_USER: string = "app_user"
DL_DATABASE_PASSWORD: string = "secreto-fuerte"
DL_DATABASE_NAME: string = "app_db"
DL_DATABASE_CHARSET: string = "utf8mb4"
DL_DATABASE_COLLATION: string = "utf8mb4_unicode_ci"
DL_DATABASE_DRIVE: string = "mysql"
DL_PREFIX: string = "dl_"

MAIL_HOST: string = "smtp.tu-proveedor.com"
MAIL_USERNAME: email = no-reply@midominio.com
MAIL_PASSWORD: string = "smtp-secreto"
MAIL_PORT: integer = 465
MAIL_COMPANY_NAME: string = "Mi Empresa"
MAIL_CONTACT: email = soporte@midominio.com
```

Opcional para API consumida desde otro dominio:

```envtype
DL_TOKEN: string = "token-largo-aleatorio-compartido-con-el-frontend"
```

### Credenciales cifradas (alternativa)

Si usas el flujo `DATABASE: boolean = true` con contenedores `.dlstorage`, la entropía vive en `$HOME/.dlunire/…` del usuario PHP ([13-credenciales-cifradas.md](13-credenciales-cifradas.md)). Asegura que el usuario de FPM (`www-data`, `deploy`, etc.) sea el mismo que ejecutó la instalación inicial o replica la entropía de forma controlada.

---

## CORS y dominios en producción

Por defecto, `boot/Project.php` solo autoriza `localhost`:

```php
Authorizations::register_domain([
    "localhost"
]);
```

Antes del deploy, edita la lista con tus dominios reales (sin protocolo en el array; el matcher acepta `http` y `https` con puerto opcional):

```php
public static function run(): void {
    Authorizations::register_domain([
        'localhost',
        'app.midominio.com',
        'api.midominio.com',
    ]);

    Authorizations::init();
    SystemCredentials::load();
    // ...
}
```

Si el frontend SPA vive en `https://app.midominio.com` y la API en `https://api.midominio.com`, **ambos** deben estar registrados si emiten peticiones cross-origin con `Origin`.

Con `DL_TOKEN` definido, las peticiones cross-origin válidas deben enviar `Authorization: Bearer <DL_TOKEN>`. Sin token configurado, solo aplican las reglas CORS.

---

## Sesión y `SystemCredentials`

El skeleton invoca `SystemCredentials::load()` en cada petición. Efectos relevantes en producción:

| Comportamiento | Detalle |
|----------------|---------|
| Cookie `__auth__` | Atributo `Secure` activo cuando `DL_PRODUCTION` es `true` |
| Validación de origen | Invalida `$_SESSION['auth']` si cambian `User-Agent`, host, puerto, etc. |
| `DL_LIFETIME` | Tiempo de sesión en segundos (`.env.type.example` usa `3800`) |

Requiere HTTPS en producción para que las cookies seguras funcionen correctamente.

---

## Permisos y directorios escribibles

| Ruta | Motivo |
|------|--------|
| `logs/` | `Logs::save()`, `DLExceptionHandler`, errores de BD/SMTP en producción |
| `.build/` | Compilación de plantillas `*.template.html` ([14-cache-vistas.md](14-cache-vistas.md)) |
| Directorios de subida | Si usas `DLUpload`, las rutas destino deben existir y ser escribibles ([12-subida-archivos.md](12-subida-archivos.md)) |

Ejemplo de permisos tras el primer deploy (usuario PHP = `www-data`):

```bash
mkdir -p logs .build
chown -R www-data:www-data logs .build
chmod 755 logs .build
```

`Path::ensure_dir()` crea algunos directorios en el primer uso, pero conviene prepararlos antes para evitar fallos silenciosos por permisos.

---

## Endpoint de salud

Registra una ruta ligera para comprobar que el stack responde tras el deploy. En `routes/health.php`:

```php
<?php

use DLRoute\Requests\DLRoute;

DLRoute::get('/health', function () {
    return [
        'status' => 'ok',
        'time'   => date('c'),
    ];
});
```

Comprobación:

```bash
curl -s https://app.midominio.com/health
# {"status":"ok","time":"2026-07-08T12:00:00+00:00"}
```

Para validar también la base de datos, extiende el callback con un `Products::count()` o `DLDatabase::get_instance()->…` y devuelve `503` si falla la conexión.

---

## Pipeline de despliegue sugerido

```
┌─────────────┐     ┌──────────────┐     ┌─────────────────┐
│ git pull /  │ ──► │ composer     │ ──► │ Permisos logs/  │
│ artefacto   │     │ install      │     │ .build/         │
└─────────────┘     │ --no-dev     │     └────────┬────────┘
                    └──────────────┘              │
                                                  ▼
                    ┌──────────────┐     ┌─────────────────┐
                    │ curl /health │ ◄── │ Pre-calentar    │
                    │ + smoke test │     │ vistas críticas │
                    └──────────────┘     └─────────────────┘
```

### Pre-calentamiento de vistas

La primera petición a cada plantilla compila el HTML en `.build/`. Tras el deploy, visita (o automatiza con `curl`) las rutas que renderizan vistas pesadas para evitar latencia en el primer usuario real ([14-cache-vistas.md](14-cache-vistas.md)).

### Pruebas en CI

Antes de publicar:

```bash
composer test          # DLCore: vendor/bin/phpunit
make testing           # skeleton: vendor/bin/phpunit
```

En el skeleton, crea pruebas bajo `tests/` y valida tipos de `.env.type` como en `DLVarsTest` ([11-excepciones-pruebas.md](11-excepciones-pruebas.md)).

---

## Errores frecuentes

| Síntoma | Causa probable | Solución |
|---------|----------------|----------|
| 404 en todas las rutas | `DocumentRoot` incorrecto o rewrite desactivado | Apuntar a `public/`; habilitar `AllowOverride` / `try_files` |
| CORS bloqueado desde el frontend | Dominio no registrado | Añadir origen en `register_domain()` |
| 403 en API con `Origin` | `DL_TOKEN` no coincide | Sincronizar Bearer entre frontend y `.env.type` |
| 500 sin detalle | `DL_PRODUCTION = true` (esperado) | Revisar `/logs/exception.json`, `database.json` |
| Sesión se pierde al cambiar de HTTP a HTTPS | Cookies `Secure` en producción | Forzar HTTPS en todo el sitio |
| Plantillas lentas tras reinicio | `.build/` vacío o no persistente | Pre-calentar o montar volumen para `.build/` |
| `Authorization` ignorado | Apache no reenvía la cabecera | Regla `HTTP_AUTHORIZATION` (sección Apache) |

---

## Seguridad — resumen

1. **`DocumentRoot` = `public/`** siempre.
2. **`.env.type` fuera de git** y fuera del alcance HTTP.
3. **`DL_PRODUCTION: true`** en servidores accesibles desde Internet.
4. **HTTPS** obligatorio para cookies de sesión seguras del skeleton.
5. **No exponer** `get_password()`, trazas PDO ni detalle SMTP en respuestas JSON ([20-credentials-environment.md](20-credentials-environment.md)).
6. **Rotar** `DL_TOKEN` y contraseñas si hay compromiso; revisar `logs/` periódicamente.

---

## Relación con otros capítulos

| Tema | Capítulo |
|------|----------|
| Bootstrap y CORS | [10-bootstrap-operacion.md](10-bootstrap-operacion.md) |
| `.env.type` y `Credentials` | [02-variables-entorno.md](02-variables-entorno.md), [20-credentials-environment.md](20-credentials-environment.md) |
| Logs en producción | [16-logs-avanzados.md](16-logs-avanzados.md) |
| Caché `.build/` | [14-cache-vistas.md](14-cache-vistas.md) |
| Credenciales cifradas | [13-credenciales-cifradas.md](13-credenciales-cifradas.md) |
| Pruebas PHPUnit | [11-excepciones-pruebas.md](11-excepciones-pruebas.md) |
| Helpers y ORM | [21-helpers-skeleton.md](21-helpers-skeleton.md) |
| `DL_TOKEN`, CORS y ORM en APIs | [23-cors-dl-token-orm.md](23-cors-dl-token-orm.md) |
| Agregaciones y ORM avanzado | [24-orm-agregaciones.md](24-orm-agregaciones.md) |
| Escritura avanzada y transacciones | [25-orm-escritura-transacciones.md](25-orm-escritura-transacciones.md) |

## Siguiente paso

API cross-origin con `DL_TOKEN` y CRUD JSON con el ORM en [23-cors-dl-token-orm.md](23-cors-dl-token-orm.md).