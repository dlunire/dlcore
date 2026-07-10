# Changelog / Registro de Cambios

All notable changes to this project will be documented in this file.  
Todos los cambios importantes de este proyecto serán documentados en este archivo.

This project adheres to [Semantic Versioning](https://semver.org/).  
Este proyecto sigue la convención de [Versionado Semántico](https://semver.org/lang/es/).

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
