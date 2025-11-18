<?php
/**
 * SERVICIO DE NOTIFICACIONES
 * Coordina envío de emails y WhatsApp
 */

require_once __DIR__ . '/EmailService.php';
require_once __DIR__ . '/WhatsAppService.php';

class NotificacionService {
    
    private $db;
    private $emailService;
    private $whatsappService;
    
    public function __construct($db) {
        $this->db = $db;
        $this->emailService = new EmailService();
        $this->whatsappService = new WhatsAppService();
    }
    
    /**
     * Notificar confirmación de cita
     */
    public function notificarConfirmacionCita($cita_id) {
        $datos = $this->obtenerDatosCita($cita_id);
        
        if (!$datos) {
            return false;
        }
        
        $resultados = [
            'email' => false,
            'whatsapp' => false
        ];
        
        // Enviar email
        if ($datos['paciente_email']) {
            $resultados['email'] = $this->emailService->enviarConfirmacionCita($datos);
        }
        
        // Enviar WhatsApp
        if ($datos['paciente_celular']) {
            $resultados['whatsapp'] = $this->whatsappService->enviarConfirmacionCita($datos);
        }
        
        // Registrar notificación
        $this->registrarNotificacion($cita_id, 'confirmacion', $resultados);
        
        return $resultados;
    }
    
    /**
     * Notificar recordatorio de cita
     */
    public function notificarRecordatorioCita($cita_id) {
        $datos = $this->obtenerDatosCita($cita_id);
        
        if (!$datos) {
            return false;
        }
        
        $resultados = [
            'email' => false,
            'whatsapp' => false
        ];
        
        // Enviar email
        if ($datos['paciente_email']) {
            $resultados['email'] = $this->emailService->enviarRecordatorioCita($datos);
        }
        
        // Enviar WhatsApp
        if ($datos['paciente_celular']) {
            $resultados['whatsapp'] = $this->whatsappService->enviarRecordatorioCita($datos);
        }
        
        // Registrar notificación
        $this->registrarNotificacion($cita_id, 'recordatorio', $resultados);
        
        return $resultados;
    }
    
    /**
     * Notificar cancelación de cita
     */
    public function notificarCancelacionCita($cita_id) {
        $datos = $this->obtenerDatosCita($cita_id);
        
        if (!$datos) {
            return false;
        }
        
        $resultados = [
            'email' => false,
            'whatsapp' => false
        ];
        
        if ($datos['paciente_email']) {
            $resultados['email'] = $this->emailService->enviarCancelacionCita($datos);
        }
        
        if ($datos['paciente_celular']) {
            $resultados['whatsapp'] = $this->whatsappService->enviarCancelacionCita($datos);
        }
        
        $this->registrarNotificacion($cita_id, 'cancelacion', $resultados);
        
        return $resultados;
    }
    
    /**
     * Notificar reprogramación de cita
     */
    public function notificarReprogramacionCita($cita_id, $fecha_nueva, $hora_nueva) {
        $datos = $this->obtenerDatosCita($cita_id);
        
        if (!$datos) {
            return false;
        }
        
        $datos['fecha_nueva'] = date('d/m/Y', strtotime($fecha_nueva));
        $datos['hora_nueva'] = date('H:i', strtotime($hora_nueva));
        
        $resultados = [
            'email' => false,
            'whatsapp' => false
        ];
        
        if ($datos['paciente_email']) {
            $resultados['email'] = $this->emailService->enviarReprogramacionCita($datos);
        }
        
        if ($datos['paciente_celular']) {
            $resultados['whatsapp'] = $this->whatsappService->enviarReprogramacionCita($datos);
        }
        
        $this->registrarNotificacion($cita_id, 'reprogramacion', $resultados);
        
        return $resultados;
    }
    
    /**
     * Obtener datos de la cita para notificaciones
     */
    private function obtenerDatosCita($cita_id) {
        $sql = "SELECT c.*,
                       CONCAT(p.nombres, ' ', p.apellidos) as paciente_nombre,
                       p.email as paciente_email,
                       p.celular as paciente_celular,
                       CONCAT(m.nombres, ' ', m.apellidos) as medico_nombre,
                       m.consultorio,
                       e.nombre as especialidad
                FROM citas c
                INNER JOIN pacientes p ON c.paciente_id = p.id
                INNER JOIN medicos m ON c.medico_id = m.id
                INNER JOIN especialidades e ON c.especialidad_id = e.id
                WHERE c.id = :cita_id";
        
        $cita = $this->db->fetchOne($sql, ['cita_id' => $cita_id]);
        
        if (!$cita) {
            return null;
        }
        
        // Formatear datos
        $cita['fecha'] = date('d/m/Y', strtotime($cita['fecha']));
        $cita['hora'] = date('H:i', strtotime($cita['hora']));
        
        return $cita;
    }
    
    /**
     * Registrar notificación en la base de datos
     */
    private function registrarNotificacion($cita_id, $tipo, $resultados) {
        $sql = "INSERT INTO notificaciones (
                    cita_id, tipo, email_enviado, whatsapp_enviado
                ) VALUES (
                    :cita_id, :tipo, :email, :whatsapp
                )";
        
        $this->db->insert($sql, [
            'cita_id' => $cita_id,
            'tipo' => $tipo,
            'email' => $resultados['email'] ? 1 : 0,
            'whatsapp' => $resultados['whatsapp'] ? 1 : 0
        ]);
    }
}
?>