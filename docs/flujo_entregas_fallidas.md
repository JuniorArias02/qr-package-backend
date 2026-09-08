# Flujo de Entregas Fallidas y Caducidad Automática (24h)

Este documento documenta el ciclo de vida de un paquete cuando no puede ser entregado con éxito, ya sea por un reporte manual del motorizado o por inactividad. También explica el proceso para re-encolar (reciclar) estos paquetes en el flujo logístico.

---

## 1. ¿Cómo falla una entrega?

Existen dos escenarios mediante los cuales una entrega (Delivery) pasa al estado `failed` (Fallida):

### A. Reporte Manual del Motorizado
Es el flujo ideal. El conductor llega al destino, se presenta un inconveniente (ej. "Dirección incorrecta", "Nadie recibe") y usa su aplicación móvil para reportarlo.
- **Endpoint:** `POST /api/v1/delivery/{id}/delivered`
- **Payload esperado:** `{"status": "failed", "observation": "Cliente no estaba"}`
- **Resultado:** La entrega cambia a `status = failed`, se guarda la `observation` y el coordinador recibe una notificación push/email.

### B. Expiración Automática por Inactividad (24 horas)
Para evitar que un paquete se quede "en el limbo" (estado `assigned`) si el motorizado olvida reportarlo o se le daña el celular, el sistema cuenta con un **Cron Job** programado.
- **Comando:** `php artisan deliveries:expire` (Ubicado en `app/Console/Commands/ExpireDeliveriesCommand.php`).
- **Frecuencia:** Se ejecuta cada hora de forma automática.
- **Resultado:** Busca entregas que lleven más de 24 horas en estado asignado. Si encuentra alguna, cambia su estado a `failed`, establece como observación *"Expirado automáticamente: Tiempo de entrega (24h) superado sin reporte del motorizado"* y notifica al coordinador.

> ⚙️ **Requisito de Infraestructura (DevOps):**
> Para que el sistema de caducidad automática funcione, el servidor de producción (Ubuntu/Debian) debe tener configurado el Cron de Laravel ejecutando `crontab -e`:
> ```bash
> * * * * * cd /ruta/absoluta/a/QRPackageApi && php artisan schedule:run >> /dev/null 2>&1
> ```

---

## 2. Recuperación: Reencolar (Reciclar) el Paquete

Cuando el paquete físico finalmente regresa a la bodega y la incidencia ha sido evaluada por el coordinador, el paquete debe estar disponible para salir nuevamente a ruta. 

Dado que el sistema usa un modelo de *Máquina de Estados* (Relación 1 a 1 entre Package y Delivery), la forma correcta de volver a despacharlo es **reiniciar** la entrega actual para devolverla al "pool" de paquetes pendientes.

- **Endpoint:** `POST /api/v1/delivery/{id}/requeue`
- **¿Qué hace?**
  1. Limpia el `driver_id` (lo vuelve `null`).
  2. Borra la `observation` del fallo.
  3. Cambia el `status` de la entrega nuevamente a `pending_assignment`.
  4. Sincroniza el estado del paquete padre a `en_espera`.

Una vez ejecutado este endpoint, el paquete reaparecerá instantáneamente en la pantalla de **Batch Dispatch**, listo para ser agrupado y asignado a un nuevo conductor para un nuevo intento.

---

## 3. Checklist de Integración para Frontend

Para que la vista de gestión administrativa esté completa, el equipo de Frontend (Coordinador) debe implementar lo siguiente:

1. **Crear Vista de Incidencias:** Construir una tabla (Ej: "Paquetes Fallidos") que consulte las entregas en estado `failed` o que posean una observación registrada.
2. **Botón de Acción:** Mostrar un botón rojo o naranja junto a cada entrega llamado **"Devolver a Bodega"** o **"Reencolar"**.
3. **Consumo de Servicio:** Al presionar el botón, consumir el endpoint `/requeue` enviando únicamente el ID por URL.
4. **Respuesta UI:** Mostrar un mensaje de éxito (toast) y remover la fila de la tabla (ya que el paquete acaba de regresar a la pantalla de pendientes).
