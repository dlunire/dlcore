# Tutorial de uso — DLCore

Guía progresiva para trabajar con el núcleo de DLUnire. Cada capítulo es independiente, pero se recomienda seguir el orden indicado.

| # | Tema | Archivo |
|---|------|---------|
| 1 | Inicio rápido y bootstrap | [01-inicio-rapido.md](01-inicio-rapido.md) |
| 2 | Variables de entorno tipadas (`.env.type`) | [02-variables-entorno.md](02-variables-entorno.md) |
| 3 | Modelos y consultas (ORM) | [03-modelos-orm.md](03-modelos-orm.md) |
| 4 | Controladores y validación de entradas | [04-controladores.md](04-controladores.md) |
| 5 | Plantillas `*.template.html` | [05-plantillas.md](05-plantillas.md) |
| 6 | Autenticación (`DLAuth`, `DLUser`) | [06-autenticacion.md](06-autenticacion.md) |
| 7 | Envío de correo (`SendMail`) | [07-correo.md](07-correo.md) |
| 8 | Markdown, JSON y vistas compuestas | [08-markdown-json.md](08-markdown-json.md) |
| 9 | Consultas SQL con `DLDatabase` | [09-consultas-sql.md](09-consultas-sql.md) |
| 10 | Bootstrap avanzado y operación | [10-bootstrap-operacion.md](10-bootstrap-operacion.md) |
| 11 | Excepciones, validación y pruebas | [11-excepciones-pruebas.md](11-excepciones-pruebas.md) |
| 12 | Subida de archivos y cuerpo en bruto | [12-subida-archivos.md](12-subida-archivos.md) |
| 13 | Credenciales cifradas y DLStorage | [13-credenciales-cifradas.md](13-credenciales-cifradas.md) |
| 14 | Caché de vistas (`.build/`) | [14-cache-vistas.md](14-cache-vistas.md) |
| 15 | Tiempo con `DLTime` | [15-dltime.md](15-dltime.md) |
| 16 | Logs avanzados | [16-logs-avanzados.md](16-logs-avanzados.md) |
| 17 | `Path` avanzado | [17-path-avanzado.md](17-path-avanzado.md) |
| 18 | PDF con `view_pdf` | [18-view-pdf.md](18-view-pdf.md) |
| 19 | Validación de URLs (`BaseURL`) | [19-baseurl.md](19-baseurl.md) |
| 20 | `Credentials` y `Environment` | [20-credentials-environment.md](20-credentials-environment.md) |
| 21 | Helpers del skeleton y ORM avanzado | [21-helpers-skeleton.md](21-helpers-skeleton.md) |
| 22 | Despliegue en producción | [22-despliegue-produccion.md](22-despliegue-produccion.md) |
| 23 | `DL_TOKEN`, CORS y ORM en APIs | [23-cors-dl-token-orm.md](23-cors-dl-token-orm.md) |
| 24 | Agregaciones y ORM avanzado | [24-orm-agregaciones.md](24-orm-agregaciones.md) |
| 25 | Escritura avanzada y transacciones | [25-orm-escritura-transacciones.md](25-orm-escritura-transacciones.md) |
| 26 | Rutas avanzadas de DLRoute | [26-dlroute-avanzado.md](26-dlroute-avanzado.md) |
| 27 | DLAuth y protección de rutas | [27-dlauth-rutas.md](27-dlauth-rutas.md) |

## Convención de nombres

En el ecosistema DLUnire se usa **snake_case** para métodos, funciones, variables y claves de array en código de aplicación (`get_video_hash`, `$user_id`, `'order_id'`). Las **clases** siguen PascalCase (`HomeController`, `ReadFile`), alineado con PSR-4 y el autoload del skeleton (`DLUnire\`).

Los ejemplos del tutorial respetan esta convención. No uses camelCase (`getUserId`, `$orderId`, `showLogin`) en código nuevo del proyecto.

## Requisitos

- PHP **8.2+**
- [`dlunire/dlroute`](https://packagist.org/packages/dlunire/dlroute) **^2.0** — capa de infraestructura obligatoria (rutas, peticiones, subida de archivos)
- Composer

## DLRoute — documentación relacionada

DLCore **no reemplaza** a DLRoute: lo consume. Tutorial progresivo de DLRoute (16 capítulos): [DLRoute tutorial](../../../dlroute/docs/tutorial/README.md) o [en GitHub](https://github.com/dlunire/dlroute/blob/master/docs/tutorial/README.md).

| Tema | Enlace |
|------|--------|
| Tutorial DLRoute (completo) | [dlroute/docs/tutorial/README.md](../../../dlroute/docs/tutorial/README.md) |
| Rutas avanzadas (vista DLCore) | [26-dlroute-avanzado.md](26-dlroute-avanzado.md) |
| Introducción y arquitectura | [DLRoute README](https://github.com/dlunire/dlroute/blob/master/README.md) |
| Router (ES) | [Router-ES.md](https://github.com/dlunire/dlroute/blob/master/documentation/Router/Router-ES.md) |
| Peticiones HTTP (ES) | [Request-ES.md](https://github.com/dlunire/dlroute/blob/master/documentation/Request/Request-ES.md) |
| Subida de archivos y SVG | [DLUpload-ES.md](https://github.com/dlunire/dlroute/blob/master/documentation/Request/DLUpload-ES.md) |
| HTTP Request (v2) | [Request.md](https://github.com/dlunire/dlroute/blob/master/documentation/v2/HTTP/Request.md) |

> En el monorepo local: `Libraries/dlroute/docs/tutorial/…` y `Libraries/dlroute/documentation/…`

## Instalación

```bash
composer require dlunire/dlcore
```

En un proyecto skeleton de DLUnire, DLCore ya viene integrado. En integraciones manuales, asegúrate de que DLRoute resuelva el *document root* antes de inicializar DLCore.

## Documentación de referencia por módulo

- [DLConfig](../DLConfig.md) — credenciales y PDO
- [DLDatabase](../DLDatabase.md) — capa de base de datos
- [DLRequest](../DLRequest.md) — peticiones HTTP
- [Sintaxis de plantillas](../README.md) — directivas del motor de vistas

