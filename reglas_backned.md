```md
# Backend – Laravel API con Autenticación JWT

## 🎯 Objetivo
Desarrollar un backend **API-first** en Laravel, seguro, escalable y mantenible, encargado de exponer servicios REST para el frontend, manejar autenticación con JWT, lógica de negocio y persistencia de datos.

---

## ⚙️ Stack Tecnológico
- Framework: **Laravel (API)**
- PHP >= 8.2
- Base de datos: **MySQL**
- Autenticación: **JWT (JSON Web Tokens)**
- ORM: **Eloquent**
- Formato de datos: **JSON**
- Manejo de archivos: **multipart/form-data**

---

## 🧱 Arquitectura del Proyecto
El backend debe seguir una **arquitectura por capas**, separando responsabilidades:

- **Controllers**  
  Reciben las requests HTTP y devuelven responses JSON.  
  No contienen lógica de negocio.

- [x] Configure Swagger (L5-Swagger)
    - [x] Install and publish L5-Swagger
    - [x] Implement PHP 8 Attributes for API Documentation
    - [x] Generate API documentation
- [ ] Final Verification [/]
- **Services**  
  Contienen la lógica de negocio del sistema.

- **Models (Eloquent)**  
  Representan las entidades de la base de datos.

- **Requests (FormRequest)**  
  Manejan validaciones de entrada.

- **Middlewares**  
  Autenticación JWT, permisos y validaciones globales.

- **Resources**  
  Transforman los modelos en respuestas JSON consistentes.

---

## 📁 Estructura de Carpetas Recomendada

```

app/
├── Http/
│   ├── Controllers/
│   ├── Requests/
│   ├── Resources/
│   ├── Middleware/
├── Models/
├── Services/
├── Repositories/   (opcional)
└── Traits/

routes/
└── api.php

database/
├── migrations/
├── seeders/

```

---

## 🔐 Autenticación JWT

### 🔑 Concepto
La API es **stateless**.  
No se usan sesiones ni cookies.  
Cada request protegida debe enviar un **JWT**.

### 📌 Header requerido
```

Authorization: Bearer {token}

```

---

## 🔄 Flujo de Autenticación

1. El usuario envía email y contraseña
2. El backend valida las credenciales
3. Se genera un JWT
4. El token se retorna al frontend
5. El frontend envía el token en cada request protegida

---

## 📦 Endpoints de Autenticación

```

POST   /api/v1/auth/login
POST   /api/v1/auth/register
POST   /api/v1/auth/logout
POST   /api/v1/auth/refresh
GET    /api/v1/auth/me

```

---

## 🧩 Middlewares
- `jwt.auth` → protege rutas
- `jwt.refresh` → renueva tokens
- Middleware custom para roles y permisos

---

## 🧠 Reglas del JWT
- Tokens con expiración
- Refresh token habilitado
- Token inválido o expirado → HTTP 401
- Logout invalida el token
- Claves JWT almacenadas en `.env`

---

## 🔗 Convenciones de Endpoints
- API versionada
- Estilo REST
- Nombres en plural

Ejemplos:
```

/api/v1/users
/api/v1/pedidos
/api/v1/productos

````

---

## 📄 Formato de Respuesta Estándar

### ✅ Respuesta exitosa
```json
{
  "status": "success",
  "mensaje": "Operación realizada correctamente",
  "objeto": {}
}
````

### ❌ Respuesta de error

```json
{
  "status": "error",
  "mensaje": "Error de validación",
  "objeto": {}
}
```

---

## 🧪 Manejo de Errores

* 200 / 201 → éxito
* 401 → no autenticado
* 403 → sin permisos
* 422 → error de validación
* 500 → error interno
* No exponer errores sensibles en producción

---

## 🧠 Reglas del Proyecto

* **¡CRÍTICO! Base de Datos Primero:** Antes de crear cualquier endpoint o escribir lógica en el backend, revisa siempre la migración principal de la base de datos para entender la estructura, relaciones y nombres de columnas. Todo lo que hagas en el backend debe coincidir con la arquitectura de la BD definida.
* Controllers delgados
* Toda la lógica va en Services
* Un endpoint = una responsabilidad
* Endpoints separados para crear y editar
* Archivos/imágenes se envían separados del JSON
* Código limpio, corto y mantenible

---

## 🚀 Escalabilidad

* Preparado para nuevos módulos
* Fácil integración de colas, jobs y eventos
* Soporte para apps web y móviles
* Uso de variables de entorno

---

## 📌 Consideraciones Finales

* El backend **no renderiza vistas Blade**
* Toda comunicación es vía JSON
* Pensado para frontend moderno (SPA / Mobile)
