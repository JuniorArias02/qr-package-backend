# Convenciones de Código y API

Para mantener el código limpio, legible y profesional, este proyecto sigue estrictamente las siguientes convenciones.

## 1. Regla del Idioma
**El Código se escribe en Inglés**: Aunque estas reglas están redactadas en español para entendimiento del equipo, TODO el código debe escribirse en **Inglés**.
* Las variables, funciones, nombres de clases, nombres de archivos, tablas de la base de datos, columnas y rutas de la API deben estar obligatoriamente en inglés.
* Los comentarios en el código pueden estar en español si lo prefiere el equipo, pero el código fuente en sí debe ser en inglés.

## 2. Convenciones de Nombres
* **Clases** (Controllers, Models, Services): `PascalCase` (ej. `DeliveryService`, `AssignPackageController`).
* **Métodos y Funciones**: `camelCase` (ej. `calculateDailyOrder`, `assignDriver`).
* **Variables y Propiedades**: `camelCase` (ej. `dailyCode`, `packageReference`).
* **Tablas de Base de Datos**: Plural en `snake_case` (ej. `daily_batches`, `deliveries`).
* **Columnas de Base de Datos**: Singular en `snake_case` (ej. `delivery_order`, `assigned_at`).
* **Rutas / Endpoints API**: Sustantivos en plural y en `kebab-case` (ej. `/api/v1/daily-batches`).

## 3. Principios de Código Limpio (Clean Code)
* **Base de Datos Primero (CRÍTICO)**: Antes de crear lógica o endpoints, revisa siempre la estructura de la base de datos (migraciones). Toda tu lógica backend debe coincidir perfectamente con cómo está armada la BD.
* **Controladores Delgados, Servicios Gruesos**: Un Controller no debería tener más de 4-5 líneas de código. Su único trabajo es: validar la petición, llamar a un Servicio y retornar un Recurso. Toda la lógica compleja va al Servicio.
* **Principio de Responsabilidad Única (SRP)**: Cada función o clase debe hacer **solo una cosa**. Si una función está validando, calculando rutas y guardando en BD, hay que dividirla.
* **No usar Números/Strings Mágicos**: Usa Constantes o Enums para estados fijos (ej. usar `DeliveryStatus::PENDING` en lugar de un string suelto `'pending'`).
* **Retorno Temprano (Early Return)**: Evita los `if/else` anidados o muy profundos. Si algo falla o no se cumple una condición inicial, retorna el error inmediatamente.

## 4. Estándares de la API REST
* **Versionamiento**: Siempre versionar la API (ej. `/api/v1/...`).
* **Usar Sustantivos, NO Verbos**: Las rutas representan recursos, no acciones. 
  * ✅ Correcto: `POST /api/v1/packages`
  * ❌ Incorrecto: `POST /api/v1/create-package`
* **Métodos HTTP correctos**: 
  * `GET` para consultar/leer.
  * `POST` para crear.
  * `PUT`/`PATCH` para actualizar.
  * `DELETE` para eliminar.

## 5. Formato Estándar de Respuesta JSON
Todas las respuestas de la API deben seguir la misma estructura. Nunca devuelvas arreglos crudos, booleanos sueltos o strings directos.

### Respuesta Exitosa (200, 201)
```json
{
  "status": "success",
  "message": "Operación realizada correctamente.",
  "data": { ... }
}
```

### Respuesta de Error (400, 401, 403, 404, 422, 500)
```json
{
  "status": "error",
  "message": "Error de validación.",
  "errors": {
     "email": ["El campo email es requerido."]
  }
}
```

## 6. Manejo de Errores
Nunca se deben exponer errores internos del sistema (stack traces) en producción.
* `200 OK` / `201 Created` -> Éxito.
* `401 Unauthorized` -> No autenticado (falta token JWT o es inválido/expirado).
* `403 Forbidden` -> Autenticado, pero el usuario no tiene permisos para esa acción.
* `404 Not Found` -> El recurso buscado no existe en la base de datos.
* `422 Unprocessable Entity` -> Error de validación del FormRequest (datos incorrectos enviados por el cliente).
* `500 Internal Server Error` -> Error grave o inesperado en el servidor.
