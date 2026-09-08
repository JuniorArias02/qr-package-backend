# Contexto del Proyecto: Gestión de Paquetes y Envíos (QRPackageApi)

## 📌 Descripción General
El sistema **QRPackageApi** es una plataforma backend para la gestión, asignación, rastreo y verificación de entregas de paquetes y envíos utilizando códigos QR. 

El modelo de negocio está diseñado con soporte **multitenant (multi-empresa)**, distinguiendo roles jerárquicos desde la super administración hasta los conductores/repartidores en campo.

---

## 🏗️ Roles de Usuario y Jerarquía
El sistema utiliza un esquema de roles definidos en la tabla `users`:
* **`super_admin`**: Administrador global de la plataforma con acceso total.
* **`company_admin`**: Administrador de una empresa específica.
* **`admin`**: Administrador operativo dentro de la empresa.
* **`coordinator`**: Coordinador de rutas/lotes de despacho. Asigna paquetes a los conductores.
* **`driver`**: Repartidor o motorizado en campo encargado de la entrega física.
* **`reporter`**: Perfil de consulta para generación de reportes y estadísticas.

---

## 🗄️ Estructura de la Base de Datos (`qrpackage`)

A continuación se detalla el esquema completo de la base de datos, organizado por dominio de negocio:

### 1. Entidades Principales del Negocio

#### `companies` (Empresas)
Almacena las empresas clientes que utilizan el sistema de envíos.
| Campo | Tipo | Nulable | Clave | Valor por Defecto | Notas |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `id` | bigint unsigned | No | PRI | Auto-increment | Identificador único |
| `name` | varchar(255) | No | | NULL | Nombre de la empresa |
| `tax_id` | varchar(255) | Sí | UNI | NULL | Identificación tributaria (NIT/RUT) |
| `address` | varchar(255) | Sí | | NULL | Dirección física |
| `phone` | varchar(255) | Sí | | NULL | Teléfono de contacto |
| `active` | tinyint(1) | No | | `1` | Estado activo (1) / inactivo (0) |
| `created_at` | timestamp | Sí | | NULL | Fecha de creación |
| `updated_at` | timestamp | Sí | | NULL | Última actualización |

#### `users` (Usuarios del Sistema)
Usuarios registrados en el sistema con asignación a empresa y rol especifico.
| Campo | Tipo | Nulable | Clave | Valor por Defecto | Notas |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `id` | bigint unsigned | No | PRI | Auto-increment | Identificador único |
| `company_id` | bigint unsigned | Sí | MUL | NULL | FK a `companies.id` |
| `name` | varchar(255) | No | | NULL | Nombre completo |
| `email` | varchar(255) | No | UNI | NULL | Correo electrónico de acceso |
| `email_verified_at` | timestamp | Sí | | NULL | Confirmación de email |
| `password` | varchar(255) | No | | NULL | Contraseña encriptada |
| `role` | enum | No | | `'driver'` | `'super_admin'`,`'company_admin'`,`'admin'`,`'coordinator'`,`'driver'`,`'reporter'` |
| `active` | tinyint(1) | No | | `1` | Estado de la cuenta |
| `profile_image` | varchar(255) | Sí | | NULL | Ruta a la foto de perfil |
| `remember_token` | varchar(100) | Sí | | NULL | Token de sesión guardada |
| `created_at` | timestamp | Sí | | NULL | |
| `updated_at` | timestamp | Sí | | NULL | |

#### `coordinators` (Coordinadores de Envíos)
Vínculo entre un usuario con rol de coordinador y su empresa correspondiente.
| Campo | Tipo | Nulable | Clave | Valor por Defecto | Notas |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `id` | bigint unsigned | No | PRI | Auto-increment | Identificador único |
| `user_id` | bigint unsigned | No | MUL | NULL | FK a `users.id` |
| `company_id` | bigint unsigned | Sí | MUL | NULL | FK a `companies.id` |
| `created_at` | timestamp | Sí | | NULL | |
| `updated_at` | timestamp | Sí | | NULL | |

#### `drivers` (Conductores / Repartidores)
Relaciona los repartidores con su empresa y el coordinador a cargo.
| Campo | Tipo | Nulable | Clave | Valor por Defecto | Notas |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `id` | bigint unsigned | No | PRI | Auto-increment | Identificador único |
| `user_id` | bigint unsigned | No | MUL | NULL | FK a `users.id` |
| `company_id` | bigint unsigned | Sí | MUL | NULL | FK a `companies.id` |
| `coordinator_id` | bigint unsigned | No | MUL | NULL | FK a `coordinators.id` |
| `active` | tinyint(1) | No | | `1` | Estado operativo del conductor |
| `created_at` | timestamp | Sí | | NULL | |
| `updated_at` | timestamp | Sí | | NULL | |

---

### 2. Operación de Envíos y Logística

#### `packages` (Paquetes)
Registro de paquetes individuales ingresados en el sistema para entrega.
| Campo | Tipo | Nulable | Clave | Valor por Defecto | Notas |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `id` | bigint unsigned | No | PRI | Auto-increment | Identificador único |
| `company_id` | bigint unsigned | Sí | MUL | NULL | FK a `companies.id` |
| `package_reference` | varchar(255) | No | UNI | NULL | Código de referencia único del paquete |
| `address` | varchar(255) | No | | NULL | Dirección de destino |
| `zone` | varchar(255) | No | | NULL | Zona / sector geográfico |
| `status` | enum | No | | `'pending'` | Estados: `'pending'`, `'assigned'`, `'delivered'` |
| `created_at` | timestamp | Sí | | NULL | |
| `updated_at` | timestamp | Sí | | NULL | |

#### `daily_batches` (Lotes Diarios de Envíos)
Agrupación de envíos por jornada y empresa.
| Campo | Tipo | Nulable | Clave | Valor por Defecto | Notas |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `id` | bigint unsigned | No | PRI | Auto-increment | Identificador único |
| `company_id` | bigint unsigned | Sí | MUL | NULL | FK a `companies.id` |
| `daily_code` | varchar(255) | No | UNI | NULL | Código de lote diario único |
| `date` | date | No | | NULL | Fecha de la operación |
| `created_at` | timestamp | Sí | | NULL | |
| `updated_at` | timestamp | Sí | | NULL | |

#### `deliveries` (Despachos y Entregas con QR)
Tabla central que une el paquete, el repartidor, el coordinador y la ruta.
| Campo | Tipo | Nulable | Clave | Valor por Defecto | Notas |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `id` | bigint unsigned | No | PRI | Auto-increment | Identificador único |
| `company_id` | bigint unsigned | Sí | MUL | NULL | FK a `companies.id` |
| `package_id` | bigint unsigned | No | MUL | NULL | FK a `packages.id` |
| `driver_id` | bigint unsigned | No | MUL | NULL | FK a `drivers.id` |
| `coordinator_id` | bigint unsigned | No | MUL | NULL | FK a `coordinators.id` |
| `daily_batch_id` | bigint unsigned | No | MUL | NULL | FK a `daily_batches.id` |
| `delivery_order` | int | No | | NULL | Orden de entrega en la ruta |
| `qr_url` | varchar(255) | Sí | | NULL | URL / contenido del código QR generado |
| `status` | enum | No | | `'assigned'` | Estados: `'assigned'`, `'delivered'` |
| `observation` | text | Sí | | NULL | Novedades u observaciones de la entrega |
| `assigned_at` | datetime | Sí | | NULL | Fecha y hora de asignación |
| `delivered_at` | datetime | Sí | | NULL | Fecha y hora de confirmación de entrega |
| `created_at` | timestamp | Sí | | NULL | |
| `updated_at` | timestamp | Sí | | NULL | |

#### `system_settings` (Configuración del Sistema)
Configuración del algoritmo de asignación.
| Campo | Tipo | Nulable | Clave | Valor por Defecto | Notas |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `id` | bigint unsigned | No | PRI | Auto-increment | Identificador único |
| `assignment_mode` | enum | No | | `'automatic'` | Modos: `'automatic'`, `'assign_to'` |
| `created_at` | timestamp | Sí | | NULL | |
| `updated_at` | timestamp | Sí | | NULL | |

---

### 3. Tablas de Infraestructura y Soporte de Laravel

* **`personal_access_tokens`**: Tokens de autenticación API (Laravel Sanitariun / Passport).
* **`sessions`**: Gestión de sesiones de usuario en base de datos.
* **`password_reset_tokens`**: Restablecimiento de contraseñas por email.
* **`jobs`**, **`job_batches`**, **`failed_jobs`**: Procesamiento de tareas en segundo plano (Colas).
* **`cache`**, **`cache_locks`**: Caché de la aplicación y bloqueos de concurrencia.
* **`migrations`**: Historial de migraciones ejecutadas.

---

## 🔄 Flujo de Trabajo y Relaciones (Diagrama Entidad-Relación Simplificado)

```
[ Company ] <--- 1:N ---> [ User ] <--- 1:1 ---> [ Coordinator ]
    |                        |                       |
    | 1:N                    +--- 1:1 ---> [ Driver ]|
    v                                          |     |
[ Package ]                                    |     |
    |                                          |     |
    +-----> [ Delivery ] <---------------------+-----+
                 ^
                 | 
         [ DailyBatch ]
```

### Flujo de Negocio:
1. **Registro e Ingesta de Paquetes**: Se reciben los paquetes asignados a una empresa (`packages`), con estado inicial `pending`.
2. **Creación de Lote Diario**: Se genera un lote para el día (`daily_batches`).
3. **Asignación de Ruta**: El coordinador (`coordinators`) o el sistema en modo `automatic` asigna los paquetes a los conductores (`drivers`) generando un registro en `deliveries` con estado `assigned` y un código QR (`qr_url`).
4. **Despacho y Confirmación**: El repartidor escanea o liquida la entrega con el código QR, registrando la fecha/hora en `delivered_at` y actualizando el estado de la entrega a `delivered`.
