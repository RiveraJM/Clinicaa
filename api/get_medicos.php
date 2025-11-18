<?php
/**
 * API: Obtener médicos por especialidad
 */

require_once '../config/conexion.php';
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$especialidad_id = $_GET['especialidad_id'] ?? '';

if (empty($especialidad_id)) {
    echo json_encode(['success' => false, 'message' => 'Especialidad no especificada']);
    exit;
}

try {
    $db = getDB();
    
    $medicos = $db->fetchAll("
        SELECT id, nombres, apellidos, nro_colegiatura
        FROM medicos
        WHERE especialidad_id = :especialidad_id 
        AND estado = 'activo'
        ORDER BY nombres, apellidos
    ", ['especialidad_id' => $especialidad_id]);
    
    echo json_encode([
        'success' => true,
        'medicos' => $medicos
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Error en get_medicos.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener médicos'
    ]);
}
?>