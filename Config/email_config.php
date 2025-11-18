<?php
/**
 * CONFIGURACIÓN DE EMAIL
 * Configurar con tu servicio de correo (Gmail, SendGrid, etc.)
 */

// Configuración SMTP
define('SMTP_HOST', 'smtp.gmail.com');  // Cambiar según tu proveedor
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', 'tucorreo@gmail.com');  // TU EMAIL
define('SMTP_PASSWORD', 'tu_app_password');     // TU PASSWORD DE APLICACIÓN
define('SMTP_FROM_EMAIL', 'tucorreo@gmail.com');
define('SMTP_FROM_NAME', 'Clínica Rodríguez');

// Configuración WhatsApp (opcional)
define('WHATSAPP_API_URL', 'https://api.whatsapp.com/send');
define('WHATSAPP_ENABLED', false);  // Cambiar a true cuando configures

// Configuración de notificaciones
define('NOTIFICACIONES_ENABLED', true);
define('ENVIAR_EMAIL', true);
define('ENVIAR_WHATSAPP', false);  // Activar cuando tengas WhatsApp Business API

// Horarios de recordatorios
define('RECORDATORIO_24H', true);   // Recordar 24 horas antes
define('RECORDATORIO_2H', true);    // Recordar 2 horas antes
?>