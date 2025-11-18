<?php
/**
 * API PARA CONSULTAR DNI - RENIEC
 * Modo de prueba incluido
 */

header('Content-Type: application/json; charset=utf-8');

// Configuración de la API (CAMBIAR CON TU API KEY)
define('API_KEY', 'TU_API_KEY_AQUI');
define('API_URL', 'https://dniruc.apisperu.com/api/v1/dni/');

// Validar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener DNI
$dni = $_POST['dni'] ?? '';

// Validar DNI
if (empty($dni) || !preg_match('/^[0-9]{8}$/', $dni)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'DNI inválido']);
    exit;
}

// ============================================
// MODO DE PRUEBA (QUITAR EN PRODUCCIÓN)
// ============================================
$datosPrueba = [
    '12345678' => [
        'nombres' => 'JUAN CARLOS',
        'apellidoPaterno' => 'PEREZ',
        'apellidoMaterno' => 'GOMEZ'
    ],
    '87654321' => [
        'nombres' => 'MARIA ELENA',
        'apellidoPaterno' => 'RODRIGUEZ',
        'apellidoMaterno' => 'SANCHEZ'
    ],
    '11111111' => [
        'nombres' => 'PEDRO LUIS',
        'apellidoPaterno' => 'MARTINEZ',
        'apellidoMaterno' => 'LOPEZ'
    ]
];

if (isset($datosPrueba[$dni])) {
    echo json_encode([
        'success' => true,
        'data' => $datosPrueba[$dni]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================
// MODO REAL CON API (DESCOMENTAR CUANDO TENGAS API KEY)
// ============================================
/*
if (API_KEY === 'TU_API_KEY_AQUI') {
    echo json_encode([
        'success' => false,
        'message' => 'Configura tu API KEY en api/consultar_dni.php'
    ]);
    exit;
}

try {
    $url = API_URL . $dni;
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . API_KEY,
            'Content-Type: application/json'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('Error en la API');
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['nombres'])) {
        throw new Exception('No se encontraron datos');
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
*/

// Si no encuentra en datos de prueba
echo json_encode([
    'success' => false,
    'message' => 'DNI no encontrado en base de prueba'
], JSON_UNESCAPED_UNICODE);
?>