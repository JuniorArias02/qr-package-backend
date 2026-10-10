# Lógica de Negocio: Asignación de Rutas y Balanceo de Carga

Este documento explica cómo el sistema de Paquetes QR maneja la asignación automática de paquetes a los conductores en tiempo real, basándose en geolocalización y carga de trabajo equitativa.

## 1. Detección Geoespacial (Geofencing)

Cuando un paquete ingresa al sistema y la API de Geoapify determina sus coordenadas (Latitud y Longitud), el algoritmo intenta ubicar automáticamente en qué zona de la ciudad se encuentra.

El sistema cruza el punto (Lat, Lng) contra todos los **Polígonos Espaciales** (Rutas) guardados en la base de datos utilizando la función geoespacial nativa de MySQL: `ST_Contains()`.

* **Regla:** El paquete solo será evaluado por los conductores de la **única ruta activa** que cubra ese polígono específico.
* Si el paquete no cae en ningún polígono dibujado, se marcará como Excepción (`pending`) y requerirá Asignación Manual por parte de un coordinador.

## 2. Balanceo de Carga (Load Balancing Equitativo)

Una vez que el sistema determina que el paquete pertenece a una ruta específica (ej. "Zona Norte"), procede a asignarlo a un conductor. 

Si la ruta tiene **múltiples conductores asignados**, el sistema ejecuta un algoritmo de balanceo para garantizar que el trabajo se distribuya de manera 100% equitativa.

### ¿Cómo funciona la matemática?
Por cada paquete nuevo que ingresa, el sistema hace una consulta rápida para contar la "carga viva" de cada conductor. La carga viva se define como: **Número de entregas activas (`status = assigned`) asignadas al conductor en el día actual.**

El algoritmo selecciona siempre al conductor que tenga el **número menor (`minLoad`)**.

#### Ejemplo Práctico:
Asumamos que hay 3 conductores asignados a la Zona Norte: **Juan, Pedro y María**.

1. **Paquete 1:** Juan (0), Pedro (0), María (0). 
   * *Resultado:* Empate absoluto. El sistema elige al primero (Juan).
2. **Paquete 2:** Juan (1), Pedro (0), María (0). 
   * *Resultado:* Pedro y María tienen menos carga. El sistema elige a Pedro.
3. **Paquete 3:** Juan (1), Pedro (1), María (0). 
   * *Resultado:* María tiene menos carga, se le asigna a ella.
4. **Paquete 4:** Juan (1), Pedro (1), María (1).
   * *Resultado:* Todos están empatados nuevamente. El ciclo se reinicia y se le da a Juan.

### Casos Especiales en Tiempo Real
* **Liberación de Carga:** Si Juan sale a ruta temprano y entrega sus paquetes exitosamente (estado pasa a `delivered`), su "carga viva" vuelve a caer. Si los demás conductores siguen estancados con sus entregas sin resolver, el sistema notará que Juan está "libre" e inyectará los próximos paquetes directamente a él para mantener el balance global.
* **Conductores Inactivos:** El algoritmo filtrará y solo tomará en cuenta a los conductores que tengan la propiedad `active = true` en su relación con la ruta.

## 3. Resumen Técnico (Flujo de Código)
1. Job de Background (`GeocodePackageJob`) -> Saca lat/lng.
2. Job de Background (`AssignPackageToRouteJob`) -> Inicia asignación.
3. Servicio (`PackageAssignmentService`) -> Ejecuta `ST_Contains` y cuenta los `Deliveries` activos de los conductores.
4. Se crea un registro en `Delivery` y el paquete queda en `assigned`.
