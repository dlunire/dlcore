# 01 — Inicio rápido

DLCore es el **kernel** de DLUnire: ORM, plantillas, autenticación, correo y lectura tipada de variables de entorno. No arranca el servidor ni define rutas por sí solo; delega el contexto de ejecución a **DLRoute**.

## Relación con DLRoute

| Capa | Paquete | Responsabilidad |
|------|---------|-----------------|
| Infraestructura | `dlunire/dlroute` | Rutas HTTP, peticiones, respuestas, **subida de archivos** (incl. saneamiento SVG) |
| Kernel | `dlunire/dlcore` | ORM, plantillas, auth, correo, `.env.type` |

Documentación DLRoute:

- [README](https://github.com/dlunire/dlroute/blob/master/README.md)
- [Router (ES)](https://github.com/dlunire/dlroute/blob/master/documentation/Router/Router-ES.md)
- [Subida de archivos / DLUpload](https://github.com/dlunire/dlroute/blob/master/documentation/Request/DLUpload-ES.md)
- Tutorial avanzado en el ecosistema DLCore: [26-dlroute-avanzado.md](26-dlroute-avanzado.md)

## Arquitectura mínima

```
public/index.php
    └── Boot\Project::run()     ← DLCore
            ├── autorizaciones / dominios
            ├── helpers y constantes
            ├── routes/           ← opcional (autoload)
            └── DLRoute           ← enrutamiento HTTP
```

## Punto de entrada típico

```php
<?php
use Boot\Project;

require dirname(__DIR__) . '/vendor/autoload.php';

Project::run();
```

Equivalente con namespace completo:

```php
<?php
use DLCore\Boot\Project;

require dirname(__DIR__) . '/vendor/autoload.php';

Project::run();
```

## Bootstrap sin autoload de rutas

Útil en tests, apps modulares o cuando registras rutas a mano:

```php
Project::run(autoload_routes: false);

// Registrar rutas manualmente antes o después,
// según tu arquitectura, y luego invocar el router.
```

## Estructura de carpetas recomendada

| Ruta | Rol |
|------|-----|
| `app/Models/` | Modelos que extienden `DLCore\Database\Model` |
| `app/Controllers/` | Controladores que extienden `DLCore\Core\BaseController` |
| `resources/` | Plantillas `*.template.html` y markdown `*.md` |
| `routes/` | Definición de rutas HTTP (cargada por defecto) |
| `.env.type` | Variables de entorno con tipos estáticos |

## Primer controlador

```php
<?php
namespace DLUnire\Controllers;

use DLCore\Core\BaseController;

final class HomeController extends BaseController {
    public function index(): array {
        return [
            'message' => 'DLCore operativo',
        ];
    }
}
```

DLRoute serializa el `array` de retorno según el contexto (JSON, vista, etc.) configurado en la ruta.

## Más adelante

Bootstrap avanzado, CORS, logs y checklist de producción en [10-bootstrap-operacion.md](10-bootstrap-operacion.md).

## Siguiente paso

Configura las variables de entorno tipadas en [02-variables-entorno.md](02-variables-entorno.md).