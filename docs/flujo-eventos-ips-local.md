# Flujo local de eventos de paquetes y sacas

Este flujo registra eventos en la base local de `api_integracion` para coordinar CN31, sacas y paquetes. No ejecuta procedimientos, consultas ni escrituras en IPS.

## Estructura del flujo

El sistema conserva tres estados distintos:

| Entidad | Estado documental | Estado operativo |
|---|---|---|
| CN31 | `status` (pendiente/conciliado/observado) | `delivery_status` calculado desde sus sacas |
| Saca CN31 | `status` (pendiente CN33/conciliado/observado) | `operational_status` para recepción y apertura; `delivery_status` calculado desde sus paquetes |
| Fila CN33 | `status` para vínculo documental CN22 | `delivery_status` calculado desde el paquete enlazado |
| Paquete | No aplica | `status` y `package_movements` son el tracking individual |

Recibir o abrir una saca nunca crea eventos para los paquetes que contiene. Cada paquete necesita su propio movimiento. La bitácora de la saca y la del paquete son historias independientes.

## Códigos de operación y tipos de evento

Los movimientos guardan el código de operación (`ips_operation_code`) y su tipo numérico (`ips_event_type_cd`) en la base local. `event_code` conserva el código de tracking `shipment.*` para compatibilidad con integraciones actuales. Estos valores no se envían a un sistema externo.

| Unidad | Operación | Tipo de evento | Estado local del paquete/saca |
|---|---|---:|---|
| Paquete | `EMA` admisión | 1 | `aceptado_en_origen` |
| Paquete | `EMD` alta al recibir en intercambio | 30 | `recibido_centro_clasificacion` |
| Paquete | `EMR` recepción de paquete existente en intercambio | 30 | `recibido_centro_clasificacion` |
| Paquete | `CUSTOMS_PRESENT` envío a Aduana | 31 | `en_proceso_aduana` |
| Paquete | `CUSTOMS_INFO` información aduanera | 34 | `en_proceso_aduana` |
| Paquete | `EDN` despacho a ubicación nacional | 35 | `despachado_origen` |
| Paquete | `EIB` incorporación a saca nacional | 13 | `en_transito` |
| Paquete | `EMG` recepción en oficina de entrega | 32 | `arribado_destino` |
| Paquete | `CUSTOMS_RETURN` retorno desde Aduana | 38 | `liberado_aduana` |
| Paquete | `HANDOVER_AGENT` transferencia al agente | 39 | `en_ruta_entrega` |
| Paquete | `EDG` salida a reparto | 74 | `en_ruta_entrega` |
| Paquete | `EDH` disponible para retiro | 75 | `disponible_retiro` |
| Paquete | `EMH` intento fallido | 36 | `intentos_carteros` |
| Paquete | `EMI` entregado | 37 | `entregado` |
| Saca | `RECEPTACLE_RECEIVE` recepción | 130 | `recibido` |
| Saca | `RECEPTACLE_OPEN` apertura | 131 | `abierto` |

`EMA` y `EMD` son operaciones de admisión y se muestran como referencia, pero no se permiten al registrar movimientos de paquetes ya existentes. Para la recepción de un paquete ya prealertado se usa `EMR`.

Los estados locales representan el avance de negocio de esta API. La siguiente operación se limita según el último código de operación local y el estado del paquete, conforme a `IpsStagePolicy`. La API no valida reglas externas de estado, autorización de oficinas, ruteo por país ni catálogos de referencia que no mantiene en su base.

## Reglas operativas

- La recepción de saca requiere confirmación física y comienza en `pendiente_recepcion`.
- Solo se puede abrir una saca recibida y en el mismo código de oficina donde se recibió.
- `EMR` requiere que el paquete figure dentro de la saca CN33 indicada, que esa saca ya esté recibida y abierta localmente y que la recepción del paquete use el mismo código de oficina donde se abrió.
- Para movimientos de paquete con `ips_operation_code`, la fecha no puede estar en el futuro y debe ser posterior al último movimiento. Los movimientos de saca aplican la misma regla. Los eventos de paquete enviados solo con `status` o `event_code` admiten hasta cinco minutos de desviación futura y los atrasados se guardan sin hacer retroceder el estado actual.
- Las escrituras usan transacción, bloqueo del agregado, historial append-only e idempotencia.
- Una entrega individual actualiza el estado de tracking del paquete y los avances calculados CN33/saca/CN31, sin reemplazar sus estados documentales.
- Los movimientos registrados desde el panel quedan marcados `is_public = true` y aparecen en el endpoint autenticado de tracking. El envío de webhook de paquete es opcional y viene desactivado en el formulario.
- Las operaciones del endpoint de saca se guardan en la bitácora de la saca; no disparan por sí solas eventos de tracking de paquete.

## Captura y consulta

En el panel administrativo, abre el CN31 y la saca para registrar `RECEPTACLE_RECEIVE` y luego `RECEPTACLE_OPEN`. Dentro del detalle individual se registra cada operación del paquete disponible para su estado actual. La captura almacena actor, código de oficina, hora del hecho, hora de registro, confirmación física cuando aplica, referencia de idempotencia y metadatos específicos de operación.

Con token de empresa y permiso `tracking:read` se consulta el historial de paquete y saca:

```http
GET /api/v1/packages/{trackingCode}/movements
GET /api/v1/cn33/bags/{bagReference}/movements
```

El permiso `tracking:events:write` habilita la captura API de eventos de paquete y saca. El evento de saca usa `RECEPTACLE_RECEIVE` o `RECEPTACLE_OPEN`; `location_code` es obligatorio y la apertura valida la oficina de recepción.

El catálogo publicado por `GET /api/v1/integration/context` incluye códigos, tipos numéricos y campos requeridos para cada operación.
