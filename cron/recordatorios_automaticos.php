<?php
/**
 * SCRIPT DE RECORDATORIOS AUTOMÁTICOS
 * Ejecutar cada hora mediante CRON
 * 
 * Configurar CRON:
 * 0 * * * * php /ruta/al/proyecto/cron/recordatorios_automaticos.php
 */

// Evitar ejecución desde navegador
if (php_sapi_name() !== 'cli') {
    die('Este script solo puede ejecutarse desde línea de comandos');
}

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../includes/NotificacionService.php';

echo "[" . date('Y-m-d H:i:s') . "] Iniciando proceso de recordatorios automáticos...\n";

try {
    $db = getDB();
    $notificacionService = new NotificacionService($db);
    
    // RECORDATORIOS 24 HORAS ANTES
    if (RECORDATORIO_24H) {
        echo "Buscando citas para recordatorio de 24 horas...\n";
        
        $sql_24h = "
            SELECT c.id
            FROM citas c
            WHERE c.estado_cita_id IN (1, 2)
            AND c.fecha = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
            AND NOT EXISTS (
                SELECT 1 FROM notificaciones n
                WHERE n.cita_id = c.id 
                AND n.tipo = 'recordatorio'
                AND DATE(n.fecha_envio) = CURDATE()
            )
        ";
        
        $citas_24h = $db->fetchAll($sql_24h);
        
        foreach ($citas_24h as $cita) {
            try {
                $notificacionService->notificarRecordatorioCita($cita['id']);
                echo "✓ Recordatorio enviado para cita ID: {$cita['id']}\n";
            } catch (Exception $e) {
                echo "✗ Error en cita ID {$cita['id']}: {$e->getMessage()}\n";
            }
        }
        
        echo "Total recordatorios 24h enviados: " . count($citas_24h) . "\n";
    }
    
    // RECORDATORIOS 2 HORAS ANTES
    if (RECORDATORIO_2H) {
        echo "Buscando citas para recordatorio de 2 horas...\n";
        
        $sql_2h = "
            SELECT c.id
            FROM citas c
            WHERE c.estado_cita_id IN (1, 2)
            AND c.fecha = CURDATE()
            AND c.hora BETWEEN DATE_ADD(NOW(), INTERVAL 2 HOUR) 
                           AND DATE_ADD(NOW(), INTERVAL 3 HOUR)
            AND NOT EXISTS (
                SELECT 1 FROM notificaciones n
                WHERE n.cita_id = c.id 
                AND n.tipo = 'recordatorio'
                AND DATE(n.fecha_envio) = CURDATE()
                AND HOUR(n.fecha_envio) = HOUR(NOW())
            )
        ";
        
        $citas_2h = $db->fetchAll($sql_2h);
        
        foreach ($citas_2h as $cita) {
            try {
                $notificacionService->notificarRecordatorioCita($cita['id']);
                echo "✓ Recordatorio 2h enviado para cita ID: {$cita['id']}\n";
            } catch (Exception $e) {
                echo "✗ Error en cita ID {$cita['id']}: {$e->getMessage()}\n";
            }
        }
        
        echo "Total recordatorios 2h enviados: " . count($citas_2h) . "\n";
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] Proceso completado exitosamente.\n\n";
    
} catch (Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}
?>