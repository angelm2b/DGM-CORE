# DGM CORE — Sistema de Gestión Migratoria (módulo CORE)

CORE (sistema central) de un sistema académico inspirado en la **Dirección General
de Migración (DGM)** de la República Dominicana. Contiene la lógica de negocio y la
base de datos maestra. Es uno de cuatro productos independientes; las apps cliente
(Caja y Web) **nunca** lo consumen directamente, solo a través de un **Sistema de
Integración**. Por eso el CORE expone una **API interna** (`/core/v1`) pensada para
un único cliente: el *integrador*.

> Este repositorio implementa **solo el CORE**. No incluye las apps de Caja ni Web,
> ni el Sistema de Integración.

## Tabla de contenido

- [Stack](#stack)
- [Puesta en marcha (local)](#puesta-en-marcha-local)
- [Configuración (`.env`)](#configuración-env)
- [Documentación de la API (OpenAPI / Scramble)](#documentación-de-la-api-openapi--scramble)
- [Arquitectura y reglas transversales](#arquitectura-y-reglas-transversales)
- [Endpoints (`/core/v1`)](#endpoints-corev1)
- [Ejemplos de llamadas](#ejemplos-de-llamadas)
- [Roles y usuarios sembrados](#roles-y-usuarios-sembrados)
- [Pruebas](#pruebas)
- [Desarrollo y mantenimiento](#desarrollo-y-mantenimiento)
- [Estructura del proyecto](#estructura-del-proyecto)

## Stack

- **Laravel 13** + **PHP 8.3+**
- **MySQL 8** (Eloquent, transacciones en toda escritura multi-tabla)
- **Laravel Sanctum** — token de larga vida para el integrador
- **dedoc/scramble** — documentación OpenAPI generada del código
- **owen-it/laravel-auditing** — auditoría de cambios
- **spatie/laravel-model-states** — máquina de estados de la solicitud
- **bcmath** para todo el dinero (`DECIMAL(12,2)`, nunca floats)
- Sin dependencias de Node: es 100% backend API (no hay frontend ni paso de build)

## Puesta en marcha (local)

**Requisitos:** PHP 8.3+, Composer y MySQL 8 en marcha.

```bash
# 1. Dependencias
composer install

# 2. Entorno (copia el ejemplo y genera la clave de la app)
cp .env.example .env
php artisan key:generate

# 3. Crea la base de datos (ajusta credenciales en .env)
mysql -uroot -e "CREATE DATABASE IF NOT EXISTS dgm_core CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 4. Migra y siembra datos reales (categorías, servicios, tarifas, oficinas,
#    puntos de control, roles/usuarios y el token del integrador)
php artisan migrate --seed

# 5. Levanta el servidor
php artisan serve            # http://localhost:8000

# 6. (Opcional) Scheduler: ejecuta el job de caducidad RN-07 una vez al día.
#    Solo es necesario si quieres que las solicitudes inactivas caduquen solas.
php artisan schedule:work
```

> **Atajo:** `composer setup` ejecuta los pasos 1, 2 y 4 (install, copia del `.env`,
> `key:generate` y `migrate --seed --force`) de una sola vez.

El **token Sanctum del integrador** se imprime al sembrar y queda guardado en
`storage/integrador_token.txt`. Úsalo como `Authorization: Bearer <token>` en cada
petición a la API. El seeder es idempotente: si el token ya existe, no se regenera.

Una sonda de salud queda disponible en `http://localhost:8000/up`.

## Configuración (`.env`)

Variables propias del CORE (todas con valor por defecto razonable):

| Variable | Por defecto | Descripción |
|----------|-------------|-------------|
| `DB_DATABASE` | `dgm_core` | Base de datos MySQL del CORE. |
| `DGM_DOCS_PASSWORD` | _(vacío)_ | Clave admin para abrir la documentación de la API en producción. Vacía = acceso por clave deshabilitado (403 en prod). |
| `DGM_CADUCIDAD_DIAS` | `90` | Días calendario de inactividad para caducar una solicitud (RN-07). |
| `DGM_ORDEN_PAGO_VIGENCIA_DIAS` | `15` | Días de validez de una orden de pago. |

Las reglas de negocio parametrizables (montos, plazos, edad de mayoría, etc.) viven
en [`config/dgm.php`](config/dgm.php) y no requieren tocarse para el uso normal.

## Documentación de la API (OpenAPI / Scramble)

- UI interactiva: `http://localhost:8000/docs/api`
- Especificación OpenAPI: `http://localhost:8000/docs/api.json`
- Exportar a archivo: `php artisan scramble:export` (genera `api.json`)

En **local** la documentación queda abierta. En **producción** se protege con clave:
el acceso pasa primero por `/docs-acceso`, donde se valida `DGM_DOCS_PASSWORD`
(máx. 5 intentos por minuto y por IP).

Todas las rutas viven bajo `/core/v1` y requieren `Authorization: Bearer <token>`.

## Arquitectura y reglas transversales

- **Versionado**: prefijo `/core/v1` (configurado en `bootstrap/app.php`).
- **Errores** estandarizados estilo `problem+json` (RFC 7807):
  `{ type, title, status, detail, correlationId }` con
  `Content-Type: application/problem+json`.
- **Correlación**: cada request propaga `X-Correlation-Id` (se genera si no viene)
  y se devuelve en la respuesta.
- **Idempotencia**: el registro de pagos usa el encabezado `Idempotency-Key`; una
  misma clave devuelve siempre el mismo pago, sin duplicar el cobro.
- **Autorización**: cada ruta exige el permiso del rol asociado al token, vía el
  middleware `permiso:` (p. ej. `permiso:pagos.registrar`).
- **Validación** con Form Requests; **salida** con API Resources (nunca modelos crudos).
- **Auditoría** (owen-it) en `personas`, `solicitudes`, `ordenes_pago`, `pagos`,
  `documentos_emitidos` y `tarifas`.
- **Numeración** correlativa sin colisiones vía tabla `secuencias` con
  `SELECT ... FOR UPDATE` (MySQL no tiene `SEQUENCE`): `numero_expediente`
  (`DGM-AAAA-NNNNNN`), `numero_comprobante` (`CMP-...`) y `numero_serie` (`DOC-...`).

### Máquina de estados de la solicitud

```
BORRADOR → ENVIADA → EN_DEPURACION → APROBADA_PAGO_PENDIENTE → PAGADA
        → EN_PROCESO → APROBADA → DOCUMENTO_EMITIDO → ENTREGADO
EN_DEPURACION ↔ DOCS_OBSERVADOS
(cualquier estado activo) → RECHAZADA | CADUCADA | ANULADA
```

Estados terminales: `ENTREGADO`, `RECHAZADA`, `CADUCADA` y `ANULADA`.

Cada transición valida que sea legal, corre en transacción, escribe el historial
append-only (`solicitud_estados`) y actualiza `fecha_ultima_accion` (base de la
caducidad RN-07). La implementación usa `spatie/laravel-model-states`
(ver [`app/States/Solicitud`](app/States/Solicitud)).

### Reglas de negocio (RN-01 a RN-12)

| Regla  | Descripción | Implementación |
|--------|-------------|----------------|
| RN-01  | Turista: máx. 120 días de estadía (base 30) | `ElegibilidadService::validarProrrogaTurista` |
| RN-02  | Sobreestadía escalonada + RD$5,000/año desde 10 años | `CalculadoraEstadiaService` |
| RN-03  | Renovación con ≥45 días de antelación | `ElegibilidadService::validarAntelacionRenovacion` |
| RN-04  | Penalidad RD$1,000 por mes de RT vencida | `CalculadoraPenalidadService` |
| RN-05  | Solo RT-9 → RP-1 dentro del país (exige carnet) | `ElegibilidadService::validarCambioCategoria` |
| RN-06  | Carnet RP-1: 1 año el primero, 4 años los siguientes | `DocumentoService` / `ElegibilidadService::vigenciaCarnetRP1` |
| RN-07  | Caducidad por inactividad > 90 días | comando `solicitudes:caducar` (scheduler) |
| RN-08  | Renovación exige PÓLIZA validada | `ElegibilidadService::validarPolizaRenovacion` |
| RN-09  | Menores de 18 → certificación de salida de menores | `ElegibilidadService::requiereCertificacionMenor` |
| RN-10  | Pasaporte con vigencia ≥6 meses | `ElegibilidadService::validarVigenciaPasaporte` |
| RN-11  | Adjuntos solo JPG | `CargarAdjuntoRequest` |
| RN-12  | RT-9 casado con dominicano(a): solvencia ≥ RD$150,000 | `ElegibilidadService::validarSolvenciaRT9` |

El job de caducidad (RN-07) se programa en [`routes/console.php`](routes/console.php)
para correr a diario a la 01:00. Para ejecutarlo manualmente:

```bash
php artisan solicitudes:caducar
```

## Endpoints (`/core/v1`)

```
POST   /personas                         GET /personas?documento=&tipo=&nacionalidad=
GET    /personas/{id}
GET    /personas/{id}/expedientes        GET /personas/{id}/documentos
GET    /expedientes/{id}
POST   /solicitudes                      GET /solicitudes?persona_id=&expediente_id=&estado=
GET    /solicitudes/{id}                 GET /solicitudes/{id}/elegibilidad
POST   /solicitudes/{id}/transicion
POST   /solicitudes/{id}/adjuntos
PUT    /solicitudes/{id}/adjuntos/{adjuntoId}/validar
DELETE /solicitudes/{id}/adjuntos/{adjuntoId}
GET    /ordenes-pago/{id}
POST   /pagos                            (con Idempotency-Key)
GET    /pagos/{id}
POST   /movimientos                      GET /movimientos?persona_id=
POST   /documentos/emitir                GET /documentos/{numero_serie}/verificar
POST   /documentos/{id}/revocar          POST /documentos/{id}/reponer
GET    /calculos/tasa-estadia?persona_id=&fecha_salida=
GET    /calculos/penalidad?fecha_vencimiento=&fecha_calculo=
GET    /catalogos/servicios|tarifas|categorias|puntos-control
```

- `POST /movimientos` con `tipo=S` (salida) calcula la sobreestadía y, si existe, la
  incluye en la respuesta.
- `POST /pagos` concilia la orden de pago, genera el `numero_comprobante` y avanza la
  solicitud a `PAGADA`.
- `PUT .../adjuntos/{id}/validar` marca un adjunto como validado (o retira la
  validación); es la base de RN-08 (renovación exige PÓLIZA validada).
- `POST /documentos/{id}/revocar` deja el documento `REVOCADO` (verifica como no
  válido). `POST /documentos/{id}/reponer` marca el original `REPUESTO` y emite uno
  nuevo con serie propia. Ambos exigen que el documento esté `VIGENTE`.
- `GET /solicitudes/{id}/elegibilidad` evalúa las reglas aplicables al servicio de
  la solicitud (RN-03/05/08/10 y el indicador RN-09) sin modificar nada; devuelve
  `elegible` y el detalle por regla. Qué reglas aplican a qué servicios se
  configura en `config/dgm.php` (`elegibilidad`).
- `DELETE .../adjuntos/{id}` elimina un adjunto cargado por error; uno validado no
  puede eliminarse sin antes retirar la validación.

## Ejemplos de llamadas

Hay una colección lista para usar en [`docs/ejemplos-api.http`](docs/ejemplos-api.http)
(compatible con la extensión *REST Client* de VS Code o el HTTP Client de IntelliJ).
Cubre el flujo completo: crear persona → crear solicitud → transiciones → pago →
adjuntos → emisión de documento → movimientos. Equivalentes con `curl`:

```bash
TOKEN=$(cat storage/integrador_token.txt)
BASE=http://localhost:8000/core/v1

# Crear persona
curl -s -X POST $BASE/personas \
  -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"tipo_documento":"PASAPORTE","numero_documento":"A1234567","nacionalidad":"USA",
       "nombres":"Jane","apellidos":"Doe","fecha_nacimiento":"1992-03-04",
       "pasaporte_vence":"2030-01-01"}'

# Registrar un pago (idempotente por Idempotency-Key)
curl -s -X POST $BASE/pagos \
  -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -H "Idempotency-Key: idem-001" \
  -d '{"orden_pago_id":"<uuid>","monto":"9500.00","metodo":"EFECTIVO"}'

# Calcular tasa de estadía
curl -s "$BASE/calculos/tasa-estadia?persona_id=<uuid>&fecha_salida=2026-02-20" \
  -H "Authorization: Bearer $TOKEN"
```

## Roles y usuarios sembrados

| Rol | Usuario | Clave |
|-----|---------|-------|
| Administrador DGM | `admin@dgm.gob.do` | `secret123` |
| Analista de Extranjería | `analista@dgm.gob.do` | `secret123` |
| Auditor | `auditor@dgm.gob.do` | `secret123` |
| Integrador (API) | `integrador@dgm.gob.do` | token en `storage/integrador_token.txt` |

> Estas credenciales son solo para desarrollo. Cámbialas antes de cualquier despliegue.

## Pruebas

```bash
php artisan test          # o: composer test
```

Las pruebas corren sobre la base `dgm_core_testing` en MySQL (ver `phpunit.xml`).
Créala una sola vez si no existe:

```bash
mysql -uroot -e "CREATE DATABASE IF NOT EXISTS dgm_core_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

La suite cubre los servicios de cálculo (estadía, tarifa, penalidad), la secuencia
de numeración, la emisión de documentos, la caducidad RN-07 y un flujo de API de
extremo a extremo (`tests/Feature/ApiFlujoTest.php`).

## Desarrollo y mantenimiento

- **Estilo de código** (Laravel Pint): `vendor/bin/pint` (usa `--test` para solo verificar).
- **Logs en vivo** (Laravel Pail): `php artisan pail`.
- **Consola interactiva** (Tinker): `php artisan tinker`.
- **Regenerar el OpenAPI**: `php artisan scramble:export`.

Toda escritura que toca varias tablas se hace dentro de una transacción y el dinero
se maneja siempre con `bcmath` a través de `App\Support\Dinero` (escala fija de 2
decimales); no se usan floats en ningún cálculo monetario.

## Estructura del proyecto

```
app/
  Console/Commands/   Comando solicitudes:caducar (RN-07)
  Http/
    Controllers/Api/  Controladores de la API /core/v1
    Middleware/        Correlación, JSON forzado, permisos, acceso a docs
    Requests/          Validación (Form Requests)
    Resources/         Serialización de salida (API Resources)
  Models/              Modelos Eloquent (algunos auditables)
  Services/            Lógica de negocio y reglas RN-01..RN-12
  States/Solicitud/    Máquina de estados de la solicitud
  Support/             Dinero (bcmath) y RespuestaProblema (problem+json)
config/dgm.php         Parámetros del dominio (plazos, montos, reglas)
database/
  migrations/          Esquema (incluye secuencias y auditoría)
  seeders/             Datos reales: catálogos, oficinas, roles, token
docs/ejemplos-api.http Colección de ejemplos de la API
routes/                api.php (/core/v1), web.php (docs), console.php (scheduler)
tests/Feature/         Pruebas de servicios y flujo de API
```
