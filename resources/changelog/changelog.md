All notable changes to this project will be documented in this file.  
Todos los cambios importantes de este proyecto serán documentados en este archivo.

This project adheres to [Semantic Versioning](https://semver.org/).  
Este proyecto sigue la convención de [Versionado Semántico](https://semver.org/lang/es/).

---

## [v2.1.0]

### Documentation / Documentación

- Tutorial progresivo de **DLCore** en `docs/tutorial/` (27 capítulos: variables de entorno, ORM, plantillas, autenticación, credenciales cifradas y DLStorage, despliegue, APIs JSON, ORM avanzado, DLRoute y `DLAuth`).
- Aclaración de **`MULTITENANT`**: variable presente pero modo multitenant incompleto; depende de **DLParse** (en desarrollo). Recomendación `MULTITENANT: false` en monoinquilino.

---

## [v2.0.0] - 2026-07-05

### BREAKING CHANGES

- Licencia cambiada de `MIT` a **`AGPL-3.0-or-later`** (modelo dual AGPL + licencias comerciales en el ecosistema DLUnire).

### Changed

- **`Project::run()`**: parámetro opcional `bool $autoload_routes = true` para deshabilitar la carga automática de `routes/` y registrar rutas manualmente (bootstrap personalizado, módulos, pruebas).

---

## [v1.1.4] - 2025-10-29

- Se instalan actualizaciones.

## [v1.1.0] - 2025-05-03

- Integración de **DLStorage** en el ecosistema DLCore (contenedores binarios, `DataStorage`, instalación vía Composer).