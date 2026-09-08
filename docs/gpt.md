# Solicitud de mejora de arquitectura para QRPackageApi

Hola Gemini,

Necesito que revises y mejores la arquitectura actual del proyecto **QRPackageApi** teniendo en cuenta los siguientes requerimientos de negocio. La idea no es únicamente modificar el código, sino también proponer y ejecutar las migraciones necesarias para que el modelo de datos soporte correctamente el flujo logístico.

## Contexto

Actualmente el sistema registra paquetes, asigna conductores y genera un código QR para la entrega.

Sin embargo, se desea evolucionar el sistema para que la asignación de rutas sea automática utilizando coordenadas geográficas obtenidas desde la dirección del destinatario.

El objetivo es que el sistema pueda crecer y ser escalable, evitando depender de zonas escritas manualmente.

---

# Problemas identificados en el modelo actual

## 1. La dirección solo se almacena como texto

Actualmente la tabla `packages` únicamente almacena:

* address
* zone

Esto presenta varios problemas.

Ejemplos:

* "San Luis"
* "Barrio San Luis"
* "SAN LUIS"
* "San luis"

Todas representan el mismo lugar, pero para el sistema son valores diferentes.

Esto hace que la asignación automática sea poco confiable.

---

## 2. No existen coordenadas geográficas

Actualmente no existe ningún campo para almacenar:

* latitud
* longitud

Sin coordenadas no es posible:

* calcular distancias
* optimizar rutas
* asignar automáticamente un conductor
* determinar qué ruta corresponde a un paquete

---

## 3. El sistema depende de una zona escrita manualmente

La columna `zone` debería dejar de ser la fuente principal para decidir la ruta.

En su lugar, el sistema debería trabajar utilizando coordenadas obtenidas mediante un servicio de geocodificación (Geoapify inicialmente).

La zona puede mantenerse únicamente como información descriptiva o mostrarse en la interfaz, pero no debería utilizarse como criterio principal para la lógica del negocio.

---

# Cambios solicitados

## Opción recomendada (preferida)

Crear una nueva tabla llamada `addresses`.

Ejemplo conceptual:

* id
* full_address
* latitude
* longitude
* provider
* geocoded_at
* created_at
* updated_at

Luego modificar `packages` para que tenga una relación con esta tabla mediante:

* address_id

De esta manera:

* varias entregas hacia la misma dirección reutilizan las mismas coordenadas
* no se consulta la API de geocodificación varias veces
* se reducen costos
* se mejora el rendimiento

Antes de llamar a Geoapify el sistema debe buscar si la dirección ya existe en la tabla.

Si existe:

* reutilizar las coordenadas.

Si no existe:

* consultar Geoapify
* guardar la dirección y sus coordenadas
* reutilizar esa información en futuras entregas.

---

## Alternativa

Si consideras que una tabla independiente no aporta ventajas importantes para este proyecto, puedes justificar técnicamente por qué y, en ese caso, agregar directamente a `packages` los siguientes campos:

* latitude
* longitude
* geocoding_status
* geocoded_at

Pero la prioridad es reutilizar coordenadas y evitar llamadas repetidas a la API.

---

# Integración con Geoapify

No quiero que la llamada a Geoapify quede acoplada al controlador.

Quiero una arquitectura desacoplada.

Por ejemplo:

Controller

↓

Application Service

↓

GeoCodingService (interfaz)

↓

GeoapifyProvider (implementación)

↓

API Geoapify

La idea es que en el futuro sea sencillo cambiar Geoapify por Google Maps, Here Maps u otro proveedor sin modificar la lógica del negocio.

---

# Flujo esperado

El flujo completo debería ser aproximadamente:

1. Registrar paquete.

2. Validar que la referencia del paquete no exista.

3. Buscar la dirección.

4. Verificar si ya existen coordenadas almacenadas.

5. Si no existen:

   * consultar Geoapify
   * guardar coordenadas

6. Crear el paquete.

7. Intentar asignar automáticamente una ruta.

8. Intentar asignar automáticamente un conductor.

9. Crear el Delivery.

10. Generar el código QR.

11. Guardar el QR.

Es importante que la generación del QR ocurra al final del proceso.

---

# Sistema de rutas

Actualmente no existe un concepto de rutas.

Analiza si es conveniente agregar una estructura similar a:

routes

* id
* company_id
* name
* active
* created_at
* updated_at

y otra tabla como:

route_points

* id
* route_id
* latitude
* longitude
* radius

La finalidad es que un paquete pueda determinar automáticamente a qué ruta pertenece utilizando sus coordenadas.

No necesariamente debes implementar exactamente este diseño.

Puedes proponer uno mejor si encuentras una solución más adecuada.

Lo importante es que la asignación de rutas no dependa de texto escrito manualmente.

---

# Cambios en Deliveries

Actualmente la tabla obliga a tener un conductor.

Sin embargo, el negocio contempla este escenario:

"No siempre existirá un conductor disponible al momento de registrar el paquete."

Por lo tanto, necesito que analices y modifiques el modelo para soportar entregas pendientes de asignación.

Idealmente:

* driver_id debería permitir NULL.

Además, ampliar los estados de la entrega.

Actualmente existen:

* assigned
* delivered

Sería recomendable manejar algo similar a:

* pending_assignment
* assigned
* in_route
* delivered
* failed

Puedes proponer otros estados si consideras que mejoran el flujo.

---

# Migraciones

Quiero que seas tú quien genere todas las migraciones necesarias.

Incluyendo:

* creación de nuevas tablas
* modificación de columnas existentes
* nuevas claves foráneas
* índices
* restricciones
* cualquier cambio requerido para mantener la integridad del modelo

También debes actualizar los modelos, relaciones y cualquier parte del código afectada.

---

# Objetivo

No busco únicamente agregar coordenadas.

Busco evolucionar el sistema para que en el futuro pueda:

* asignar rutas automáticamente
* optimizar recorridos
* reutilizar coordenadas
* reducir llamadas externas
* desacoplar el proveedor de geocodificación
* permitir cambiar fácilmente de proveedor
* soportar crecimiento del sistema sin tener que rediseñar la arquitectura nuevamente

Si durante el análisis encuentras una solución mejor que la planteada aquí, puedes implementarla, siempre y cuando mantenga estos objetivos de escalabilidad, mantenibilidad y buenas prácticas de arquitectura.
