<?php
/**
 * SERVICIO DE EMAIL
 * Envío de correos electrónicos
 */

class EmailService {
    
    private $from_email;
    private $from_name;
    
    public function __construct() {
        require_once __DIR__ . '/../config/email_config.php';
        $this->from_email = SMTP_FROM_EMAIL;
        $this->from_name = SMTP_FROM_NAME;
    }
    
    /**
     * Enviar email simple
     */
    public function enviar($to, $subject, $body, $isHTML = true) {
        if (!ENVIAR_EMAIL) {
            return false;
        }
        
        $headers = [
            'From: ' . $this->from_name . ' <' . $this->from_email . '>',
            'Reply-To: ' . $this->from_email,
            'X-Mailer: PHP/' . phpversion()
        ];
        
        if ($isHTML) {
            $headers[] = 'MIME-Version: 1.0';
            $headers[] = 'Content-type: text/html; charset=utf-8';
        }
        
        $headers_string = implode("\r\n", $headers);
        
        return mail($to, $subject, $body, $headers_string);
    }
    
    /**
     * Enviar confirmación de cita
     */
    public function enviarConfirmacionCita($datos) {
        $to = $datos['paciente_email'];
        $subject = "Confirmación de Cita - Clínica Rodríguez";
        
        $body = $this->getPlantillaConfirmacion($datos);
        
        return $this->enviar($to, $subject, $body, true);
    }
    
    /**
     * Enviar recordatorio de cita
     */
    public function enviarRecordatorioCita($datos) {
        $to = $datos['paciente_email'];
        $subject = "Recordatorio de Cita - Clínica Rodríguez";
        
        $body = $this->getPlantillaRecordatorio($datos);
        
        return $this->enviar($to, $subject, $body, true);
    }
    
    /**
     * Enviar notificación de cancelación
     */
    public function enviarCancelacionCita($datos) {
        $to = $datos['paciente_email'];
        $subject = "Cita Cancelada - Clínica Rodríguez";
        
        $body = $this->getPlantillaCancelacion($datos);
        
        return $this->enviar($to, $subject, $body, true);
    }
    
    /**
     * Enviar notificación de reprogramación
     */
    public function enviarReprogramacionCita($datos) {
        $to = $datos['paciente_email'];
        $subject = "Cita Reprogramada - Clínica Rodríguez";
        
        $body = $this->getPlantillaReprogramacion($datos);
        
        return $this->enviar($to, $subject, $body, true);
    }
    
    /**
     * Plantilla de confirmación
     */
    private function getPlantillaConfirmacion($datos) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #00BCD4; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
                .info-box { background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #00BCD4; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
                .btn { display: inline-block; padding: 12px 30px; background: #00BCD4; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🏥 Clínica Rodríguez</h1>
                    <p>Confirmación de Cita Médica</p>
                </div>
                <div class='content'>
                    <p>Estimado(a) <strong>{$datos['paciente_nombre']}</strong>,</p>
                    <p>Su cita ha sido registrada exitosamente. Por favor, tome nota de los siguientes detalles:</p>
                    
                    <div class='info-box'>
                        <p><strong>📅 Fecha:</strong> {$datos['fecha']}</p>
                        <p><strong>🕐 Hora:</strong> {$datos['hora']}</p>
                        <p><strong>👨‍⚕️ Médico:</strong> {$datos['medico_nombre']}</p>
                        <p><strong>🏥 Especialidad:</strong> {$datos['especialidad']}</p>
                        <p><strong>📍 Consultorio:</strong> {$datos['consultorio']}</p>
                    </div>
                    
                    <h3>Recomendaciones:</h3>
                    <ul>
                        <li>Llegue 15 minutos antes de su cita</li>
                        <li>Traiga su DNI y seguro (si aplica)</li>
                        <li>Traiga exámenes anteriores si los tiene</li>
                    </ul>
                    
                    <p><strong>📍 Dirección:</strong> Jr. Brasil 262, Tarapoto</p>
                    <p><strong>📞 Teléfono:</strong> (042) 522-123</p>
                </div>
                <div class='footer'>
                    <p>Este es un mensaje automático, por favor no responda a este correo.</p>
                    <p>&copy; 2025 Clínica Rodríguez y Especialistas - Todos los derechos reservados</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Plantilla de recordatorio
     */
    private function getPlantillaRecordatorio($datos) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #FFA726; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
                .info-box { background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #FFA726; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>⏰ Recordatorio de Cita</h1>
                </div>
                <div class='content'>
                    <p>Estimado(a) <strong>{$datos['paciente_nombre']}</strong>,</p>
                    <p>Le recordamos su cita médica programada:</p>
                    
                    <div class='info-box'>
                        <p><strong>📅 Fecha:</strong> {$datos['fecha']}</p>
                        <p><strong>🕐 Hora:</strong> {$datos['hora']}</p>
                        <p><strong>👨‍⚕️ Médico:</strong> {$datos['medico_nombre']}</p>
                        <p><strong>🏥 Especialidad:</strong> {$datos['especialidad']}</p>
                    </div>
                    
                    <p>Por favor, no olvide llegar 15 minutos antes.</p>
                    <p><strong>📍 Dirección:</strong> Jr. Brasil 262, Tarapoto</p>
                </div>
                <div class='footer'>
                    <p>&copy; 2025 Clínica Rodríguez y Especialistas</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Plantilla de cancelación
     */
    private function getPlantillaCancelacion($datos) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #F44336; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
                .info-box { background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #F44336; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>❌ Cita Cancelada</h1>
                </div>
                <div class='content'>
                    <p>Estimado(a) <strong>{$datos['paciente_nombre']}</strong>,</p>
                    <p>Su cita médica ha sido cancelada:</p>
                    
                    <div class='info-box'>
                        <p><strong>📅 Fecha:</strong> {$datos['fecha']}</p>
                        <p><strong>🕐 Hora:</strong> {$datos['hora']}</p>
                        <p><strong>👨‍⚕️ Médico:</strong> {$datos['medico_nombre']}</p>
                    </div>
                    
                    <p>Para agendar una nueva cita, por favor comuníquese con nosotros.</p>
                    <p><strong>📞 Teléfono:</strong> (042) 522-123</p>
                </div>
                <div class='footer'>
                    <p>&copy; 2025 Clínica Rodríguez y Especialistas</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Plantilla de reprogramación
     */
    private function getPlantillaReprogramacion($datos) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2196F3; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border: 1px solid #ddd; }
                .info-box { background: white; padding: 20px; margin: 10px 0; border-left: 4px solid #2196F3; }
                .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🔄 Cita Reprogramada</h1>
                </div>
                <div class='content'>
                    <p>Estimado(a) <strong>{$datos['paciente_nombre']}</strong>,</p>
                    <p>Su cita ha sido reprogramada. Nuevos datos:</p>
                    
                    <div class='info-box'>
                        <h3>Nueva Fecha y Hora:</h3>
                        <p><strong>📅 Fecha:</strong> {$datos['fecha_nueva']}</p>
                        <p><strong>🕐 Hora:</strong> {$datos['hora_nueva']}</p>
                        <p><strong>👨‍⚕️ Médico:</strong> {$datos['medico_nombre']}</p>
                    </div>
                    
                    <p>Por favor, tome nota de la nueva fecha y hora.</p>
                </div>
                <div class='footer'>
                    <p>&copy; 2025 Clínica Rodríguez y Especialistas</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
?>