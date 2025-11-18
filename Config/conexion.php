<?php
/**
 * CONFIGURACIÓN DE CONEXIÓN A BASE DE DATOS
 * Sistema de Gestión de Clínica
 */

// Configuración de Base de Datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'clinica_db');
define('DB_CHARSET', 'utf8mb4');

// Configuración de Zona Horaria
date_default_timezone_set('America/Lima');

// Iniciar sesión si no está iniciada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Clase de Conexión usando PDO
class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    private $charset = DB_CHARSET;
    private $pdo;

    public function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];

        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }

    public function getConnection() {
        return $this->pdo;
    }

    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Error en consulta: " . $e->getMessage());
        }
    }

    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return $this->pdo->lastInsertId();
    }

    public function execute($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
}

// Función para obtener instancia de BD
function getDB() {
    static $db = null;
    if ($db === null) {
        $db = new Database();
    }
    return $db;
}

// Función para validar sesión
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

// Función para obtener usuario actual
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'rol' => $_SESSION['rol'],
            'email' => $_SESSION['email']
        ];
    }
    return null;
}

// Función para verificar rol
function hasRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    return in_array($_SESSION['rol'], $roles);
}

// Función para redireccionar
function redirect($url) {
    header("Location: $url");
    exit();
}

// Función para sanitizar datos
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Función para validar DNI
function validarDNI($dni) {
    return preg_match('/^[0-9]{8}$/', $dni);
}

// Función para formatear fecha
function formatFecha($fecha) {
    return date('d/m/Y', strtotime($fecha));
}

// Función para calcular edad
function calcularEdad($fecha_nacimiento) {
    $fecha_nac = new DateTime($fecha_nacimiento);
    $hoy = new DateTime();
    return $hoy->diff($fecha_nac)->y;
}
?>