# Mejoras Futuras y Tareas Pendientes (Notificaciones)

Este documento guarda el contexto de las notificaciones ideales que se propusieron en la documentación original (`frontend_integracion_campanita.md`), pero que **aún no se han implementado** porque dependen de características lógicas que el sistema backend no tiene programadas al día de hoy (Agosto 2026).

Cuando se desarrollen dichas características, se debe crear la clase `Notification` correspondiente e inyectarla.

---

## 1. Módulo: Cancelación de Rutas / Desasignación
**Aplica a:** `Driver`
**Contexto:** Actualmente, el sistema permite reasignar un paquete pisando el `driver_id`, pero no hay un flujo explícito de "Cancelar Ruta".
**Lo que falta:** 
* Crear un endpoint para que el Coordinador cancele un paquete.
* Crear la notificación `PackageCancelled`.
* **Disparador:** `$driver->user->notify(new PackageCancelled($package));`

## 2. Módulo: Fin de Jornada / Cierre de Lote
**Aplica a:** `Coordinator`
**Contexto:** El coordinador necesita saber cuándo un conductor ha terminado el 100% de su trabajo para el día.
**Lo que falta:** 
* Una lógica que se evalúe cada vez que un paquete pase a estado `delivered`. Si es el último paquete del conductor para ese día, disparar la alerta. O bien, un botón en la app del conductor que diga "Finalizar Jornada".
* Crear la notificación `DriverFinishedRoute`.
* **Disparador:** `$coordinator->user->notify(new DriverFinishedRoute($driver));`

## 3. Módulo: Tareas Programadas (Cron Jobs) y Métricas
**Aplica a:** `Admin` / `Company Admin` / `Coordinator`
**Contexto:** Los administradores necesitan un resumen al final del día sobre cómo le fue a su empresa sin tener que estar entrando a generar reportes manualmente.
**Lo que falta:** 
* Configurar el programador de tareas de Laravel (Console Kernel / Schedule).
* Crear un Job (ej. `GenerateDailyStatsJob`) que corra todos los días a las 18:00 hrs.
* El Job debe calcular cuántos paquetes se entregaron, cuántos fallaron y agrupar la data.
* Crear la notificación `DailyMetricsReport`.
* **Disparador:** `$admin->notify(new DailyMetricsReport($stats));`

## 4. Alerta: Paquetes sin asignar
**Aplica a:** `Coordinator`
**Contexto:** Evitar que paquetes se queden olvidados en la bodega.
**Lo que falta:** 
* Un Cron Job que corra a las 15:00 hrs.
* Busque en la base de datos si existen paquetes con `status = 'pending'`.
* Crear la notificación `UnassignedPackagesWarning`.
* **Disparador:** Si hay `count > 0`, `$coordinator->user->notify(...)`.
