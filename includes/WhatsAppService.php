<?php
/**
 * SERVICIO DE WHATSAPP
 * Envío de mensajes por WhatsApp Business API
 */

class WhatsAppService {
    
    private $api_url;
    private $enabled;
    
    public function __construct() {
        require_once __DIR__ . '/../config/email_config.php';
        $this->api_url = WHATSAPP_API_URL;
        $this->enabled = WHATSAPP_ENABLED;
    }
    
    /**
     * Enviar mensaje simple
     */
    public function enviarMensaje($telefono, $mensaje) {
        if (!$this->enabled || !ENVIAR_WHATSAPP) {
            return false;
        }
        
        // Limpiar número de teléfono
        $telefono = preg_replace('/[^0-9]/', '', $telefono);
        
        // Para Perú, agregar código de país si no lo tiene
        if (strlen($telefono) == 9) {
            $telefono = '51' . $telefono;
        }
        
        // URL de WhatsApp Web API (método básico)
        $url = "https://api.whatsapp.com/send?phone={$telefono}&text=" . urlencode($mensaje);
        
        // Para producción, usar WhatsApp Business API
        // Necesitarías configurar con Meta (Facebook)
        
        return true;
    }
    
    /**
     * Enviar confirmación de cita
     */
    public function enviarConfirmacionCita($datos) {
        $mensaje = "🏥 *CLÍNICA RODRÍGUEZ*\n\n";
        $mensaje .= "✅ *Cita Confirmada*\n\n";
        $mensaje .= "Estimado(a) *{$datos['paciente_nombre']}*\n\n";
        $mensaje .= "📅 Fecha: {$datos['fecha']}\n";
        $mensaje .= "🕐 Hora: {$datos['hora']}\n";
        $mensaje .= "👨‍⚕️ Médico: {$datos['medico_nombre']}\n";
        $mensaje .= "🏥 Especialidad: {$datos['especialidad']}\n\n";
        $mensaje .= "📍 Jr. Brasil 262, Tarapoto\n";
        $mensaje .= "📞 (042) 522-123\n\n";
        $mensaje .= "Por favor, llegue 15 minutos antes.";
        
        return $this->enviarMensaje($datos['paciente_celular'], $mensaje);
    }
    
    /**
     * Enviar recordatorio de cita
     */
    public function enviarRecordatorioCita($datos) {
        $mensaje = "⏰ *RECORDATORIO DE CITA*\n\n";
        $mensaje .= "Estimado(a) *{$datos['paciente_nombre']}*\n\n";
        $mensaje .= "Le recordamos su cita:\n\n";
        $mensaje .= "📅 {$datos['fecha']}\n";
        $mensaje .= "🕐 {$datos['hora']}\n";
        $mensaje .= "👨‍⚕️ {$datos['medico_nombre']}\n\n";
        $mensaje .= "📍 Jr. Brasil 262, Tarapoto\n";
        $mensaje .= "No olvide llegar 15 minutos antes.";
        
        return $this->enviarMensaje($datos['paciente_celular'], $mensaje);
    }
    
    /**
     * Enviar cancelación de cita
     */
    public function enviarCancelacionCita($datos) {
        $mensaje = "❌ *CITA CANCELADA*\n\n";
        $mensaje .= "Estimado(a) *{$datos['paciente_nombre']}*\n\n";
        $mensaje .= "Su cita del {$datos['fecha']} a las {$datos['hora']} ha sido cancelada.\n\n";
        $mensaje .= "Para más información:\n";
        $mensaje .= "📞 (042) 522-123";
        
        return $this->enviarMensaje($datos['paciente_celular'], $mensaje);
    }
    
    /**
     * Enviar reprogramación de cita
     */
    public function enviarReprogramacionCita($datos) {
        $mensaje = "🔄 *CITA REPROGRAMADA*\n\n";
        $mensaje .= "Estimado(a) *{$datos['paciente_nombre']}*\n\n";
        $mensaje .= "Su cita ha sido reprogramada:\n\n";
        $mensaje .= "📅 Nueva fecha: {$datos['fecha_nueva']}\n";
        $mensaje .= "🕐 Nueva hora: {$datos['hora_nueva']}\n";
        $mensaje .= "👨‍⚕️ {$datos['medico_nombre']}\n\n";
        $mensaje .= "📍 Jr. Brasil 262, Tarapoto";
        
        return $this->enviarMensaje($datos['paciente_celular'], $mensaje);
    }
}
?>