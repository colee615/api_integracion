# Documentacion Unificada de Integracion Postal

## Objetivo

Este documento unifica toda la documentacion funcional, tecnica y de consumo del proyecto `Integracion`.

Aqui se explica en un solo lugar:

- como se autentica una empresa
- cual es el flujo correcto `CN31 -> CN33 -> CN22`
- que endpoints existen
- que valida cada API
- como se consultan resultados
- como usar la API desde frontend o sistemas externos
- que reglas de negocio, seguridad, sandbox e idioma estan activas

## Vision general

La integracion postal empresarial opera sobre tres documentos principales:

1. `CN31`
   manifiesto principal con sus sacas
2. `CN33`
   detalle de paquetes por saca
3. `CN22`
   pre-alerta y detalle individual de cada paquete

El flujo recomendado siempre es:

1. autenticar empresa
2. registrar `CN31`
3. registrar `CN33`
4. registrar `CN22`
5. consultar resultados
6. consumir webhooks o revisar portal

## Aislamiento por empresa

Todas las operaciones estan aisladas por `company_id`.

- una empresa no puede ver datos de otra
- cada token o sesion pertenece a una sola empresa
- el idioma de respuestas JSON depende de `company.locale`
- el frontend puede cambiar idioma segun la empresa y el backend responde en ese mismo idioma

## Base path y formato

### Base path

```http
/api/v1
```

El contrato descargable para el API de tracking y webhooks esta en [`openapi-tracking.yaml`](openapi-tracking.yaml).
El flujo local de paquetes y sacas, incluidos sus códigos y tipos de operación, se describe en [`flujo-eventos-ips-local.md`](flujo-eventos-ips-local.md).
El flujo técnico de integración de 360 Lions para `WS34353433222` se documenta en [`flujo-360-lions-ws34353433222.md`](flujo-360-lions-ws34353433222.md).

### Convenciones generales

- request y response en `JSON`
- respuestas con fechas en `ISO 8601` cuando aplica
- varias entradas requieren formato exacto `Y-m-d H:i:s`
- headers recomendados:

```http
Accept: application/json
Content-Type: application/json
```

## Modelos de autenticacion

### 1. Token API de empresa

Es el unico modelo habilitado para integracion.

```http
Authorization: Bearer {token_empresa}
```

El token se genera manualmente desde el panel administrativo y luego se reutiliza en todas las APIs operativas:

- `GET /api/v1/me`
- `POST /api/v1/cn31/manifests`
- `POST /api/v1/cn33/bags/{bagNumber}/packages`
- `POST /api/v1/cn22/shipments`
- `POST /api/v1/integration/bulk` (CN31 + CN33 + CN22 en una sola solicitud)
- `GET /api/v1/packages`
- `POST /api/v1/packages/{trackingCode}/movements`
- `GET /api/v1/cn33/bags/{bagReference}/movements`
- `POST /api/v1/cn33/bags/{bagReference}/movements`
- `POST /api/v1/webhooks`

Los permisos se asignan al emitir el token. Usa solo los necesarios: `tracking:read`, `tracking:write`, `tracking:events:write`, `documents:read`, `documents:write` y `webhooks:manage`. Por compatibilidad, `packages:read` también concede `tracking:read` y `documents:read`; `packages:write` también concede `tracking:write`, `tracking:events:write`, `documents:write` y `webhooks:manage`. Para tokens nuevos, conviene asignar permisos específicos.

Cada token está asociado a una empresa; las consultas usan esa empresa como alcance, por lo que un token de otra empresa no puede leer estos registros. Los tokens de prueba tienen además alcance por token: solo consultan registros creados por el mismo token. Los tokens de producción consultan los registros de producción de su empresa; no acceden a registros aislados de un token de prueba.

| Permiso | Acceso |
|---|---|
| `tracking:read` | Consultar paquetes, movimientos y contexto |
| `tracking:write` | Registrar un paquete |
| `tracking:events:write` | Agregar eventos al tracking |
| `documents:read` | Consultar CN31 y CN33 |
| `documents:write` | Crear CN31, CN33, CN22 y cargas masivas |
| `webhooks:manage` | Administrar destinos y reintentos |

Un token sin el permiso requerido recibe `403 INSUFFICIENT_ABILITY`.

### 2. Sesion portal empresa

Usado por el portal empresarial:

```http
Authorization: Bearer {portal_session_token}
```

### 3. Endpoint de contexto de integracion

```http
GET /api/v1/integration/context
```

Devuelve entorno, cupo sandbox, estados soportados y eventos webhook.
Tambien informa los permisos del token y las transiciones de estado permitidas.

## Estados operativos

### Estados de manifiesto o saca

- `pendiente_cn33`
- `conciliado`
- `observado`

### Estados documentales de paquete

- `pendiente_cn22`
- `documentado_cn22`

### Estados operativos de paquete

- `registrado`
- `pre_alerta_recibida`
- `recibido_api`
- `aceptado_en_origen`
- `recibido_centro_clasificacion`
- `despachado_origen`
- `en_transito`
- `arribado_destino`
- `en_proceso_aduana`
- `retenido_aduana`
- `liberado_aduana`
- `disponible_retiro`
- `en_ruta_entrega`
- `intentos_carteros`
- `entregado`
- `incidencia_entrega`
- `en_devolucion`
- `devuelto_remitente`
- `perdido`

`clasificado` se mantiene como alias legado de `recibido_centro_clasificacion`.

`en_transito` ahora conserva el significado de transporte entre puntos; `en_ruta_entrega` identifica la ultima milla. Versiones anteriores de este servicio podian normalizar ambos como ruta de entrega, asi que revisa esa expectativa antes de publicar el cambio a clientes existentes.

### Eventos webhook

- `registrado` -> `shipment.registered`
- `pre_alerta_recibida` -> `shipment.pre_alert_received`
- `recibido_api` -> `shipment.received_by_operator`
- `aceptado_en_origen` -> `shipment.accepted_at_origin`
- `recibido_centro_clasificacion` -> `shipment.received_sorting_center`
- `despachado_origen` -> `shipment.departed_origin`
- `en_transito` -> `shipment.in_transit`
- `arribado_destino` -> `shipment.arrived_destination`
- `en_proceso_aduana` -> `shipment.customs_in_progress`
- `retenido_aduana` -> `shipment.customs_held`
- `liberado_aduana` -> `shipment.customs_released`
- `disponible_retiro` -> `shipment.available_for_pickup`
- `en_ruta_entrega` -> `shipment.out_for_delivery`
- `intentos_carteros` -> `shipment.delivery_attempt`
- `incidencia_entrega` -> `shipment.delivery_incident`
- `en_devolucion` -> `shipment.return_started`
- `devuelto_remitente` -> `shipment.returned_to_sender`
- `entregado` -> `shipment.delivered`
- `perdido` -> `shipment.lost`

## Formato de errores

### Error entendible estandarizado

Usado por `CN31`, `CN33` y `CN22`:

```json
{
  "success": false,
  "code": "CN33_VALIDATION_ERROR",
  "message": "El CN33 contiene errores de validacion.",
  "error_count": 1,
  "errors": [
    {
      "field": "packages.0.recipient_name",
      "record_index": 0,
      "attribute": "recipient_name",
      "messages": [
        "El nombre del destinatario es obligatorio en CN33."
      ]
    }
  ]
}
```

### Error not found estandarizado

```json
{
  "success": false,
  "code": "CN31_NOT_FOUND",
  "message": "No se encontro el CN31 solicitado para esta empresa."
}
```

### Error Laravel clasico

Algunos endpoints operativos o de portal responden asi:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email field is required."
    ]
  }
}
```

## Portal empresa

El portal es solo para visualizacion y consulta. La empresa no inserta informacion manualmente en el portal.

### Login

```http
POST /api/v1/company/auth/login
```

```json
{
  "email": "empresa@cliente.com",
  "password": "123456789"
}
```

#### Respuesta

```json
{
  "message": "Inicio de sesion correcto.",
  "data": {
    "token": "PORTAL_SESSION_TOKEN",
    "user": {
      "name": "Empresa Demo",
      "email": "empresa@cliente.com",
      "role": "empresa"
    },
    "company": {
      "id": 1,
      "name": "360 Lions",
      "slug": "360-lions",
      "status": "active",
      "locale": "es"
    }
  }
}
```

### Perfil de sesion

```http
GET /api/v1/company/auth/me
```

### Logout

```http
POST /api/v1/company/auth/logout
```

### Cambio de contrasena

```http
POST /api/v1/company/auth/change-password
```

```json
{
  "current_password": "123456789",
  "password": "987654321",
  "password_confirmation": "987654321"
}
```

### Dashboard

```http
GET /api/v1/company/dashboard
```

Campos principales:

- `summary`
- `analytics`
- `insights`
- `alerts`
- `recent_manifests`
- `recent_bags`
- `recent_movements`
- `recent_packages`
- `tokens`

## Flujo simple para empresa integradora

La empresa debe pensar el proceso asi:

1. pruebo el acceso
2. declaro el manifiesto y las sacas
3. declaro los paquetes de cada saca
4. envio la ficha completa de cada paquete
5. consulto resultados

En una frase:

> Primero se declara el envio grande, luego el contenido de cada saca y al final el detalle individual de cada paquete.

## Paso 1. Validar identidad de integracion

```http
GET /api/v1/me
```

Sirve para confirmar:

- que el token funciona
- que la empresa es valida
- que el acceso sigue activo

## Paso 2. Registrar CN31

### Endpoint

```http
POST /api/v1/cn31/manifests
```

### Objetivo

Crear el manifiesto principal y sus sacas.

### Body ejemplo

```json
{
  "cn31_number": "CWPUNAPELIMAAUX60063",
  "dispatch_number_manifest": "0063",
  "origin_office": "CWPUNA",
  "destination_office": "PELIMA",
  "dispatch_date": "2026-10-20 00:00:00",
  "bags": [
    {
      "bag_number": "SACA-001",
      "package_count": 2,
      "total_weight_kg": 0.500,
      "dispatch_note": "Carga inicial"
    }
  ]
}
```

### Campos obligatorios

- `cn31_number`
- `dispatch_number_manifest`
- `origin_office`
- `destination_office`
- `dispatch_date`
- `bags`
- `bags[].bag_number`
- `bags[].package_count`
- `bags[].total_weight_kg`

### Reglas

- La unicidad de `cn31_number` se aplica dentro del alcance de empresa/token
- `cn31_number` recibe el `Dispatch-ID` completo impreso bajo el codigo de barras del CN31
- `dispatch_number_manifest` recibe el campo numerico `Dispatch No.` del CN31; es una cadena de longitud variable formada solo por digitos y conserva ceros iniciales (por ejemplo, `0063`)
- `dispatch_number_manifest` es obligatorio y puede repetirse si el serial se reutiliza en otra serie; `cn31_number` es el identificador para consultar un despacho dentro del alcance autenticado
- `dispatch_date` usa la fecha del CN31. Como el formulario no trae hora, enviarla como `YYYY-MM-DD 00:00:00`; la API la interpreta en UTC. No usar la hora de salida del vuelo del CN38.
- En CN31, `origin_office` y `destination_office` usan los códigos IMPC de seis caracteres de las oficinas de intercambio (por ejemplo, `CWPUNA` y `PELIMA`). La API los guarda como texto y no valida el catálogo IMPC.
- `bags` minimo `1`
- `bag_number` unico por empresa y distinto dentro del mismo request
- `bag_number` es el identificador unico de cada saca, asignado por 360Lions; debe ser unico en el alcance de empresa/token y reutilizarse sin cambios en las llamadas CN33 y de movimientos
- En el endpoint CN31, cada saca queda asociada al despacho al incluirse en `bags[]`; en carga masiva, la lista va en `manifest.bags[]`. 360Lions proporciona `bag_number`; la API no lo deriva del CN31/CN38 ni usa un campo separado `dispatch_number_bag`
- `package_count` entero mayor o igual a `1`
- `total_weight_kg` mayor a `0`

### Respuesta

```json
{
  "success": true,
  "message": "CN31 recibido correctamente.",
  "data": {
    "manifest_id": 1,
    "cn31_number": "CWPUNAPELIMAAUX60063",
    "dispatch_number_manifest": "0063",
    "status": "pendiente_cn33",
    "total_bags": 1,
    "total_packages": 2,
    "total_weight_kg": 0.5
  }
}
```

## Paso 3. Registrar CN33

### Endpoint

```http
POST /api/v1/cn33/bags/{bagNumber}/packages
```

### Objetivo

Declarar que paquetes van dentro de una saca existente.

### Precondicion

La saca debe existir previamente en `CN31`.

### Body ejemplo

```json
{
  "packages": [
    {
      "tracking_code": "EN000000001BO",
      "origin": "COCHABAMBA",
      "destination": "LA PAZ",
      "weight_kg": 0.25
    }
  ]
}
```

### Campos obligatorios

- `packages`
- `packages[].tracking_code`
- `packages[].origin`
- `packages[].destination`
- `packages[].weight_kg`

### Reglas

- `tracking_code` no duplicado en el mismo request
- `tracking_code` no declarado previamente en otra saca de la misma empresa
- `weight_kg` mayor a `0`

### Logica

- si el paquete ya existe en `packages`, se enlaza de inmediato
- si todavia no existe en `CN22`, queda como `pendiente_cn22`
- el sistema recalcula cantidad y peso cargado
- la saca queda `conciliado` o `observado`
- el manifiesto padre se actualiza en consecuencia

### Respuesta

```json
{
  "success": true,
  "message": "CN33 recibido correctamente.",
  "data": {
    "bag_id": 1,
    "bag_number": "SACA-001",
    "manifest_number": "CN31-0001-BO",
    "status": "conciliado",
    "declared_package_count": 2,
    "loaded_package_count": 2,
    "declared_weight_kg": 0.5,
    "loaded_weight_kg": 0.5,
    "documented_packages": 0,
    "pending_packages": 2
  }
}
```

## Paso 4. Registrar CN22

`CN22` es la pre-alerta aduanera completa y el detalle individual del paquete.

### Endpoint

```http
POST /api/v1/cn22/shipments
```

### Body ejemplo

```json
{
  "records": [
    {
      "tracking_code": "EN000000001BO",
      "origin_office": "COCHABAMBA",
      "destination_office": "LA PAZ",
      "sender_name": "Leonardo Doria Medina",
      "sender_country": "BOLIVIA",
      "sender_address": "Av. Sendero 123",
      "sender_phone": "78458965",
      "recipient_name": "Marco Antonio Espinoza Rojas",
      "recipient_document": "1234567",
      "recipient_address": "Av Mario Mercado 220",
      "recipient_address_reference": "Puerta azul frente a farmacia",
      "recipient_city": "LA PAZ",
      "recipient_department": "LA PAZ",
      "recipient_phone": "76785423",
      "recipient_whatsapp": "76785423",
      "destination": "LA PAZ",
      "description": "Documentos personales",
      "gross_weight_grams": 250,
      "weight_kg": 0.250,
      "length_cm": 20,
      "width_cm": 15,
      "height_cm": 2,
      "value_fob_usd": 17.00,
      "customs_items": [
        {
          "description": "Documentos",
          "quantity": 1,
          "value": 17.00,
          "weight_kg": 0.250,
          "hs_code": "490110",
          "origin_country": "BO"
        }
      ]
    }
  ]
}
```

## Paso opcional. Registrar todo en una sola API masiva

Si la empresa prefiere evitar tres llamadas separadas, puede enviar `CN31 + CN33 + CN22` en un solo request.

### Endpoint

```http
POST /api/v1/integration/bulk
```

### Objetivo

Registrar en una sola operacion:

- manifiesto `CN31`
- sacas `CN33`
- detalle individual `CN22`

### Estructura general

```json
{
  "manifest": {
    "cn31_number": "CWPUNAPELIMAAUX60063",
    "dispatch_number_manifest": "0063",
    "origin_office": "CWPUNA",
    "destination_office": "PELIMA",
    "dispatch_date": "2026-10-20 00:00:00",
    "bags": [
      {
        "bag_number": "SACA-BULK-001",
        "package_count": 1,
        "total_weight_kg": 0.250,
          "dispatch_note": "Carga masiva",
        "packages": [
          {
            "tracking_code": "EN000000901BO",
            "reference": "BULK-901",
            "recipient_name": "Marco Antonio Espinoza Rojas",
            "destination": "LA PAZ",
            "weight_kg": 0.250,
            "notes": "Paquete masivo",
            "cn22": {
              "origin_office": "COCHABAMBA",
              "destination_office": "LA PAZ",
              "sender_name": "360 Lions",
              "sender_country": "BOLIVIA",
              "sender_address": "Av. Integracion 123",
              "sender_phone": "70000000",
              "recipient_name": "Marco Antonio Espinoza Rojas",
              "recipient_document": "1234567",
              "recipient_address": "Av Mario Mercado 220",
              "recipient_address_reference": "Puerta azul frente a farmacia",
              "recipient_city": "LA PAZ",
              "recipient_department": "LA PAZ",
              "recipient_phone": "76785423",
              "recipient_whatsapp": "76785423",
              "description": "Documentos personales",
              "gross_weight_grams": 250,
              "length_cm": 20,
              "width_cm": 15,
              "height_cm": 2,
              "value_fob_usd": 17.00
            }
          }
        ]
      }
    ]
  }
}
```

### Reglas

- `manifest.package_count` debe coincidir con la cantidad real de `packages` dentro de cada saca
- cada `tracking_code` debe ser unico en toda la carga masiva
- cada paquete debe incluir su bloque `cn22`
- si una saca no cuadra en cantidad o peso, queda `observado`
- si cuadra, queda `conciliado`

### Respuesta

```json
{
  "success": true,
  "message": "Carga masiva CN31, CN33 y CN22 recibida correctamente.",
  "data": {
    "manifest_id": 1,
    "cn31_number": "CN31-BULK-001",
    "status": "conciliado",
    "total_bags": 1,
    "total_packages": 1,
    "total_weight_kg": 0.25,
    "bags": [
      {
        "bag_id": 1,
        "bag_number": "SACA-BULK-001",
        "status": "conciliado",
        "declared_package_count": 1,
        "loaded_package_count": 1,
        "documented_packages": 1
      }
    ],
    "packages": [
      {
        "tracking_code": "EN000000901BO",
        "bag_number": "SACA-BULK-001",
        "status": "pre_alerta_recibida"
      }
    ]
  }
}
```

### Campos obligatorios

- `records`
- `records[].tracking_code`
- `records[].origin_office`
- `records[].destination_office`
- `records[].sender_name`
- `records[].sender_country`
- `records[].sender_address`
- `records[].sender_phone`
- `records[].recipient_name`
- `records[].recipient_document`
- `records[].recipient_address`
- `records[].recipient_address_reference`
- `records[].recipient_city`
- `records[].recipient_department`
- `records[].recipient_phone`
- `records[].description`
- `records[].gross_weight_grams`
- `records[].length_cm`
- `records[].width_cm`
- `records[].height_cm`
- `records[].value_fob_usd` o `records[].declared_amount`

### Reglas

- `tracking_code` unico por empresa
- no duplicado dentro del mismo lote
- compatible con `UPU S10` o formato alfanumerico acordado
- `gross_weight_grams` entero mayor a `0`
- `weight_kg` puede calcularse automaticamente
- dimensiones mayores a `0`
- `value_fob_usd` mayor a `0`
- `customs_items` es opcional

### Logica

- crea paquete con estado `pre_alerta_recibida`
- crea movimiento inicial `pre_alerta_recibida`
- si el tracking ya estaba en `CN33`, enlaza paquete, saca y manifiesto
- guarda remitente, destinatario, declaracion y resultado API

### Respuesta

```json
{
  "success": true,
  "message": "Lote CN22 recibido correctamente.",
  "data": {
    "received": 1,
    "results": [
      {
        "package_id": 1,
        "tracking_code": "EN000000001BO",
        "status": "pre_alerta_recibida",
        "received_at": "2026-03-31T20:05:00+00:00",
        "tracking_standard": "UPU_S10",
        "message": "Registro CN22 recibido correctamente por el sistema."
      }
    ]
  }
}
```

## Consultas principales

### Listar manifiestos

```http
GET /api/v1/cn31/manifests
```

- paginado
- orden descendente por `dispatch_date`
- incluye `bags_count`
- filtros opcionales exactos por `dispatch_number_manifest` (cadena de solo digitos, longitud variable; conserva ceros iniciales) y por rangos inclusivos `dispatch_date_from` / `dispatch_date_to` y `received_at_from` / `received_at_to`
- fechas en formato `YYYY-MM-DD`; `dispatch_date` es la fecha declarada y `received_at` el momento en que la API registró el CN31
- `per_page` configurable entre 1 y 100 (predeterminado 20); `page` comienza en 1 y los enlaces conservan los filtros
- cada elemento devuelve únicamente `cn31_number`, `dispatch_number_manifest`, `origin_office`, `destination_office`, `dispatch_date`, `received_at`, `status`, `status_label`, `delivery_status`, `delivery_status_label`, `total_bags`, `total_packages`, `total_weight_kg` y `bags_count`
- no incluye IDs internos, `meta` ni columnas de auditoría

La respuesta conserva el paginador estándar en el nivel raíz: `data` contiene los despachos y allí mismo aparecen `current_page`, `per_page`, `total`, `last_page`, `links` y las URL de navegación. No hay un objeto `meta` envolviendo esos datos.

Ejemplos:

```http
GET /api/v1/cn31/manifests?dispatch_number_manifest=0063
GET /api/v1/cn31/manifests?dispatch_date_from=2026-10-01&dispatch_date_to=2026-10-31&per_page=100
GET /api/v1/cn31/manifests?received_at_from=2026-10-01&received_at_to=2026-10-31
```

### Ver CN31

```http
GET /api/v1/cn31/manifests/{cn31Number}
```

Incluye:

- datos generales
- sacas declaradas
- `loaded_packages`
- `loaded_weight_kg`
- `documented_packages`
- `status`
- `delivery_summary` con sacas registradas/completadas/en proceso/pendientes y paquetes registrados/entregados/en proceso/pendientes
- un `delivery_summary` de paquetes por saca

### Ver saca CN33

```http
GET /api/v1/cn33/bags/{bagNumber}
```

Incluye:

- identificacion de saca
- manifiesto padre
- cantidad declarada
- peso declarado
- estado
- lista de paquetes
- `delivery_summary` con paquetes registrados/entregados/en proceso/pendientes

Para consultar la bitácora de la saca:

```http
GET /api/v1/cn33/bags/{bagReference}/movements
```

Requiere `tracking:read` y devuelve los movimientos de la saca en orden cronológico, con paginación. Esta historia es independiente de los movimientos de cada paquete.

### Listar paquetes

```http
GET /api/v1/packages
GET /api/v1/packages?tracking_code=PKG-001
```

La respuesta conserva el paginador estándar en el nivel raíz: `data` contiene los paquetes y allí mismo aparecen `current_page`, `per_page`, `total`, `last_page`, `links` y las URL de navegación. Cada elemento es un resumen con `tracking_code`, `reference`, `origin`, `destination`, `status`, `status_label`, `registered_at` y `last_movement_at`. No serializa el modelo completo ni incluye identificadores internos o datos de contacto/documento del remitente o destinatario. Para ver el detalle de un envío, consulta `/api/v1/packages/{trackingCode}`.

### Ver paquete

```http
GET /api/v1/packages/{trackingCode}
```

### Listar movimientos

```http
GET /api/v1/packages/{trackingCode}/movements
```

El detalle del paquete muestra hasta los 20 movimientos marcados `is_public = true`. Este endpoint autenticado pagina el historial completo con `per_page` entre 1 y 100 y devuelve solo esos movimientos.

## API operativa de paquetes

### Crear paquete manual

```http
POST /api/v1/packages
```

```json
{
  "tracking_code": "PKG-001",
  "reference": "PED-1001",
  "recipient_name": "Juan Perez",
  "recipient_document": "1234567",
  "destination": "La Paz",
  "status": "registrado",
  "registered_at": "2026-03-31 10:00:00",
  "meta": {
    "canal": "web"
  }
}
```

Campos obligatorios:

- `tracking_code`
- `recipient_name`

## API de movimientos

### Registrar movimiento

```http
POST /api/v1/packages/{trackingCode}/movements
```

Envia `Idempotency-Key` para que un reintento no duplique el evento. La clave se limita al envio y a la empresa. Si se repite con el mismo contenido, la API responde `200`; si se reutiliza con contenido distinto, responde `409`.

En el evento puedes enviar `status` (codigo de negocio en espanol) o `event_code` (codigo estable en ingles). Si envias ambos, deben representar el mismo estado.
Tambien puedes enviar un `ips_operation_code` para registrar uno de los códigos de operación configurados junto con su tipo numerico; el movimiento se guarda en las bitácoras locales. En un evento `EMI`, `signatory` guarda un nombre como texto complementario: por sí solo no prueba la identidad del receptor ni adjunta evidencia de firma.

```json
{
  "status": "en_proceso_aduana",
  "event_code": "shipment.customs_in_progress",
  "location": "Aeropuerto El Alto",
  "description": "Ingreso a canal aduanero",
  "occurred_at": "2026-03-31T11:30:00-04:00",
  "location_code": "LPZ-HUB-01",
  "reason_code": "recipient_unavailable",
  "source_reference": "scan-839201",
  "meta": {
    "source": "hub-central"
  }
}
```

`occurred_at` debe ser una fecha RFC 3339 con zona horaria. Se guardan por separado la hora del hecho (`occurred_at`) y la hora en que el sistema lo recibio (`recorded_at`), ambas normalizadas a UTC. Los eventos atrasados se agregan al historial y no hacen retroceder el estado actual.

Estados soportados:

- `pre_alerta_recibida`
- `recibido_centro_clasificacion`
- `aceptado_en_origen`
- `despachado_origen`
- `arribado_destino`
- `en_proceso_aduana`
- `retenido_aduana`
- `liberado_aduana`
- `disponible_retiro`
- `en_ruta_entrega`
- `entregado`
- `incidencia_entrega`
- `intentos_carteros`
- `en_transito`
- `en_devolucion`
- `devuelto_remitente`
- `perdido`

Logica:

- valida la transicion contra el estado actual
- agrega un evento con ID, codigo estable, origen y hora de registro
- actualiza el estado del envio de forma transaccional
- registra idempotencia y conserva eventos fuera de orden cuando se envian estados o codigos `event_code`; las operaciones con `ips_operation_code` deben respetar su secuencia local
- encola el webhook sin esperar al servidor receptor

`GET /api/v1/integration/context` devuelve el catalogo vigente y las transiciones para que cada empresa pueda adaptar su integracion.
`reason_code` permite clasificar incidentes y causas de intento; el contexto de integracion publica codigos recomendados.
Para movimientos de paquete enviados con `status` o `event_code`, el servidor acepta hasta cinco minutos de desviacion futura en `occurred_at` y guarda eventos atrasados sin retroceder el estado actual. Si se envia `ips_operation_code`, la fecha no puede estar en el futuro y debe ser posterior al movimiento anterior. Los movimientos de saca tampoco aceptan fechas futuras y deben seguir el orden de la bitacora.

### Eventos de sacas y coordinación local

Las sacas tienen su propia bitácora: `RECEPTACLE_RECEIVE` (tipo 130) confirma recepción física y `RECEPTACLE_OPEN` (tipo 131) abre la saca recibida en la misma oficina. Abrir una saca no crea movimientos en sus paquetes; cada tracking sigue su flujo individual.

Consulta una saca con `GET /api/v1/cn33/bags/{bagReference}/movements`. Las escrituras de paquete y saca requieren `tracking:events:write`. La conciliación CN33, la recepción/apertura física y el avance de entrega se mantienen en campos distintos.

## Webhooks

### Registrar endpoint

```http
POST /api/v1/webhooks
```

```json
{
  "name": "360Lion Produccion",
  "target_url": "https://360lion.example/webhooks/agbc",
  "events": [
    "shipment.received_sorting_center",
    "shipment.customs_in_progress",
    "shipment.customs_released",
    "shipment.out_for_delivery",
    "shipment.delivered",
    "shipment.delivery_incident"
  ]
}
```

### Listar endpoints

```http
GET /api/v1/webhooks
```

Incluye:

- configuracion del endpoint
- secreto enmascarado
- ultimas entregas
- estado de exito o error

Los destinos deben usar HTTPS y resolver a una IP publica. El secreto se cifra en la base de datos y se muestra completo una sola vez al crear el endpoint. Cada peticion incluye `X-Webhook-Event`, `X-Webhook-Delivery` y `X-Webhook-Signature`; esta ultima es el HMAC-SHA256 hexadecimal del cuerpo JSON exacto con el secreto compartido.

La entrega es al menos una vez: el receptor debe usar `X-Webhook-Delivery` como identificador de deduplicacion, porque un timeout puede provocar que el mismo evento se reenvie.

```http
GET    /api/v1/webhooks/{id}/deliveries
POST   /api/v1/webhooks/{id}/deliveries/{deliveryId}/retry
POST   /api/v1/webhooks/{id}/test
PATCH  /api/v1/webhooks/{id}
DELETE /api/v1/webhooks/{id}
```

`PATCH` permite cambiar URL, eventos y activacion, o rotar el secreto. El secreto nuevo se devuelve una sola vez.

Las entregas son asincronas y reintentan fallos hasta seis veces con espera progresiva. Despues, quedan en estado `failed` y pueden reintentarse manualmente. En produccion deben ejecutarse un worker (`php artisan queue:work`) y el scheduler de Laravel (`php artisan schedule:work`, o `schedule:run` desde cron cada minuto); el comando `webhooks:dispatch-pending` recupera entregas que quedaron pendientes.

Antes de aplicar migraciones, realiza un respaldo y confirma que `APP_KEY` este configurada y sea estable: cifra los secretos existentes de webhook. Para envios que no tenian movimientos, la migracion agrega un evento base con el estado actual; no es posible reconstruir escaneos historicos que nunca se guardaron.

Aplica el cambio en el entorno destino con `php artisan migrate --force`, mantén un worker activo y registra `schedule:run` cada minuto en cron.

## API interna de entrega

Estas rutas son solo para servicios internos y requieren `X-Internal-Token`:

```http
POST /api/v1/internal/packages/delivery-attempt
POST /api/v1/internal/packages/deliver
```

Configura `INTERNAL_API_TOKEN` con un secreto aleatorio fuerte en el entorno del servidor. La aplicacion falla de forma cerrada si el valor no esta configurado.

Cuando un `tracking_code` exista en varias empresas, incluye `company_id`; sin esa identificacion la API responde `409` en lugar de modificar un envio arbitrario. También admiten `Idempotency-Key`.

La API interna opera sobre envios de produccion y omite los datos aislados de tokens de prueba.

## Seguridad y reglas de negocio

### Token API de empresa

- cada token pertenece a una empresa
- puede estar `activo`, `programado`, `expirado` o `revocado`
- se usa directamente como `Bearer`
- es el mismo token para todas las APIs de integracion

### Sandbox

- la empresa puede operar en `sandbox` o `production`
- sandbox controla:
  - ventana temporal
  - cupo maximo de `100` envios ficticios
  - contador usado
- si `CN22` supera el cupo, responde `SANDBOX_SHIPMENT_LIMIT_REACHED`
- al revocar, desactivar o eliminar un token de tipo `trial`, se purgan sus registros de prueba: manifiestos, sacas, paquetes y sus movimientos, además de sus datos de webhooks
- reactivar el token no restaura esos registros; los datos de producción no se eliminan

### Duplicados

- `CN31`: no admite `cn31_number` duplicado por empresa
- `CN31`: no admite `bag_number` duplicado por empresa
- `CN33`: no admite `tracking_code` duplicado por empresa
- `CN22`: no admite `tracking_code` duplicado por empresa

### Portal

- login solo para usuarios `role = company`
- la sesion se guarda por `12 horas`
- requiere empresa activa

## Consumo desde Nuxt o frontend

### Base URL sugerida

```env
NUXT_PUBLIC_API_BASE=http://localhost/api/v1
```

### Headers

```http
Authorization: Bearer TU_TOKEN
Accept: application/json
Content-Type: application/json
```

### Helper simple en Nuxt

```ts
const config = useRuntimeConfig()

export const apiFetch = async <T>(path: string, options: Record<string, any> = {}) => {
  const token = useState<string>('api-token').value

  return await $fetch<T>(`${config.public.apiBase}${path}`, {
    ...options,
    headers: {
      Accept: 'application/json',
      Authorization: `Bearer ${token}`,
      ...(options.headers || {}),
    },
  })
}
```

### Endpoints frontend mas utiles

- `GET /api/v1/me`
- `GET /api/v1/packages`
- `GET /api/v1/packages/{trackingCode}`
- `GET /api/v1/packages/{trackingCode}/movements`
- `POST /api/v1/packages/{trackingCode}/movements`

## Errores comunes explicados simple

### Token invalido

Significa:

- la llave no sirve
- esta vencida
- fue revocada

### CN31 duplicado

Significa:

- ese manifiesto ya fue usado por la misma empresa

### Saca no encontrada en CN33

Significa:

- se esta intentando cargar una saca que no fue creada en `CN31`

### Tracking duplicado

Significa:

- ese paquete ya existe o ya fue declarado

### Estado observado

Significa:

- cantidad o peso no coincide con lo declarado

## Secuencia de prueba recomendada

1. `GET /api/v1/me`
2. `POST /api/v1/cn31/manifests`
3. `POST /api/v1/cn33/bags/{bagNumber}/packages`
4. `POST /api/v1/cn22/shipments`
5. `GET /api/v1/cn31/manifests/{cn31Number}`
6. `GET /api/v1/cn33/bags/{bagNumber}`
7. `GET /api/v1/packages/{trackingCode}`
8. `GET /api/v1/packages/{trackingCode}/movements`

## Resumen final

La secuencia correcta siempre es:

1. autenticacion
2. `CN31`
3. `CN33`
4. `CN22`
5. consultas
6. webhooks o portal

Si la empresa respeta ese orden, el sistema puede:

- guardar correctamente manifiesto, saca y paquete
- conciliar cantidades y pesos
- activar trazabilidad end-to-end
- preparar pre-alerta aduanera
- devolver estados y resultados en el idioma de la empresa

Este archivo es el documento maestro unico del proyecto. Si se agregan nuevas APIs o reglas, este archivo debe actualizarse.
