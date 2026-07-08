**DLCore** es el núcleo del framework [DLUnire](https://dlunire.dev): ORM, motor de plantillas, autenticación, correo, variables de entorno tipadas (`.env.type`) y bootstrap de aplicaciones PHP modernas. Se apoya en [**DLRoute**](https://github.com/dlunire/dlroute) ^2.0 (rutas, peticiones HTTP, subida de archivos) y [**DLStorage**](https://github.com/dlunire/dlstorage) ^0.1.3 (contenedores binarios y credenciales cifradas).

### Requisitos

- PHP **8.2+**
- Composer

### Documentación disponible

| Recurso | Enlace |
|---------|--------|
| **Tutorial DLCore** (27 capítulos) | [docs/tutorial/README.md](https://github.com/dlunire/dlcore/blob/main/docs/tutorial/README.md) |
| Tutorial DLRoute (16 capítulos) | [dlroute/docs/tutorial/](https://github.com/dlunire/dlroute/blob/master/docs/tutorial/README.md) |
| Tutorial DLStorage (11 capítulos) | [dlstorage/docs/tutorial/](https://github.com/dlunire/dlstorage/blob/master/docs/tutorial/README.md) |
| Referencia API (phpDocumentor) | generar con `phpdoc.xml` → `docs/.build/` en el repositorio |
| Sitio oficial | [dlunire.dev](https://dlunire.dev) |

El tutorial de DLCore cubre desde el inicio rápido hasta despliegue en producción, credenciales cifradas, ORM avanzado, integración con DLRoute y protección de rutas con `DLAuth`.

### Licencia

Desde v2.0.0 el paquete se distribuye bajo **AGPL-3.0-or-later**. El uso en productos de código cerrado o SaaS sin publicar el código fuente correspondiente requiere licencia comercial. Detalle en el repositorio principal `dlunire/dlunire` (`LICENSE`, `LICENSING.md`).

### `MULTITENANT` — en desarrollo

La variable `MULTITENANT` en `.env.type` apunta a un modo SaaS (una base de datos por dominio), pero **aún no está terminada**. Hoy la entropía de credenciales ya incorpora el host normalizado; el aislamiento multitenant completo depende de **DLParse**, que sigue en desarrollo.

En despliegues **monoinquilino**, usa `MULTITENANT: boolean = false`. Más contexto en el capítulo 13 del tutorial: [Credenciales cifradas y DLStorage](https://github.com/dlunire/dlcore/blob/main/docs/tutorial/13-credenciales-cifradas.md#multitenant--estado-actual).