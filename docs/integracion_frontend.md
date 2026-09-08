# Guía de Integración para el Frontend (Actualización Logística)

Hola equipo de Frontend 👋. Recientemente el backend de QRPackageApi recibió una actualización arquitectónica mayor para soportar **asignación automática por geolocalización (Geoapify)** y **entregas pendientes de asignación**.

Este documento detalla los nuevos flujos, los endpoints actualizados y unas preguntas clave para alinear la vista (UI) con estos nuevos súper poderes del backend.

---

## 1. Actualización en el Flujo de Entregas (Deliveries)

El cambio más importante es que **ya no es obligatorio tener un motorizado (conductor) disponible** al momento de registrar un paquete en la bodega. 

Si el paquete entra, se geolocaliza automáticamente su dirección y se le busca una ruta. Si no hay conductor, la Entrega (`Delivery`) se crea de todas formas con `driver_id = null` y su estado queda en `'pending_assignment'`.

### Endpoint: Registrar Paquete
* **Ruta**: `POST /api/v1/delivery/register-package`
* **Novedad**: El campo `driver_id` en el body es opcional.

**Request (Ejemplo):**
```json
{
    "package_reference": "PKG-2026-001",
    "address": "Calle 100 #15-20, Bogotá",
    "driver_id": null 
}
```

**Response (201 Created - Sin conductor disponible):**
```json
{
    "status": "success",
    "mensaje": "Package successfully processed. Assigned to: ninguno (Pendiente de Asignación)",
    "objeto": {
        "delivery": {
            "id": 45,
            "company_id": 1,
            "package_id": 12,
            "driver_id": null,
            "route_id": 2, 
            "status": "pending_assignment",
            "qr_url": "/delivery/45",
            "assigned_at": null
        },
        "assignment_mode": "automatic",
        "label_image": "data:image/svg+xml;base64,...",
        "day_identifier": "20260319",
        "order_number": 0
    }
}
```

### Endpoint: Asignación Manual Posterior
Cuando el coordinador decida a quién darle el paquete que estaba pendiente, llamará al endpoint de asignación que ya existía, pero el backend ahora es lo suficientemente inteligente para **actualizar la entrega existente** en lugar de duplicarla o dar error.

* **Ruta**: `POST /api/v1/delivery/assign`

**Request:**
```json
{
    "package_id": 12,
    "driver_id": 5
}
```

**Response (200 OK):**
```json
{
    "status": "success",
    "mensaje": "Package successfully assigned to Carlos (Motorizado)",
    "objeto": {
        "delivery": {
            "id": 45,
            "driver_id": 5,
            "status": "assigned",
            "assigned_at": "2026-03-19T10:30:00.000000Z"
        },
        "assignment_mode": "manual"
    }
}
```

---

## 2. Nuevos Endpoints: Gestión de Rutas Espaciales

Para que el backend sepa asignar paquetes automáticamente, los Coordinadores o Administradores deben crear **Rutas** y definirles **Puntos de Geocerca (Latitud, Longitud y Radio en KM)**.

### A. Listar Rutas
* **Ruta**: `GET /api/v1/routes`
* **Descripción**: Trae las rutas de la empresa activa junto con sus puntos de geocerca (`points`).

**Response (200 OK):**
```json
{
    "status": "success",
    "message": "Routes retrieved successfully",
    "data": [
        {
            "id": 1,
            "name": "Zona Norte Central",
            "active": true,
            "points": [
                {
                    "id": 1,
                    "latitude": 4.60971,
                    "longitude": -74.08175,
                    "radius_km": 3.5
                }
            ]
        }
    ]
}
```

### B. Crear una Ruta (Con o sin puntos iniciales)
* **Ruta**: `POST /api/v1/routes`

**Request:**
```json
{
    "name": "Zona Sur",
    "description": "Cobertura de toda la zona sur de la ciudad",
    "active": true,
    "points": [
        {
            "latitude": 4.570868,
            "longitude": -74.125890,
            "radius_km": 2.5
        }
    ]
}
```
*(Retorna un 201 Created con los datos de la ruta recién creada).*

### C. Agregar un Punto a una Ruta Existente
* **Ruta**: `POST /api/v1/routes/{id}/points`

**Request:**
```json
{
    "latitude": 4.600100,
    "longitude": -74.100200,
    "radius_km": 1.5
}
```

---

## ❓ Preguntas para el equipo Frontend (Para alinear el desarrollo):

Para asegurar que todo conecte perfecto, me gustaría que me confirmes lo siguiente:

1. **Interfaz de Entregas Pendientes**: Ya que ahora tenemos entregas en estado `"pending_assignment"` (sin conductor), ¿tienen pensada una pestaña, tabla o vista específica tipo "Bodega / Sin Asignar" para que el coordinador arrastre o seleccione estos paquetes y se los asigne a un motorizado?
2. **Creación de Rutas con Mapa**: Para los endpoints de `POST /api/v1/routes`, ¿piensan integrar algún componente visual de mapa (como Leaflet o Google Maps) donde el admin pueda hacer clic para obtener la `latitude` y `longitude`, y ajustar visualmente el `radius_km`? Si es así, ¿el JSON que les devuelve el backend actual les es suficiente para dibujar esos círculos de cobertura en el mapa?
3. **Flujo de QR**: Para los paquetes que queden sin asignar al inicio, el identificador de orden (`order_number`) viaja en 0 temporalmente. ¿El diseño de la etiqueta QR que imprimen soporta indicar "Sin Asignar" visualmente hasta que se haga el `assign`?

¡Quedo muy atento a sus respuestas o feedback para hacer ajustes en el backend si lo necesitan! 🚀
