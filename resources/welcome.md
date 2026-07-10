### Requisitos del kernel

- PHP **8.2+**
- Composer
- **DLRoute** ^2.0 (dependencia obligatoria — contexto de ejecución)
- **DLStorage** ^0.1.3 (credenciales y contenedores binarios)

### Documentación disponible

| Recurso | Enlace |
|---------|--------|
| **Tutorial DLCore** (27 capítulos) | [docs/tutorial/README.md](https://github.com/dlunire/dlcore/blob/master/docs/tutorial/README.md) |
| Tutorial DLRoute (16 capítulos) | [dlroute/docs/tutorial/](https://github.com/dlunire/dlroute/blob/master/docs/tutorial/README.md) |
| Tutorial DLStorage (11 capítulos) | [dlstorage/docs/tutorial/](https://github.com/dlunire/dlstorage/blob/master/docs/tutorial/README.md) |
| Referencia API (phpDocumentor) | generar con `phpdoc.xml` → `docs/.build/` en el repositorio |
| Sitio oficial DLUnire | [dlunire.dev](https://dlunire.dev) |

El tutorial del kernel abarca bootstrap (`Project::run()`), ORM, plantillas, autenticación, integración con DLRoute, credenciales cifradas, despliegue y APIs JSON.

### Licencia

Desde v2.0.0 el paquete se distribuye bajo **AGPL-3.0-or-later**. El uso en productos de código cerrado o SaaS sin publicar el código fuente correspondiente requiere licencia comercial. Detalle en el repositorio principal `dlunire/dlunire` (`LICENSE`, `LICENSING.md`).