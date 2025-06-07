# Sistema de Tickets - Strong Shoes

Sistema de gestión de tickets para la zapatería Strong Shoes. Permite crear, editar, ver y eliminar pedidos de calzado, así como generar tickets en PDF.

## Requisitos

- PHP 7.4 o superior
- MySQL 5.7 o superior
- Composer
- Servidor web (Apache/Nginx)

## Instalación

1. Clonar el repositorio en tu servidor web:
```bash
git clone <url-del-repositorio>
cd ticket
```

2. Instalar las dependencias con Composer:
```bash
composer install
```

3. Configurar la base de datos:
   - Crear una base de datos MySQL llamada `strong_shoes`
   - Editar el archivo `config/database.php` con tus credenciales de MySQL

4. Asegurarse de que el directorio del proyecto tenga los permisos correctos:
```bash
chmod -R 755 .
chmod -R 777 assets/uploads
```

5. Acceder al sistema a través del navegador:
```
http://localhost/ticket
```

## Características

- Gestión de pedidos de calzado
- Registro de tallas por tipo de calzado (caballero, dama, niño, juvenil)
- Generación de tickets en PDF
- Búsqueda avanzada de pedidos
- Interfaz moderna y responsiva

## Estructura de Directorios

```
ticket/
├── assets/          # Archivos estáticos (imágenes, etc.)
├── config/          # Configuración de la base de datos
├── includes/        # Archivos PHP reutilizables
├── vendor/         # Dependencias de Composer
├── composer.json   # Configuración de Composer
└── README.md      # Este archivo
```

## Uso

1. **Ingresar Pedido**: 
   - Click en "Ingresar Pedido"
   - Llenar el formulario con los datos del pedido
   - Las tallas se generan automáticamente según el tipo de calzado

2. **Ver Pedidos**:
   - Click en "Ver Pedidos"
   - Usar los filtros para buscar pedidos específicos
   - Descargar tickets en PDF
   - Editar o eliminar pedidos existentes

## Soporte

Para reportar problemas o solicitar ayuda, por favor crear un issue en el repositorio. 