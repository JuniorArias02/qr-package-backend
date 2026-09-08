# Recomendaciones y Buenas Prácticas

Para asegurar que el backend del sistema de Paquetes QR se mantenga robusto, escalable y fácil de mantener a lo largo del tiempo, sigue estas recomendaciones durante el desarrollo.

## 1. Confiabilidad en la Base de Datos
* **Claves Foráneas Estrictas**: Siempre protege la integridad de los datos usando `foreign keys` a nivel de base de datos en tus migraciones.
* **Transacciones (DB Transactions)**: Cuando un proceso modifique más de una tabla a la vez (por ejemplo, crear una Entrega implica generar un código diario en una tabla, actualizar el paquete en otra tabla y crear el registro de entrega), envuelve todo usando `DB::transaction()`. Si algún paso falla a la mitad, todo se revierte (rollback) y no quedan datos sueltos o corruptos.

## 2. Documentación de la API
* **Swagger/OpenAPI**: Mantén la documentación de la API 100% actualizada utilizando atributos de PHP 8 (vía el paquete `L5-Swagger`).
* Documenta cada endpoint nuevo: los headers requeridos (como el token JWT), los parámetros del body y el formato exacto del JSON que retorna. El equipo de Frontend y el que programa las Máquinas de QR dependen totalmente de esta documentación para poder conectar.

## 3. Rendimiento y Escalabilidad (Performance)
* **Eager Loading (Carga Temprana)**: Evita el problema clásico de las consultas N+1 en Laravel utilizando la función `with()` cuando traigas modelos que tienen relaciones (ej. `Delivery::with(['driver', 'package'])->get()`).
* **Colas para Tareas Pesadas (Queues)**: Si un endpoint envía correos electrónicos, genera PDFs pesados o se comunica con APIs externas lentas, envía esa tarea a un Job / Queue de Laravel en segundo plano para que el usuario o la máquina QR no tengan que quedarse esperando.
* **Índices de Base de Datos**: Asegúrate de que las columnas por las que se busca muy frecuentemente (como `package_reference` o `daily_code`) tengan índices (index) agregados en las migraciones de Laravel.

## 4. Seguridad
* **JWT**: Mantén un tiempo de expiración corto para los tokens de acceso y usa un mecanismo de Refresh Token para mantener las sesiones de los conductores seguras.
* **Asignación Masiva (Mass Assignment)**: Protege siempre los modelos usando la propiedad `$fillable`. Nunca uses `$guarded = []` a menos que controles el 100% de la data que entra al modelo y no dependas del input del cliente.
* **Validar Todo**: Jamás confíes en la información que envía el cliente (sea web, app o la máquina). Cada petición entrante debe pasar rigurosamente por las reglas de validación de una clase `FormRequest` de Laravel.

## 5. Organización del Código
* **Uso de Enums**: A partir de PHP 8.1, usa Enums nativos para los estados (ej. `DeliveryStatus::ASSIGNED`, `UserRole::ADMIN`) en lugar de strings sueltos esparcidos por el código. Aportan seguridad y son autocompletables en el editor.
* **Clases de Acción (Action Classes)**: Si un Servicio empieza a tener demasiado código y métodos, y se vuelve difícil de leer, considera separarlo en clases de "Acción" que tengan una sola responsabilidad (ej. crear una clase `CreateDeliveryAction` o `GenerateDeliveryQrAction`).
