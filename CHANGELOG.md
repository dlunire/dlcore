# Changelog / Registro de Cambios

All notable changes to this project will be documented in this file.  
Todos los cambios importantes de este proyecto serán documentados en este archivo.

This project adheres to [Semantic Versioning](https://semver.org/).  
Este proyecto sigue la convención de [Versionado Semántico](https://semver.org/lang/es/).

---

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
