# Licencia comercial DLUnire — Alcance claro

Guía pública para entender **cuándo la necesita**, **qué compra** y **qué no compra** al desplegar su aplicación con el ecosistema DLUnire (**DLCore**, **DLRoute**, **DLStorage**, skeleton **dlunire/dlunire**).

**Titular:** DLUnire · David E Luna M · NIT 700551569-1  
**Contacto:** [ventas@dlunire.dev](mailto:ventas@dlunire.dev)

> Este documento es orientativo. El **certificado o contrato firmado** prevalece sobre esta guía.

---

## Puede crear su aplicación con DLUnire

**Sí, siempre.** DLUnire existe para que usted construya su producto: SaaS, API, panel, intranet, proyecto open source o lo que necesite.

Esta guía **no limita** quién puede desarrollar ni qué puede construir. Solo aclara **cómo cumplir la licencia** cuando despliega código cerrado a usuarios externos por red:

| Camino | Qué implica |
|--------|-------------|
| **AGPL (gratis)** | Publica el código de **su aplicación** bajo AGPL. Puede cobrar, operar comercialmente y usar todos los paquetes del ecosistema. |
| **Licencia comercial (de pago)** | Mantiene el código de **su aplicación** cerrado en producción. Compra un **permiso legal** para ese despliegue; no compra el framework ni el derecho a «usar DLUnire». |

La licencia comercial **no es** un producto de software aparte ni un requisito para empezar a programar. Es la alternativa cuando la publicación AGPL de su aplicación no encaja con su modelo de negocio.

---

## En una frase

DLUnire es **AGPL**. Si terceros usan su app por internet y el código de **su aplicación** no será público bajo AGPL, necesita **licencia comercial** o **publicar fuentes**. En cualquier otro caso habitual (desarrollo local, uso interno, open source), **no**.

---

## En 30 segundos

| Pregunta | Respuesta corta |
|----------|-----------------|
| **¿Puedo crear mi app con DLUnire?** | **Sí.** Sin pedir permiso previo. La licencia comercial solo entra al desplegar código cerrado a terceros por red. |
| **¿Qué es la licencia comercial?** | Permiso legal para **un (1) producto** con código cerrado en producción, sin publicar el fuente de **su aplicación** bajo AGPL. |
| **¿Qué NO es?** | No es «comprar DLUnire», no es desarrollo de su app, no es hosting ni soporte ilimitado. |
| **¿Cuánto cuesta?** | Desde **$0** (Fundador) o **$20.000 COP** pago único (Freelance). Producto propio: Indie o Pro. **Todo pago único** — sin suscripciones. Sin IVA. |
| **¿Alternativa gratis?** | Publicar el código de **su aplicación** bajo **AGPL-3.0-or-later**. |
| **¿Puedo ganar dinero sin pagar licencia?** | **Sí.** Cobrar por desarrollo, diseño, hosting o soporte es siempre válido. Lo de pago es solo el permiso de despliegue cerrado. |

---

## No es «software libre o de pago»

Son **dos caminos para cumplir la misma licencia AGPL** del ecosistema. DLUnire **no se compra** para empezar a programar.

| | **Camino AGPL ($0)** | **Camino comercial (de pago)** |
|--|----------------------|--------------------------------|
| **Usar DLUnire** | Gratis | Gratis (mismos paquetes en Packagist) |
| **Desarrollar y cobrar por su trabajo** | **Sí** | **Sí** |
| **Desplegar a usuarios externos por red** | Publicando el código de **su aplicación** bajo AGPL | Manteniendo el código de **su aplicación** cerrado |
| **¿Paga a DLUnire?** | **No** | Sí, por el permiso de ese despliegue cerrado |

**Ganar dinero no es pagar licencia.** Un desarrollador puede facturar al cliente por construir una encuesta, una tienda o un SaaS. La pregunta de licencia comercial aparece solo al **poner en producción** un producto con código cerrado al que acceden terceros por internet.

---

## Para desarrolladores y agencias

Casos habituales al trabajar **para un cliente** o por cuenta propia:

| Caso | ¿Paga licencia comercial a DLUnire? |
|------|-------------------------------------|
| Construir una encuesta o tienda en **local** o **staging** | **No** |
| Cobrar al cliente por **desarrollo**, diseño, hosting o mantenimiento | **No** (cobra servicios; no es licencia del framework) |
| Encuesta en **producción**, repo de la app **público** bajo AGPL | **No** |
| Encuesta en **producción**, repo de la app **privado** (código cerrado) | **Sí** — plan **Freelance** (simbólico), Indie o AGPL |
| Tienda online en **producción**, clientes compran, código **cerrado** | **Sí** — plan **Freelance** (simbólico), Indie o AGPL |
| Tienda online en **producción**, código de la app **público** bajo AGPL | **No** |
| Panel interno solo para empleados del cliente | **No** |

**Mensaje para desarrolladores:**

> Usen DLUnire gratis. Construyan lo que quieran y cobren a sus clientes. Solo si van a dejar el producto **en producción** con código cerrado y usuarios externos lo usan por internet, necesitan licencia comercial **de ese producto** — o publican el código de su app bajo AGPL.

La licencia comercial va ligada al **producto desplegado**, no a «ser desarrollador». Puede asumirla quien despliega (desarrollador o cliente final); en el contrato se identifica un titular y un producto.

---

## Tienda online: ¿tienen que pagar?

**Sí, en el caso habitual** — si la tienda está **en producción**, los compradores acceden por internet y el código de **su aplicación** (la tienda, no DLUnire) permanece **cerrado**.

| Situación | ¿Paga licencia comercial? |
|-----------|---------------------------|
| Construye la tienda en local o staging | **No** |
| Tienda en vivo, usuarios compran, código de la app **cerrado** | **Sí** — **Freelance** desde $50k si es del cliente |
| Tienda en vivo, repo de la app **público** bajo AGPL | **No** |

La alternativa gratuita no es «no usar AGPL»: es **publicar el código de su tienda** bajo AGPL. Si el código debe quedar privado, el plan **Freelance** ofrece un permiso **simbólico** para desarrolladores que entregan el sitio a un cliente.

Esto aplica igual a encuestas públicas, SaaS, APIs comerciales y cualquier app web cerrada a la que acceden terceros por red.

---

## Facilidades para quien empieza

DLUnire no cobra por aprender ni por construir. Estas facilidades son **concretas** y aplican sin pedir permiso previo:

### Siempre gratis (sin trámite)

| Facilidad | Detalle |
|-----------|---------|
| **Instalar y usar el ecosistema** | `composer require`, skeleton, DLCore, DLRoute, DLStorage. Sin registro ni tarjeta. |
| **Desarrollo ilimitado** | Local y staging todo el tiempo que necesite. No hay trial que expire. |
| **Tutorial y documentación** | Capítulos del kernel, guías de integración y ejemplos en la welcome. |
| **Facturar su trabajo** | Cobrar al cliente por diseño, desarrollo, hosting o mantenimiento. Eso es su negocio, no licencia DLUnire. |
| **Probar antes de abrir** | Demo al cliente, pasarela en sandbox, catálogo de prueba, encuesta piloto — sin licencia comercial. |
| **Soporte comunitario** | Issues públicos para dudas generales del ecosistema (no sustituye soporte comercial contratado). |
| **Camino AGPL** | Si publica el código de **su aplicación** bajo AGPL, el despliegue cerrado no aplica: **$0** para siempre. |

### Paga solo al desplegar cerrado (y puede ser simbólico)

| Facilidad | Detalle |
|-----------|---------|
| **El cobro no es al programar** | La licencia comercial entra al poner **en producción** código cerrado con usuarios externos por internet. |
| **Plan Freelance para clientes** | Si trabaja **para terceros**, pago único desde **$20.000** (INICIO) o **$50.000** (proyecto) o **$100.000** (pack 5 clientes). |
| **Indie / Pro para producto propio** | Si el producto es **suyo** (su SaaS, su marca), **$600.000** o **$800.000 COP** pago único. |
| **Certificado en ~3 días hábiles** | Tras confirmar pago, recibe permiso documentado para el producto o proyecto cliente indicado. |
| **Sin IVA en lista** | Precios publicados en COP son total a pagar (RUT código 49). |

### Flujo recomendado para un freelancer

1. Instala DLUnire y construye la tienda o encuesta del cliente (**$0**).
2. Muestra staging al cliente y cobra su desarrollo (**$0** a DLUnire).
3. Al subir a producción con código cerrado, elige:
   - **Freelance Proyecto** — **$50.000 COP** por ese sitio de cliente, o
   - **Freelance Pack** — **$100.000 COP** pago único (hasta 5 clientes), o
   - **AGPL** — publicar el repo de la app del cliente (si el cliente acepta).
4. Sigue facturando mantenimiento al cliente con normalidad.

**En la práctica:** no tiene que elegir licencia el día uno. Puede validar el negocio completo antes de pagar un monto simbólico por el permiso de despliegue.

---

## ¿Necesito licencia comercial?

```
¿Su aplicación usa DLCore / DLRoute / DLStorage?
│
├─ NO → No aplica esta guía.
│
└─ SÍ → Puede desarrollarla con normalidad. La pregunta es solo al desplegar:
    │
    ├─ ¿Solo su equipo la usa por red, sin usuarios externos?
    │     (herramienta interna, intranet)
    │   └─ SÍ → NO necesita licencia comercial (AGPL basta).
    │
    ├─ ¿Va a publicar el código de SU aplicación bajo AGPL?
    │   └─ SÍ → NO necesita licencia comercial.
    │
    ├─ ¿Desarrollo, pruebas o staging sin usuarios externos comerciales?
    │   └─ SÍ → NO necesita licencia comercial.
    │
    └─ ¿Usuarios externos acceden por internet (web, API, SaaS) y el código
        de SU aplicación NO será público bajo AGPL?
        └─ SÍ → SÍ necesita licencia comercial (o publicar fuentes).
```

### Ejemplos concretos

| Caso | ¿Licencia comercial? |
|------|----------------------|
| Crear y desarrollar su SaaS en local o staging | **No** |
| Desarrollador cobra a un cliente por construir una encuesta o tienda | **No** (cobra servicios) |
| Encuesta web en producción, repo privado de la app | **Freelance** desde $50k, Indie o AGPL |
| Tienda online en producción, código cerrado | **Freelance** desde $50k, Indie o AGPL |
| Freelancer con 3 tiendas de clientes distintos | **Freelance Pack** ($100k, 5 cupos) o 3× Proyecto |
| SaaS de facturación en producción, repo privado | **Sí** |
| API REST comercial sin publicar el repositorio de la app | **Sí** |
| App web para clientes, código cerrado | **Sí** |
| Proyecto open source: repo público de la app bajo AGPL | **No** |
| Panel interno solo para empleados de su empresa | **No** |
| Agencia que desarrolla **dos** SaaS distintos con DLUnire | **Dos licencias** (o plan Business) |
| Revender «DLUnire» como framework empaquetado | **No permitido** (consultar Business) |

---

## AGPL gratis vs licencia comercial

| | **AGPL (gratis)** | **Licencia comercial (de pago)** |
|--|-------------------|----------------------------------|
| **Crear y desarrollar su app** | **Sí** | **Sí** |
| **Precio** | $0 | COP según plan (tabla abajo) |
| **Código de su aplicación** | Debe poder ofrecerse bajo AGPL a quienes usan la app por red | Puede permanecer **cerrado** en producción |
| **Uso comercial (cobrar)** | Permitido | Permitido |
| **Paquetes del ecosistema** | Mismos (`dlcore`, `dlroute`, `dlstorage`) | Mismos |
| **Certificado / contrato** | No aplica | Sí, con producto identificado |
| **Soporte incluido** | Comunidad / issues | Horas por **correo** según plan |
| **Actualizaciones del núcleo** | Siempre vía Composer/Packagist | Según vigencia del plan |

La AGPL **no prohíbe** ganar dinero ni crear productos comerciales. Exige **transparencia del código de la aplicación** cuando terceros la usan por red. La licencia comercial es la vía de pago si esa publicación no es viable.

---

## Qué compra exactamente

Al pagar una licencia comercial adquiere un **derecho legal** (no exclusivo, intransferible salvo acuerdo Business) para:

1. **Integrar** DLCore, DLRoute, DLStorage y el skeleton en **un (1) producto** que usted identifica (nombre, dominio o descripción).
2. **Desplegar** ese producto en producción (web, API, SaaS, on‑premise) con el código de **su aplicación** cerrado.
3. **Modificar** el código del ecosistema dentro de su proyecto según necesidad técnica (sin sublicenciar el framework por separado).
4. **Recibir** certificado de licencia, actualizaciones del núcleo y soporte por **correo** dentro de los límites del plan.

**No está comprando** el framework: los paquetes siguen siendo públicos en Packagist. Compra el **permiso de despliegue cerrado** de su producto concreto.

### Qué cuenta como «un (1) producto»

| Cuenta como **un** producto | Necesita **otra** licencia o Business |
|-----------------------------|--------------------------------------|
| Mismo sistema en producción + staging | Segundo SaaS con marca o código base distinto |
| Varios subdominios del mismo sistema (`app.ejemplo.com`, `api.ejemplo.com`) | White‑label: cada cliente final despliega su propia instancia como producto propio |
| Módulo web + API del **mismo** negocio | Segunda línea de negocio con aplicación separada |
| Una empresa, un producto licenciado | Grupo con varias apps comerciales distintas |

En el contrato se documenta: **nombre del producto**, **dominio(s)** y **descripción en una frase**.

### Paquetes cubiertos

- `dlunire/dlcore`
- `dlunire/dlroute`
- `dlunire/dlstorage`
- Skeleton `dlunire/dlunire`

Versiones publicadas en **Packagist** durante el periodo de actualizaciones de su plan.

### Qué NO compra (límites fijos)

La licencia comercial **no incluye**:

| No incluido | Qué significa en la práctica |
|-------------|------------------------------|
| Desarrollo de su producto | Usted (o su equipo) implementa features de negocio |
| Diseño, contenido, marketing | Fuera de alcance |
| Hosting, DevOps, backups | Usted opera servidores |
| Auditorías o certificaciones | No incluidas |
| Soporte 24/7 o teléfono permanente | Solo **correo**, horario laboral orientativo |
| Segundo producto | Requiere otra licencia o plan Business |
| Revender el framework | No puede empaquetar DLUnire y venderlo como producto aparte |
| Roadmap garantizado | No se prometen fechas de features futuras (p. ej. MULTITENANT) |

Servicios extra (horas de desarrollo, capacitación, integración avanzada) se cotizan aparte.

---

## Planes y tarifas

**Todos los planes son pago único en COP.** Sin suscripciones, sin renovaciones obligatorias ni cobros recurrentes.

Precios de lista en **pesos colombianos (COP)**. El USD es **orientativo** (varía con la [TRM](https://www.banrep.gov.co/es/estadisticas/trm)). **Sin IVA** (RUT código 49).

| Plan | Precio COP (pago único) | USD orientativo* | Para quién |
|------|-------------------------|------------------|------------|
| **Freelance INICIO** | **$20.000** | ≈ USD 5 | 1.er proyecto de cliente (promo tras Fundadores) |
| **Freelance Proyecto** | **$50.000** | ≈ USD 13 | **Un sitio de cliente** (tienda, encuesta, web) |
| **Freelance Pack** | **$100.000** | ≈ USD 25 | **Hasta 5 sitios de cliente** (mismo titular) |
| **Indie** | **$600.000** | ≈ USD 150 | **Su propio** producto o SaaS |
| **Pro** | **$800.000** | ≈ USD 200 | **Su propio** producto + más soporte |
| **Business** | Cotización | — | Muchos productos, white‑label, SLA |

\*Ejemplo TRM ≈ $4.000.

**En todos los planes de pago:** uso del producto licenciado **perpetuo** en la última versión recibida; **12 meses** de actualizaciones del núcleo desde la compra; soporte por correo según cupo del plan (una sola vez, no recurrente).

### Plan Freelance (desarrolladores que trabajan para clientes)

Pensado para que **no pierda oportunidades**: usted factura al cliente; DLUnire cobra un **pago único simbólico** por el despliegue cerrado.

| | **INICIO** | **Proyecto** | **Pack** |
|--|------------|--------------|----------|
| **Precio (pago único)** | **$20.000 COP** | **$50.000 COP** | **$100.000 COP** |
| **Alcance** | 1 proyecto de cliente | 1 proyecto de cliente | **Hasta 5** proyectos de cliente |
| **Actualizaciones** | 12 meses desde la compra | 12 meses | 12 meses |
| **Soporte (correo)** | 1 h | 1 h | 2 h (total del pack) |
| **Ideal si** | Primer proyecto pagado | Un encargo puntual | Varios clientes sin pagar 5×50k |

**Qué cuenta como «proyecto de cliente»:** sitio en producción para el negocio de un **tercero** (el cliente final). Ej.: `tienda.cliente.com`, encuesta del evento del cliente, web corporativa con formularios.

**Qué NO cubre Freelance:**

- Su **propio** SaaS, marketplace o producto con su marca → plan **Indie** o **Pro**.
- Más de **5** clientes con Pack → proyecto extra (**$50.000 COP** c/u) o **Business**.
- Revender una plantilla white‑label a muchos clientes que cada uno opera como producto propio → **Business**.

Cada proyecto se registra en el certificado: **nombre del cliente**, **dominio** y **descripción en una frase**.

### Comparación Indie vs Pro (pago único)

| | **Indie** | **Pro** |
|--|-----------|---------|
| **Precio** | **$600.000 COP** pago único | **$800.000 COP** pago único |
| **Derecho de uso** | **Perpetuo** para el producto indicado | **Perpetuo** |
| **Actualizaciones del núcleo** | **12 meses** desde la compra | **12 meses** desde la compra |
| **Soporte por correo** | **4 h** (cupo total) | **8 h** (cupo total) |
| **Ideal si** | Su SaaS o producto propio, presupuesto ajustado | Mismo, con más horas de soporte incluidas |

### Business

Para organizaciones con **varios productos**, SLA o bolsa de integración. Contactar con listado de aplicaciones y necesidades.

---

## Soporte: qué sí y qué no

El soporte incluido es **solo por correo** (respuesta orientativa **2–3 días hábiles**). No hay videollamadas ni soporte telefónico incluido en los planes comerciales.

**Sí cubre:**

- Dudas de licenciamiento (AGPL vs comercial, alcance de su plan).
- Instalación: `composer`, `.env.type`, `Project::run()`.
- Integración **básica**: rutas, un modelo, una vista, despliegue PHP habitual.
- Orientación para actualizar versiones **menores** del núcleo dentro del periodo de actualizaciones.

**No cubre:**

- Desarrollar módulos de negocio completos.
- Depuración ilimitada de su código de aplicación.
- Administración de servidores o incidentes en producción a demanda.
- Consultoría legal (derivar a su abogado).

Cada consulta por correo cuenta mínimo **15 minutos** del cupo del plan.

---

## Después del pago único (12 meses de actualizaciones)

En **todos** los planes comerciales:

- **Puede** seguir operando en producción con la **última versión** del núcleo recibida durante los 12 meses de actualizaciones.
- **No recibe** versiones nuevas del núcleo pasados esos 12 meses (salvo nueva compra o acuerdo).
- El **soporte incluido** se consume del cupo total del plan; no se renueva solo.
- **No puede** ampliar a otro producto o cliente extra sin nueva licencia del plan que corresponda.

No hay suscripción ni cargo automático. Si quiere más actualizaciones o soporte después, consulte en ventas.

---

## Programa Fundadores — 20 cupos gratis

**Código: `DLUNIRE-FUNDADOR`** · **$0** · **Freelance Proyecto** (1 sitio de cliente)

| Detalle | Regla |
|---------|-------|
| **Cupos** | **20** certificados gratuitos (programa de lanzamiento 2026) |
| **Qué cubre** | Permiso de despliegue cerrado para **un (1) proyecto de cliente** (tienda, encuesta, web) |
| **Quién** | Desarrollador o agencia; **un cupo por correo** asociado al dominio |
| **Cómo solicitar** | Correo o WhatsApp con datos del proyecto; mencione `DLUNIRE-FUNDADOR` |
| **Pago** | **Ninguno** — certificado directo tras validación |
| **Después del cupo 20** | Aplica **DLUNIRE-INICIO** ($20.000) o tarifa de lista ($50.000) |

**Datos obligatorios:** titular (nombre o razón social), **correo asociado al dominio**, nombre del **cliente final**, **dominio** en producción, descripción en una frase. **No** pedimos documento de identidad ni NIT del titular para Freelance. Al enviar datos acepta la [política de tratamiento de datos personales](/politica-datos) (Ley 1581 de 2012, Colombia).

Plazo del certificado: orientativo **≤ 3 días hábiles** (suele ser el mismo día).

---

## Promoción de entrada — Freelance (tras Fundadores)

**Código: `DLUNIRE-INICIO`** (lanzamiento 2026) — **mínimo de entrada**

Como referencia de precio simbólico al primer despliegue (modelo similar a promos de entrada de proveedores cloud): el primer permiso cuesta lo mínimo; los siguientes, tarifa de lista.

| Beneficio | Detalle |
|-----------|---------|
| **Primer Freelance Proyecto** | **$20.000 COP** (mínimo) en lugar de $50.000 |
| **Quién aplica** | Desarrollador o agencia con su **primer** sitio de cliente en DLUnire (un uso por titular) |
| **Cómo activarla** | Mencione `DLUNIRE-INICIO` al solicitar por correo o WhatsApp |
| **Siguientes proyectos** | $50.000 COP c/u (o Pack $100.000 pago único por 5 clientes) |

Válida hasta agotar cupo de lanzamiento o fin de **2026**, lo que ocurra primero. DLUnire confirma si aplica al responder su solicitud.

---

## Solicitar y pagar — plan Freelance

### Paso 1 — Escriba con estos datos

Envíe un correo a **[ventas@dlunire.dev](mailto:ventas@dlunire.dev?subject=Licencia%20Freelance%20DLUnire&body=Plan%3A%20%5BProyecto%20%2F%20Pack%5D%0ATitular%20(qui%C3%A9n%20contrata)%3A%20%5Bnombre%20o%20raz%C3%B3n%20social%5D%0ADominio%20en%20producci%C3%B3n%3A%20%5Bej.%20tienda.cliente.com%5D%0ACorreo%20asociado%20al%20dominio%3A%20%5Bej.%20contacto%40cliente.com%5D%0ACliente%20final%3A%20%5Bnombre%20del%20negocio%20del%20cliente%5D%0ADescripci%C3%B3n%20(en%20una%20frase)%3A%20%5Bej.%20tienda%20de%20ropa%20online%5D%0A%0AAcepto%20pol%C3%ADtica%20de%20datos%20(Ley%201581)%3A%20S%C3%AD%0AC%C3%B3digo%20promo%3A%20DLUNIRE-INICIO%0APreferencia%20de%20pago%3A%20%5BWompi%20%2F%20ePayco%20%2F%20Bancolombia%5D)** o WhatsApp **+57 302 648 8528** con:

| Campo | Freelance Proyecto / INICIO | Freelance Pack |
|-------|----------------------------|----------------|
| Plan | `Freelance Proyecto` o `INICIO` | `Freelance Pack` |
| Titular (quien paga) | Su nombre o razón social | Su nombre o agencia |
| Dominio en producción | `tienda.cliente.com` | Por cada cliente |
| Correo asociado al dominio | `contacto@cliente.com` o del mismo sitio | Por cada cliente |
| Cliente final | Nombre del negocio del cliente | Lista de hasta 5 (o se registran al entregar) |
| Aceptación política de datos | Sí (Ley 1581) | Sí |
| Descripción en una frase | «Tienda de calzado online» | «Agencia — pack 5 clientes» |
| Código promo | `DLUNIRE-FUNDADOR` (si quedan cupos) o `DLUNIRE-INICIO` | Según cupo |

No necesita licencia previa para escribir. Puede solicitar **el día que va a desplegar** o unos días antes.

### Paso 2 — Confirmación y monto

DLUnire responde en **1–3 días hábiles** (a menudo el mismo día) con:

- Confirmación de que el caso encaja en **Freelance** (sitio de **cliente**, no su propio SaaS).
- Si queda cupo **Fundador**: **$0** y certificado sin pasarela.
- Si no: monto en **COP** (sin IVA) y **link Wompi** u otra pasarela.

### Paso 3 — Pague (si aplica)

**Precio de lista**

| Modalidad | COP | Fundador (cupos 1–20) | Con `DLUNIRE-INICIO` |
|-----------|-----|------------------------|----------------------|
| Freelance Proyecto | $50.000 | **$0** | **$20.000** |
| Freelance Pack | $100.000 pago único | — | — |

#### Medios de pago y compatibilidad

| Medio | ¿Cuándo? | Compatible con registro DLUnire | Notas |
|-------|----------|--------------------------------|-------|
| **Wompi** (PSE, tarjeta, Nequi, Bancolombia) | Colombia | **Sí — preferido** | Pasarela principal Freelance. Link de pago con monto **COP** exacto. NIT **700551569-1**, sin IVA (RUT código 49). |
| **ePayco** (PSE, tarjeta, débito) | Colombia | **Sí** | Respaldo. Mismo monto en COP. |
| **Bancolombia** (link de recaudo) | Colombia | **Sí** | Respaldo. Mismo monto en COP. |
| **Nequi** o **transferencia Bancolombia** | Colombia | **Sí** | Pago manual; envíe comprobante por correo o WhatsApp. |
| **PayPal** | Fuera de Colombia o preferencia del cliente | **Sí** | Equivalente en **USD** según TRM del día de cotización (orientativo). |
| **Binance** (USDT u otra stablecoin) | Solo si el cliente lo pide | **Sí, manual** | Equivalente COP ÷ TRM del día; envíe **hash / captura** de la transacción. DLUnire confirma recepción antes del certificado. |

**Enlaces Wompi (Freelance):** tras solicitar, recibe el link de **pago único** (`$20.000` INICIO · `$50.000` proyecto · `$100.000` pack). **ventas@dlunire.dev** lo envía si aún no está en la web.

**Recomendación:** en Colombia, pague por **Wompi** (un clic, comprobante inmediato). PayPal o Binance solo si está fuera del país o lo prefiere.

### Paso 4 — Certificado

**Fundador ($0):** certificado tras validar datos (sin pago).

**De pago:** tras confirmar pasarela, Nequi/transferencia, PayPal o Binance:

1. Recibe **certificado de licencia** PDF con cliente, dominio, plan e ID (`LIC-2026-F…`).
2. Correo de bienvenida con resumen de alcance y soporte incluido.
3. Plazo orientativo: **≤ 3 días hábiles** (ideal: mismo día hábil).

---

## Proceso de compra (otros planes)

Para **Indie**, **Pro** o **Business**, el flujo es el mismo: correo a **ventas@dlunire.dev** → confirmación de alcance y monto COP → link **Wompi** / **ePayco** / **Bancolombia** (o alternativa acordada) → certificado.

---

## Preguntas frecuentes

**¿La licencia comercial me impide crear mi aplicación con DLUnire?**  
**No.** Puede instalar, desarrollar y probar sin licencia comercial. Solo entra en juego al desplegar código cerrado a usuarios externos por red.

**¿Es «gratis o de pago»? ¿Tengo que pagar para ganar dinero?**  
**No.** DLUnire es software libre (AGPL) y puede usarlo sin pagar. También puede **cobrar** por desarrollar, vender, hostear o dar soporte. La licencia comercial es **opcional** y solo aplica si despliega un producto con código cerrado a terceros por red y no quiere publicar ese código bajo AGPL.

**¿Un desarrollador que hace una encuesta o tienda para un cliente debe pagar?**  
**No por desarrollar ni por cobrar al cliente.** Solo al **desplegar en producción** con código cerrado: ahí el producto necesita licencia comercial o repo público AGPL. Mientras trabaja en local o staging, no paga nada a DLUnire.

**¿Una tienda donde usuarios compran por internet debe pagar licencia comercial?**  
**Sí**, si está en producción con código cerrado. Si la tienda es **de un cliente**, **Freelance** cubre el despliegue con **pago único** desde **$50.000 COP** (proyecto) o **$100.000 COP** (pack de 5). Alternativa gratuita: publicar el código bajo AGPL.

**¿Freelance o Indie?**  
**Freelance** si el sitio en producción es del **cliente** (usted entrega la obra). **Indie** o **Pro** si el producto es **suyo** (su SaaS, su tienda, su marca). Puede usar ambos en paralelo si tiene clientes y además opera su propio producto.

**¿Cómo pago Freelance desde el exterior?**  
Prefiera **PayPal** (USD según TRM del día) o **Binance** (USDT, manual con comprobante). En Colombia, **Wompi** es lo más directo.

**¿Hay cupos gratis Fundadores?**  
Sí: **20 cupos** con código **`DLUNIRE-FUNDADOR`** ($0, un cupo por titular). Cuando se agoten, aplica **DLUNIRE-INICIO** ($20.000) o $50.000 de lista.

**¿Aplica la promoción DLUNIRE-INICIO?**  
Sí, tras agotar Fundadores o si no califica: **$20.000 COP** en su primer proyecto pagado. Un uso por titular.

**¿Puedo usar DLUnire en un producto que cobro a mis clientes?**  
Sí. Tanto con AGPL (publicando fuentes) como con licencia comercial (código cerrado).

**¿La licencia cubre a mi cliente final?**  
Cubre **su** producto identificado en el contrato. Si cada cliente despliega su propia instancia cerrada como producto separado (white‑label), suele requerir Business o licencias adicionales.

**¿Puedo eliminar los avisos de copyright del ecosistema en mi repo?**  
No. Debe conservar avisos de licencia en archivos fuente del ecosistema que permanezcan en el proyecto.

**¿Necesito licencia para desarrollar en local?**  
No. Desarrollo, pruebas y staging sin usuarios externos comerciales no requieren licencia comercial.

**¿Pro incluye actualizaciones para siempre?**  
Incluye **12 meses** de actualizaciones del núcleo. Después, el **uso** del producto sigue siendo perpetuo en la última versión recibida; las versiones nuevas requieren renovación de soporte/updates (consultar).

**¿Hay soporte por videollamada?**  
No en los planes Indie y Pro. El soporte incluido es **solo por correo**.

**¿Puedo cambiar de Indie a Pro?**  
Consultar en ventas según su situación; no hay conversión automática documentada aquí.

---

## Alternativa gratuita (AGPL)

Si puede publicar el código fuente de **su aplicación** bajo **AGPL-3.0-or-later**, no necesita licencia comercial. Más contexto en la sección AGPL de la [welcome del kernel](/#licencia).

---

## Texto legal

- [SPDX — AGPL-3.0-or-later](https://spdx.org/licenses/AGPL-3.0-or-later.html)