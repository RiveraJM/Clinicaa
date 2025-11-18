# 🔔 SISTEMA DE NOTIFICACIONES

## 📋 Configuración Inicial

### 1. Configurar Email (Gmail)

**Paso 1:** Habilitar verificación en 2 pasos en tu cuenta Gmail
- Ve a: https://myaccount.google.com/security
- Activa "Verificación en 2 pasos"

**Paso 2:** Generar contraseña de aplicación
- Ve a: https://myaccount.google.com/apppasswords
- Selecciona "Correo" y "Otro (nombre personalizado)"
- Copia la contraseña generada (16 caracteres)

**Paso 3:** Editar `config/email_config.php`
```php
define('SMTP_USERNAME', 'tucorreo@gmail.com');
define('SMTP_PASSWORD', 'xxxx xxxx xxxx xxxx');  // La contraseña de app
define('SMTP_FROM_EMAIL', 'tucorreo@gmail.com');
```

### 2. Configurar WhatsApp (Opcional)

**Opción A: WhatsApp Business API (Recomendado para producción)**
- Requiere cuenta de Meta Business
- Costo: Variable según volumen
- Configuración: https://business.whatsapp.com

**Opción B: Integración simple (Para desarrollo)**
- Actualmente usa links de WhatsApp Web
- Editar `config/email_config.php`:
```php
define('WHATSAPP_ENABLED', true);
```

### 3. Configurar CRON para Recordatorios Automáticos

**Linux/cPanel:**
```bash
# Ejecutar cada hora
0 * * * * php /ruta/completa/cron/recordatorios_automaticos.php >> /var/log/recordatorios.log 2>&1
```

**Windows (Task Scheduler):**
- Crear tarea programada
- Acción: `php.exe`
- Argumentos: `C:\xampp\htdocs\sistema-clinica\cron\recordatorios_automaticos.php`
- Frecuencia: Cada hora

**Probar manualmente:**
```bash
php cron/recordatorios_automaticos.php
```

## 🚀 Uso del Sistema

### Notificaciones Automáticas

El sistema envía notificaciones automáticamente en estos casos:

1. **Al agendar cita:** Email/WhatsApp de confirmación
2. **24 horas antes:** Recordatorio automático (CRON)
3. **2 horas antes:** Segundo recordatorio (CRON)
4. **Al cancelar:** Notificación de cancelación
5. **Al reprogramar:** Notificación de cambio

### Tipos de Notificaciones

| Tipo | Email | WhatsApp | Cuándo se envía |
|------|-------|----------|----------------|
| Confirmación | ✅ | ✅ | Al crear cita |
| Recordatorio | ✅ | ✅ | 24h y 2h antes |
| Cancelación | ✅ | ✅ | Al cancelar |
| Reprogramación | ✅ | ✅ | Al reprogramar |

## 📊 Ver Historial

**Acceder a:** `views/notificaciones/lista.php`

Muestra:
- Total de notificaciones enviadas
- Filtros por fecha y tipo
- Estado de envío (exitoso/fallido)
- Estadísticas de emails y WhatsApp

## 🔧 Troubleshooting

### Email no se envía

**Error: "Could not authenticate"**
- Verifica usuario y contraseña de app
- Asegúrate de tener verificación en 2 pasos activa

**Error: "Failed to connect to server"**
- Verifica conexión a internet
- Algunos servidores bloquean puerto 587

**Solución alternativa:**
- Usar SendGrid, Mailgun o AWS SES
- Cambiar configuración en `email_config.php`

### WhatsApp no funciona

- Para producción, necesitas WhatsApp Business API
- Actualmente solo funciona como links
- Alternativa: Integrar con Twilio WhatsApp

### CRON no ejecuta

**Verificar:**
```bash
# Ver logs de cron
tail -f /var/log/recordatorios.log

# Verificar permisos
chmod +x cron/recordatorios_automaticos.php

# Probar manualmente
php cron/recordatorios_automaticos.php
```

## 💡 Mejoras Futuras

- [ ] Integración con WhatsApp Business API oficial
- [ ] Notificaciones SMS
- [ ] Plantillas personalizables
- [ ] Envío en lote
- [ ] Estadísticas avanzadas
- [ ] Panel de configuración visual

## 📞 Soporte

Para configuración avanzada o problemas:
- Revisar logs en `/var/log/`
- Verificar `error_log` de PHP
- Contactar al administrador del sistema