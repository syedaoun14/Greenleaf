<?php
// ================================================
// GreenLeaf Smart Plant Nursery
// Database Configuration - MS SQL Server 2014
// ================================================

define('DB_SERVER',   'localhost');
define('DB_NAME',     'GreenLeafNursery');
define('DB_USER',     'sa');
define('DB_PASS',     'YourPassword123');

// MSSQL Connection using sqlsrv extension
function getDB() {
    $connectionInfo = [
        "Database"             => DB_NAME,
        "UID"                  => DB_USER,
        "PWD"                  => DB_PASS,
        "CharacterSet"         => "UTF-8",
        "TrustServerCertificate" => true
    ];
    $conn = sqlsrv_connect(DB_SERVER, $connectionInfo);
    if ($conn === false) {
        die(json_encode([
            'error' => true,
            'message' => 'Database connection failed.',
            'details' => sqlsrv_errors()
        ]));
    }
    return $conn;
}

// Session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper: send JSON response
function sendJSON($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Helper: sanitize input
function clean($str) {
    return htmlspecialchars(strip_tags(trim($str)));
}

// Helper: check customer login
function isCustomerLoggedIn() {
    return isset($_SESSION['customer_id']);
}

// Helper: check admin login
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// Helper: check consultant login
function isConsultantLoggedIn() {
    return isset($_SESSION['consultant_id']);
}

// Helper: redirect
function redirect($url) {
    header("Location: $url");
    exit;
}
?>
