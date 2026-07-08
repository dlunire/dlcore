# Documentación DLCore

## Tutorial de uso (recomendado)

Guía progresiva en español: [tutorial/README.md](tutorial/README.md)

**Capa de infraestructura:** DLCore depende de [DLRoute](https://github.com/dlunire/dlroute) (rutas, peticiones, [subida de archivos](https://github.com/dlunire/dlroute/blob/master/documentation/Request/DLUpload-ES.md)).

| Capítulo | Tema |
|----------|------|
| 1 | [Inicio rápido](tutorial/01-inicio-rapido.md) |
| 2 | [Variables de entorno](tutorial/02-variables-entorno.md) |
| 3 | [Modelos ORM](tutorial/03-modelos-orm.md) |
| 4 | [Controladores](tutorial/04-controladores.md) |
| 5 | [Plantillas](tutorial/05-plantillas.md) |
| 6 | [Autenticación](tutorial/06-autenticacion.md) |
| 7 | [Envío de correo](tutorial/07-correo.md) |
| 8 | [Markdown, JSON y vistas compuestas](tutorial/08-markdown-json.md) |
| 9 | [Consultas SQL (`DLDatabase`)](tutorial/09-consultas-sql.md) |
| 10 | [Bootstrap y operación](tutorial/10-bootstrap-operacion.md) |
| 11 | [Excepciones y pruebas](tutorial/11-excepciones-pruebas.md) |
| 12 | [Subida de archivos](tutorial/12-subida-archivos.md) |
| 13 | [Credenciales cifradas y DLStorage](tutorial/13-credenciales-cifradas.md) |
| 14 | [Caché de vistas](tutorial/14-cache-vistas.md) |
| 15 | [Tiempo (`DLTime`)](tutorial/15-dltime.md) |
| 16 | [Logs avanzados](tutorial/16-logs-avanzados.md) |
| 17 | [`Path` avanzado](tutorial/17-path-avanzado.md) |
| 18 | [PDF (`view_pdf`)](tutorial/18-view-pdf.md) |
| 19 | [URLs (`BaseURL`)](tutorial/19-baseurl.md) |
| 20 | [`Credentials` y `Environment`](tutorial/20-credentials-environment.md) |
| 21 | [Helpers del skeleton y ORM avanzado](tutorial/21-helpers-skeleton.md) |
| 22 | [Despliegue en producción](tutorial/22-despliegue-produccion.md) |
| 23 | [`DL_TOKEN`, CORS y ORM en APIs](tutorial/23-cors-dl-token-orm.md) |
| 24 | [Agregaciones y ORM avanzado](tutorial/24-orm-agregaciones.md) |
| 25 | [Escritura avanzada y transacciones](tutorial/25-orm-escritura-transacciones.md) |
| 26 | [Rutas avanzadas de DLRoute](tutorial/26-dlroute-avanzado.md) |
| 27 | [DLAuth y protección de rutas](tutorial/27-dlauth-rutas.md) |

---

# Sintaxis de las plantillas

## Directivas

Ya disponibles las siguientes directivas en nuestro proyecto:

La directiva `@base` permite tener una vista como principal, aquella que podríamos utilizar en todo el proyecto.:

```blade
@base('nombre-de-la-vista')
```

Es el equivalente a la directiva `@extends` de Laravel.

La directiva `@section` y `@endsection` permite crear contenido que puede ser recuparada más tarde en la vista principal, que se invoca desde la directiva `@base`:

```blade
@section('nombre_de_la_seccion') y @endsection
```

La directiva `@print` permite recuperar el contenido definido en una sección creada con la directiva `@section('seccion')`:

```blade
@print('nombre_de_la_seccion')
```

También, la herramienta cuenta con dos tipos de sintaxis:

- `{{ $variable }}`: Muestra en pantalla la información contenida en la variable $variable con escapado de entidades HTML.

- `{!! $variable !!}`: Exactamente lo mismo que en el caso anterior, pero con la diferencia de que el código HTML se escapa, se interpreta.

### Otras directivas

- `@foreach()` y `@endforeach`: Estas directivas permiten iterar un array.

- `@for ()` y `@endfor`: Estas directiva permite iterar una cantidad de veces determinada en función de lo que ha definido el usuario programador.

- `@if @endif`, `@if@else @endif`, `@if @elseif @else @endif`: Estas directivas permiten definir estructuras condicionales.

- `@php` y `@endphp`: Esta directiva permite indicarle a la plantilla que el código fuente es PHP.

- `@json($array)`: Esta directiva permite devolver un array en formato JSON, pero con caracteres escapados y compactado.

- `@json($array, 'pretty')`: Esta directiva permite devolver un array en formato JSON, pero debidamente formateado sin escapar.
