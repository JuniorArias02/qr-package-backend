# Stack Tecnológico

Este proyecto está construido sobre el ecosistema moderno de PHP, diseñado para ser un backend API rápido, seguro y escalable.

## Core (Tecnologías Principales)
* **Framework**: [Laravel](https://laravel.com/) (Configurado puramente como API, sin vistas Blade).
* **Lenguaje**: PHP 8.2+
* **Base de Datos**: MySQL

## Paquetes y Librerías Clave
* **Autenticación**: [JWT-Auth](https://github.com/PHP-Open-Source-Saver/jwt-auth) (`php-open-source-saver/jwt-auth`) - Se usa para emitir tokens seguros (JSON Web Tokens) porque la API es "stateless" (sin estado, no guarda sesiones en el servidor).
* **Documentación API**: [L5-Swagger](https://github.com/DarkaOnLine/L5-Swagger) (`darkaonline/l5-swagger`) - Genera la documentación Swagger/OpenAPI automáticamente leyendo Atributos de PHP 8 en los controladores.
* **Generación de QR**: [Simple QrCode](https://github.com/SimpleSoftwareIO/simple-qrcode) (`simplesoftwareio/simple-qrcode`) - Librería utilizada para generar el QR final de la Entrega (Delivery QR).
* **Entorno Local**: Laravel Sail (Docker) para levantar el proyecto y la base de datos sin necesidad de instalar servicios en la PC de desarrollo.

## Herramientas de Desarrollo y Testing
* **Formateo de Código**: Laravel Pint.
* **Pruebas (Testing)**: PHPUnit / Pest (configurado vía `phpunit.xml`).
* **Mocking**: Mockery.
* **Análisis y Debug**: Laravel Telescope y Collision.

## Decisiones de Arquitectura
* **Sin Estado (Stateless)**: Al usar JWT, cada petición HTTP es independiente. Esto es ideal ya que esta API sirve a diferentes clientes (Máquinas de QR, App Web de Administración, App Móvil para conductores). No se usan cookies de sesión tradicionales (Sanctum SPA).
* **ORM**: Eloquent ORM se usa para toda la interacción con la base de datos. No se recomienda usar Query Builder directo a menos que sea una consulta extraordinariamente compleja o masiva.
* **Formato Universal**: Todo dato que entra (Requests) y todo dato que sale (Responses) es en formato JSON puro.
