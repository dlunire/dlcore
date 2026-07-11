# Changelog / Registro de Cambios

All notable changes to this project will be documented in this file.  
Todos los cambios importantes de este proyecto serán documentados en este archivo.

This project adheres to [Semantic Versioning](https://semver.org/).  
Este proyecto sigue la convención de [Versionado Semántico](https://semver.org/lang/es/).

---

## [Unreleased]

---

## [v2.2.0] - 2026-07-11

### Security / Seguridad

- **Welcome CSP (Content-Security-Policy) + per-request nonce:**
  - HTTP header + meta share the same policy string (`$csp` from `dlcore_welcome_csp()`).
  - Random **`$token`** (`bin2hex(random_bytes(32))`) applied as `nonce` on stylesheets and `welcome.js`.
  - **`script-src` / `style-src` use only the nonce** (no `'self'`), so same-origin JSONP / unexpected scripts are blocked unless they carry that response’s nonce; Google Fonts host remains on `style-src`.
  - Also: `object-src 'none'`, `frame-ancestors 'none'` (header), `base-uri` / `form-action` constrained, `upgrade-insecure-requests`.
- **Isotipo CSP-safe:** inline `<style>` removed from `layouts/icons/isotipo`; rules live in `public/style.css` (`.isotipo`, fills). Prevents CSP from dropping logo sizing and deforming the floating nav bar.
- **CSP en welcome + nonce por petición:**
  - Cabecera HTTP y meta con la misma política (`$csp` vía `dlcore_welcome_csp()`).
  - **`$token`** aleatorio en `nonce` de hojas de estilo y `welcome.js`.
  - **`script-src` / `style-src` solo con nonce** (sin `'self'`): un script del mismo origen sin el nonce de esa respuesta no se ejecuta; host de Google Fonts en `style-src`.
  - Además: `object-src 'none'`, `frame-ancestors 'none'` (cabecera), `base-uri` / `form-action` acotados, `upgrade-insecure-requests`.
- **Isotipo compatible con CSP:** eliminado el `<style>` inline del partial; estilos en `public/style.css`. Evita que la CSP deje el SVG a tamaño real y deforme la barra.

### Dependencies / Dependencias

- **`dlunire/dlstorage` `^0.1.3` → `^0.2.2`** (`composer.json` + `composer.lock`).
  - Aligns with ecosystem dual licensing: DLStorage **0.2.x** is **`AGPL-3.0-or-later`** (0.1.x was MIT).
  - No intentional change to `EncryptedCredentials` / `SaveData`.
- **`dlunire/dlstorage` `^0.1.3` → `^0.2.2`**.
  - Alineado al dual licensing: **0.2.x** es **AGPL-3.0-or-later**. Sin cambio intencional de superficie de integración.

### Added / Añadido

- **`Model::all()` / `DLDatabase::all()`** — full fetch without the safety limit (`allow_unlimited`). Documented as dangerous on large tables; prefer `paginate()` for listings.
- **`Model::all()` / `DLDatabase::all()`** — lectura completa sin tope. Documentado como riesgoso en tablas grandes; prefiera `paginate()` para listados.

### Changed / Cambiado

- **ORM `get()` safety cap:** `Model::get()` / `DLDatabase::get()` apply **`DLDatabase::DEFAULT_GET_LIMIT` (1000)** when no explicit `limit()` was set.
- **Tope de seguridad en `get()`:** **`DEFAULT_GET_LIMIT` (1000)** si no hubo `limit()` explícito.
- **`DLDatabase::where()` PHPDoc** — full params, fluent return, example / documentación completa.

- **Welcome UI (rediseño):**
  - Hero `wh` (copy + install panel + pillars), aligned with skeleton.
  - **Arquitectura**, **ecosistema**, **inicio rápido** share `qs-flow`.
  - Floating glass header (`header--float` / `header__bar`); metrics via `getBoundingClientRect` (`--header-height`, `--header-offset`, `--header-scroll-margin`).
  - Footer always dark; light-theme `code` contrast; theme toggle + drawer menu.
  - Kernel/stack focus (no prices, commercial license block, reference or in-page changelog).
  - Version **v2.2.0** / `dlstorage ^0.2.2` / `dlroute ^2.0`.
- **Routing in templates (`Router::to` → `$route`):**
  - Assets: `style.css`, `welcome.js`, favicons.
  - Site paths: e.g. `politica-datos`.
  - Fragments: `$route('/#inicio-rapido')`, `$route('/#top')`, etc.
  - External `https://…` unchanged.
  - `routes/web.php` injects one shared `$route = Router::to(...)` into welcome and docs views.
- **Docs templates** (`docs-licencia`, `docs-politica-datos`): local nav/assets via `$route`; dropped dead `favicon.png` references.
- **Public assets:** `favicon.svg` + `favicon-dark.svg`; removed `favicon.png`.

- **UI de bienvenida (rediseño):** hero `wh`, `qs-flow`, header flotante glass, pie oscuro, tema claro/oscuro, chips v2.2.0.
- **Enrutado en plantillas (`$route`):** assets, rutas de sitio y anclas con `Router::to`; externos sin `$route`; docs con la misma inyección.
- **Assets públicos:** favicons SVG claro/oscuro; sin `favicon.png`.

### Removed / Eliminado

- Unused demo templates/markdown under `resources/`: `home`, `products`, `clients`, `files`, `base`, `layouts/demo`, `layouts/styles`, `test`/`vista`/`welcome.md`, `changelog`. Kept `welcome`, `docs-licencia`, `docs-politica-datos`, `layouts/icons/isotipo`.
- Plantillas/markdown de demo no usados. Se mantienen welcome, docs de licencia/política e isotipo.

### Documentation / Documentación

- Tutorials `03`, `09`, `21`, `23`, `24` and `docs/DLDatabase.md`: `get` / `all` / `paginate`; pagination samples use `get_integer('page')`.
- Tutoriales y `DLDatabase.md` actualizados al comportamiento de `get` / `all` / `paginate`.

---

## [v2.1.2] - 2026-07-10

### Fixed / Corregido

- **`AuthInterface::auth()` signature (PHP 8+ deprecation):** the optional parameter `$options = []` was declared **before** a required `$cookie` parameter. In PHP 8+, that is deprecated (*Optional parameter … declared before required parameter … is implicitly treated as a required parameter*). The interface now matches the implementation in `DLAuth`:
  - **Before:** `auth(DLUser $user, array|DLAuthOptions $options = [], ?DLCookie $cookie): bool`
  - **After:** `auth(DLUser $user, array|DLAuthOptions $options = [], ?DLCookie $cookie = null): bool`
  - File: `src/Interfaces/AuthInterface.php`. No call-site changes required; `DLAuth` already used `= null`.

- **Firma de `AuthInterface::auth()` (deprecación PHP 8+):** el parámetro opcional `$options = []` se declaraba **antes** de un `$cookie` obligatorio. En PHP 8+ eso emite *Deprecated: Optional parameter … declared before required parameter…*. La interfaz queda alineada con `DLAuth`:
  - **Antes:** `auth(DLUser $user, array|DLAuthOptions $options = [], ?DLCookie $cookie): bool`
  - **Después:** `auth(DLUser $user, array|DLAuthOptions $options = [], ?DLCookie $cookie = null): bool`
  - Archivo: `src/Interfaces/AuthInterface.php`. No requiere cambios en llamadas; `DLAuth` ya usaba `= null`.

---

## [v2.1.1] - 2026-07-10

### Dependencies / Dependencias

- **`dlunire/dlroute` updated to `v2.0.2`** (constraint remains `^2.0` in `composer.json`; `composer.lock` pins the resolved release).
  - Upstream fix: removal of the accidental absolute symlink `public/subdirectorio` (local Linux path under `/srv/Aplicaciones/...`) that broke **Composer install/extract on Windows** (`7z`: *Dangerous link path was ignored*).
  - That link was only a local document-root / environment probe and was never part of the DLRoute public API.
  - No DLCore API changes are required; applications that depend on DLCore continue to pull a Windows-safe DLRoute when they update the lockfile.

- **`dlunire/dlroute` actualizado a `v2.0.2`** (la restricción en `composer.json` sigue siendo `^2.0`; `composer.lock` fija la versión resuelta).
  - Corrección en el paquete: se eliminó el enlace simbólico absoluto accidental `public/subdirectorio` (ruta local Linux bajo `/srv/Aplicaciones/...`) que rompía la **instalación/extracción de Composer en Windows** (`7z`: *Dangerous link path was ignored*).
  - Ese enlace solo servía para pruebas locales de document root / entorno y no formaba parte de la API pública de DLRoute.
  - No hay cambios de API en DLCore; las aplicaciones que dependen de DLCore obtienen un DLRoute seguro en Windows al actualizar el lockfile.

---

## [v2.1.0]

### Removed / Eliminado

- **`enshrined/svg-sanitize` dependency**: removed from `composer.json`. DLCore never referenced or called this package. SVG sanitization for uploads is handled by **DLRoute** (`DLUpload::sanitize_svg()`), which cleans SVG content on the server when a file is received (scripts, inline events, unsafe attributes). A separate sanitizer in DLCore is therefore redundant for the standard DLUnire upload flow.

- **Dependencia `enshrined/svg-sanitize`**: eliminada de `composer.json`. DLCore nunca la referenció ni la invocó. El saneamiento de SVG en subidas lo realiza **DLRoute** (`DLUpload::sanitize_svg()`), que depura el contenido en el servidor al recibir el archivo (scripts, eventos inline, atributos inseguros). Un sanitizador adicional en DLCore resulta redundante en el flujo estándar de carga de archivos de DLUnire.

### Documentation / Documentación

- Added a progressive **DLCore usage tutorial** under `docs/tutorial/` (quick start, environment variables, ORM, controllers, templates, authentication, email, Markdown/JSON, SQL query builder, bootstrap & operations, exceptions & testing, file uploads, encrypted credentials & DLStorage, view compilation cache, `DLTime`, advanced logging, advanced `Path`, PDF via `view_pdf`, `BaseURL`, `Credentials` & `Environment`, skeleton helpers & advanced ORM, production deployment, `DL_TOKEN`/CORS & ORM in APIs, ORM aggregations, advanced writes & transactions, advanced DLRoute, `DLAuth` & route protection).

- Se añadió un **tutorial de uso de DLCore** progresivo en `docs/tutorial/` (inicio rápido, variables de entorno, ORM, controladores, plantillas, autenticación, correo, Markdown/JSON, constructor SQL `DLDatabase`, bootstrap y operación, excepciones y pruebas, subida de archivos, credenciales cifradas y DLStorage, caché de compilación de vistas, tiempo con `DLTime`, logs avanzados, `Path` avanzado, PDF con `view_pdf`, validación de URLs con `BaseURL`, `Credentials` y `Environment` avanzado, helpers del skeleton y ORM avanzado con vistas virtuales y `paginate()`, despliegue en producción con Apache/Nginx, CORS y checklist operativo, `DL_TOKEN`/CORS y uso práctico del ORM en APIs JSON, agregaciones y ORM avanzado con `GROUP BY`, vistas virtuales para reportes y `query()`, escritura avanzada con inserción masiva, `replace`, transacciones manuales y patrones multi-tabla, rutas avanzadas de DLRoute con `filter_by_type`, `match`, `RouteHandler` y MIME, `DLAuth` y protección de rutas con `restrict_route`, `SystemCredentials` y APIs JSON protegidas), con enlaces a la documentación de **DLRoute** (incl. subida de archivos y saneamiento SVG).

---

## [v2.0.0] - 2026-07-05

### BREAKING CHANGES

- **License change**: the package license changed from `MIT` to **`AGPL-3.0-or-later`**, as part of the adoption of a dual licensing model across the whole DLUnire ecosystem (open source under AGPL-3.0 + commercial licenses for use in closed-source products). Use in proprietary products — including network access (SaaS) — without publishing the corresponding source code now requires a commercial license. See `LICENSE` and `LICENSING.md` in the main repository (`dlunire/dlunire`) for full details. The `license` field in `composer.json` was updated accordingly.

- **Cambio de licencia**: se cambió la licencia del paquete de `MIT` a **`AGPL-3.0-or-later`**, como parte de la adopción de un modelo de licenciamiento dual para todo el ecosistema DLUnire (código abierto vía AGPL-3.0 + licencias comerciales para uso en productos de código cerrado). El uso en productos propietarios —incluido el acceso por red (SaaS)— sin publicar el código fuente correspondiente ahora requiere una licencia comercial. Ver `LICENSE` y `LICENSING.md` en el repositorio principal (`dlunire/dlunire`) para el detalle completo. El campo `license` en `composer.json` fue actualizado en consecuencia.

### Changed

- **Bootstrap**: `Project::run()` now accepts an optional `bool $autoload_routes = true` parameter, allowing applications to disable the automatic loading of the `routes/` directory and perform route registration manually when building custom bootstrapping processes, modular applications or testing environments.

- **Bootstrap**: `Project::run()` ahora acepta un parámetro opcional `bool $autoload_routes = true`, permitiendo deshabilitar la carga automática del directorio `routes/` y registrar las rutas manualmente durante procesos de inicialización personalizados, aplicaciones modulares o entornos de pruebas.

---

## [v1.1.4] - 2025-10-29

- Se instalan actualizaciones

## [v1.1.3] - 2025-10-29

- Se instalan actualizaciones

## [v1.1.2] - 2025-10-29

- Se instalan actualizaciones

## [1.1.0] - 2025-05-03

### Added / Añadido

* Se integró la biblioteca `DLStorage` al ecosistema `DLCore`.
  `DLStorage` es una librería para almacenamiento eficiente de datos binarios, diseñada para funcionar de forma independiente o integrada con el framework `DLUnire`.

* Soporte para almacenamiento y recuperación de archivos binarios mediante clases como `DataStorage`.
  La biblioteca incluye validaciones, manejo de excepciones (`StorageException`), y una estructura modular extensible.

* Se agregó instalación vía Composer:

  ```bash
  composer require dlunire/dlstorage  
  ```

---

## [1.0.0] - 2025-04-08

### Added / Añadido

- Initial stable release of `DLRoute`.  
  Versión estable inicial de `DLRoute`.

- Routing system with support for HTTP methods: `GET`, `POST`, `PUT`, `PATCH`, `DELETE`.  
  Sistema de enrutamiento con soporte para métodos HTTP: `GET`, `POST`, `PUT`, `PATCH`, `DELETE`.

- Route definitions using callbacks, arrays, or controller references.  
  Definición de rutas usando callbacks, arrays o referencias a controladores.

- Parameterized routes with type filtering (`integer`, `string`, `boolean`, `email`, etc.).  
  Rutas parametrizadas con filtrado por tipo (`integer`, `string`, `boolean`, `email`, etc.).

- Support for regular expression filters on route parameters.  
  Soporte para filtros con expresiones regulares en parámetros de rutas.

- JSON request body support (application/json).  
  Soporte para cuerpo de solicitudes JSON (`application/json`).

- Basic controller structure included.  
  Estructura básica de controladores incluida.

- Composer autoload with `psr-4`.  
  Autocarga de clases con `psr-4` mediante Composer.

- Integration-ready for the `DLUnire` framework.  
  Listo para integrarse con el framework `DLUnire`.

---

## Upcoming / Próximamente

### Planned / Planeado

- Named routes support.  
  Soporte para rutas con nombre.

- Middleware integration.  
  Integración de middlewares.

- Route groups with prefix and middleware stacking.  
  Agrupación de rutas con prefijo y pila de middlewares.

- Route caching.  
  Cacheo de rutas.

- CLI generator for controllers and routes.  
  Generador CLI para controladores y rutas.
