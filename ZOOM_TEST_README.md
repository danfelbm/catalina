# 🎥 Sistema de Pruebas Zoom Laravel

Este es un sistema independiente para probar las funcionalidades del paquete `jubaer/zoom-laravel` de manera rápida y sencilla.

## ⚙️ Configuración

### 1. Variables de Entorno

Asegúrate de configurar las siguientes variables en tu archivo `.env`:

```env
# Zoom Laravel Package Configuration (Server-to-Server OAuth)
ZOOM_CLIENT_KEY=your_zoom_client_id
ZOOM_CLIENT_SECRET=your_zoom_client_secret  
ZOOM_ACCOUNT_ID=your_zoom_account_id
```

### 2. Obtener Credenciales de Zoom

1. Ve a [https://marketplace.zoom.us/develop/create](https://marketplace.zoom.us/develop/create)
2. Crea una **Server-to-Server OAuth app**
3. Completa el formulario con la información de tu aplicación
4. Marca todos los scopes necesarios
5. Activa tu aplicación
6. Copia tu API Key (Client ID), API Secret (Client Secret) y Account ID

## 🚀 Uso

### Acceso
Una vez configurado, visita: **https://catalina.test/zoom-test**

### Funcionalidades Disponibles

#### ➕ Crear Reunión
- **Título**: Nombre de la reunión
- **Agenda**: Descripción de la agenda
- **Duración**: En minutos (por defecto 60)
- **Fecha/Hora**: Automáticamente se configura 1 hora en el futuro
- **Contraseña**: Por defecto "123456"

#### 🔍 Obtener Reunión
- Introduce el ID de la reunión para obtener sus detalles

#### ✏️ Actualizar Reunión
- Introduce el ID de la reunión y nuevos datos para actualizarla

#### 🗑️ Eliminar Reunión
- Introduce el ID de la reunión para eliminarla (requiere confirmación)

#### 📋 Listar Reuniones
- **Todas las Reuniones**: Lista todas las reuniones
- **Reuniones Próximas**: Solo las reuniones futuras
- **Reuniones Pasadas**: Solo las reuniones que ya ocurrieron

#### 👥 Obtener Usuarios
- Lista los usuarios de Zoom con diferentes estados
- Configurable por páginas

## 📝 Resultados

Todos los resultados se muestran en formato JSON en la parte superior de la página para facilitar la depuración.

## 🔧 Archivos Creados

- `app/Http/Controllers/ZoomTestController.php` - Controlador principal
- `resources/views/zoom-test/index.blade.php` - Vista de pruebas
- Rutas agregadas a `routes/web.php`

## ⚠️ Importante

- Este es un sistema de **pruebas únicamente**
- **NO** está integrado al sistema principal de votaciones
- Los logs se guardan en `storage/logs/laravel.log`
- Para producción, estas rutas deben ser removidas

## 🗑️ Limpiar (Opcional)

Para remover completamente el sistema de pruebas:

```bash
# Desinstalar paquete
composer remove jubaer/zoom-laravel

# Eliminar archivos
rm app/Http/Controllers/ZoomTestController.php
rm -rf resources/views/zoom-test/
rm ZOOM_TEST_README.md

# Remover rutas del archivo routes/web.php
# Remover variables ZOOM_* del archivo .env
```

## 📊 Troubleshooting

### Error de Autenticación
- Verifica que las credenciales en `.env` sean correctas
- Asegúrate de que la app de Zoom esté activada
- Revisa que los scopes estén configurados correctamente

### Error 404
- Verifica que las rutas estén correctamente agregadas
- Limpia cache de rutas: `php artisan route:clear`

### Errores de Permisos
- Revisa que tu app de Zoom tenga los permisos necesarios
- Verifica que el Account ID sea correcto