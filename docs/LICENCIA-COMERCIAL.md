# Licencia comercial DLUnire — Alcance y tarifas

Guía pública para desarrolladores y empresas que necesitan usar el ecosistema DLUnire (**DLCore**, **DLRoute**, **DLStorage**, skeleton **dlunire/dlunire**) en un producto de **código cerrado** o **SaaS** sin publicar el fuente de su aplicación bajo AGPL-3.0.

**Titular:** DLUnire · David E Luna M  
**Contacto licencias:** [Repositorio DLUnire](https://github.com/dlunire/dlunire) (abrir issue o contacto indicado en el README)

> Resumen orientativo. El contrato firmado prevalece sobre este documento.

---

## ¿Cuándo necesita licencia comercial?

| Situación | AGPL gratuita | Licencia comercial |
|-----------|---------------|-------------------|
| Proyecto open source que publica su código | ✓ | No necesaria |
| Uso interno (solo su equipo, sin usuarios externos) | ✓ | Normalmente no |
| SaaS / API / web con código **cerrado** | Debe publicar fuentes | ✓ Exento de publicar |
| Producto on-premise sin entregar fuentes | Debe publicar fuentes | ✓ Exento de publicar |
| Cliente exige contrato y claridad legal | — | ✓ |

---

## Tarifas

Los precios de lista están en **pesos colombianos (COP)**. El equivalente en **USD** es orientativo: varía con la [TRM](https://www.banrep.gov.co/es/estadisticas/trm) del mercado, pero **el monto a pagar en Colombia es el COP indicado**.

| Plan | Precio COP (lista) | USD orientativo* |
|------|-------------------|------------------|
| **Indie** (anual) | **$600.000 / año** | ≈ USD 150 |
| **Pro** (pago único) | **$800.000** | ≈ USD 200 |
| **Business** | Cotización en COP | A convenir |

\*Ejemplo con TRM ≈ $4.000 COP/USD: $600.000 ÷ 4.000 ≈ 150 · $800.000 ÷ 4.000 ≈ 200. El USD orientativo varía con la TRM.

**IVA:** precios **sin IVA**. DLUnire no es responsable de IVA según RUT (código 49). El total a pagar es el valor en COP de la tabla.

### Indie — anual

Para un **(1) producto** (una aplicación o SaaS).

- Derecho de uso comercial con código cerrado.
- Actualizaciones del núcleo mientras la suscripción esté vigente.
- Soporte por correo: hasta **4 horas/año** (instalación, licencia, integración básica).
- Respuesta orientativa en **2–3 días hábiles**.

### Pro — pago único

Para un **(1) proyecto** con licencia perpetua de uso.

- Derecho perpetuo para ese producto identificado en el contrato.
- **12 meses** de actualizaciones del núcleo (DLCore, DLRoute, DLStorage).
- Soporte por correo: hasta **8 horas** el primer año; **2 horas/año** en años siguientes (consultas de licencia e integración, sin periodo de actualizaciones salvo renovación de soporte).
- Ideal si prefiere un solo pago inicial.
- Tras los 12 meses de actualizaciones, el **uso perpetuo** continúa en la última versión del núcleo recibida.

### Business — bajo propuesta

Para equipos con varios productos, SLA o bolsa de horas de integración.

Contactar a DLUnire con descripción de la organización y número de aplicaciones.

### Pago en Colombia

- **ePayco** — PSE, tarjeta débito/crédito y medios habilitados en su cuenta.
- **Bancolombia** — según link o botón de recaudo de su comercio.
- **Transferencia / Nequi** — bajo solicitud a ventas@dlunire.dev.

Tras confirmar el pago se envía el certificado de licencia (plazo orientativo: 3 días hábiles).

### Renovación (plan Indie)

Si no renueva la suscripción anual, puede **seguir operando** la última versión del núcleo recibida durante la vigencia pagada, pero **sin** nuevas actualizaciones ni soporte incluido hasta renovar. No amplía el alcance a productos adicionales.

---

## Alcance incluido

Al adquirir una licencia comercial recibe:

1. **Contrato o certificado** que acredita el derecho de uso en el producto indicado.
2. **Derecho legal** de integrar y desplegar el ecosistema sin obligación AGPL sobre el código de **su** aplicación.
3. **Actualizaciones** del núcleo según el plan (ver tarifas).
4. **Soporte por correo** dentro del límite de horas del plan, en temas de:
   - Licenciamiento y cumplimiento.
   - Instalación (`composer`, `.env.type`, `Project::run()`).
   - Integración básica (rutas, modelos, vistas, despliegue PHP habitual).
   - Orientación para actualizar versiones del núcleo.

---

## Alcance no incluido

La licencia comercial **no** incluye:

- Desarrollo de funcionalidades de negocio de su producto.
- Diseño, contenido, marketing ni mantenimiento de su aplicación.
- Hosting, DevOps, backups ni administración de servidores.
- Auditorías de seguridad ni certificaciones.
- Soporte 24/7 ni guardias.
- Licencia para un **segundo producto** sin ampliación de plan.
- Sublicenciar o revender el framework como producto independiente.

Servicios adicionales pueden cotizarse por separado.

---

## Proceso de solicitud

1. Identifique su producto (nombre, descripción o URL).
2. Elija plan **Indie** o **Pro** (o solicite **Business**).
3. Escriba a **ventas@dlunire.dev** indicando plan y producto.
4. Reciba propuesta, alcance y monto en **COP** (precio de lista; sin IVA).
5. Pague con enlace **ePayco** o **Bancolombia**, o transferencia acordada.
6. Reciba certificado de licencia y acceso a soporte según plan.

---

## Alternativa gratuita

Si puede publicar el código fuente de su aplicación bajo **AGPL-3.0-or-later**, no necesita licencia comercial. Véase [AGPL en la práctica](https://github.com/dlunire/dlcore#licencia) en la documentación del kernel.

---

## Texto legal AGPL

- [SPDX — AGPL-3.0-or-later](https://spdx.org/licenses/AGPL-3.0-or-later.html)