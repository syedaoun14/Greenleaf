<?php
require_once 'config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ============ CUSTOMER REGISTER ============
    case 'register':
        $name     = clean($_POST['name'] ?? '');
        $email    = clean($_POST['email'] ?? '');
        $phone    = clean($_POST['phone'] ?? '');
        $address  = clean($_POST['address'] ?? '');
        $city     = clean($_POST['city'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$name || !$email || !$password) {
            sendJSON(['error' => true, 'message' => 'Name, email and password are required.']);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendJSON(['error' => true, 'message' => 'Invalid email address.']);
        }
        if (strlen($password) < 6) {
            sendJSON(['error' => true, 'message' => 'Password must be at least 6 characters.']);
        }

        $conn = getDB();
        // Check duplicate email
        $check = sqlsrv_query($conn, "SELECT CustomerID FROM Customers WHERE Email = ?", [$email]);
        if (sqlsrv_fetch($check)) {
            sendJSON(['error' => true, 'message' => 'Email already registered.']);
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $sql  = "INSERT INTO Customers (FullName, Email, Phone, Address, City, PasswordHash)
                 VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = sqlsrv_query($conn, $sql, [$name, $email, $phone, $address, $city, $hash]);

        if ($stmt) {
            sendJSON(['error' => false, 'message' => 'Registration successful! Please login.']);
        } else {
            sendJSON(['error' => true, 'message' => 'Registration failed. Please try again.']);
        }
        break;

    // ============ CUSTOMER LOGIN ============
    case 'login':
        $email    = clean($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$email || !$password) {
            sendJSON(['error' => true, 'message' => 'Email and password required.']);
        }

        $conn = getDB();
        $sql  = "SELECT CustomerID, FullName, Email, PasswordHash FROM Customers WHERE Email = ? AND IsActive = 1";
        $stmt = sqlsrv_query($conn, $sql, [$email]);
        $user = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        if ($user && password_verify($password, $user['PasswordHash'])) {
            $_SESSION['customer_id']   = $user['CustomerID'];
            $_SESSION['customer_name'] = $user['FullName'];
            $_SESSION['customer_email']= $user['Email'];
            sendJSON(['error' => false, 'message' => 'Login successful!', 'name' => $user['FullName']]);
        } else {
            sendJSON(['error' => true, 'message' => 'Invalid email or password.']);
        }
        break;

    // ============ ADMIN LOGIN ============
    case 'admin_login':
        $email    = clean($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $conn = getDB();
        $sql  = "SELECT AdminID, FullName, Email, PasswordHash, Role FROM Admins WHERE Email = ?";
        $stmt = sqlsrv_query($conn, $sql, [$email]);
        $admin = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['PasswordHash'])) {
            $_SESSION['admin_id']    = $admin['AdminID'];
            $_SESSION['admin_name']  = $admin['FullName'];
            $_SESSION['admin_email'] = $admin['Email'];
            $_SESSION['admin_role']  = $admin['Role'];
            sendJSON(['error' => false, 'message' => 'Admin login successful!', 'name' => $admin['FullName']]);
        } else {
            sendJSON(['error' => true, 'message' => 'Invalid admin credentials.']);
        }
        break;

    // ============ CONSULTANT LOGIN ============
    case 'consultant_login':
        $email    = clean($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $conn = getDB();
        $sql  = "SELECT ConsultantID, FullName, Email, PasswordHash FROM Consultants WHERE Email = ?";
        $stmt = sqlsrv_query($conn, $sql, [$email]);
        $consultant = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        if ($consultant && password_verify($password, $consultant['PasswordHash'])) {
            $_SESSION['consultant_id']   = $consultant['ConsultantID'];
            $_SESSION['consultant_name'] = $consultant['FullName'];
            sendJSON(['error' => false, 'message' => 'Login successful!', 'name' => $consultant['FullName']]);
        } else {
            sendJSON(['error' => true, 'message' => 'Invalid credentials.']);
        }
        break;

    // ============ LOGOUT ============
    case 'logout':
        session_destroy();
        sendJSON(['error' => false, 'message' => 'Logged out successfully.']);
        break;

    // ============ GET SESSION ============
    case 'session':
        if (isCustomerLoggedIn()) {
            sendJSON(['loggedIn' => true, 'role' => 'customer', 'name' => $_SESSION['customer_name']]);
        } elseif (isAdminLoggedIn()) {
            sendJSON(['loggedIn' => true, 'role' => 'admin', 'name' => $_SESSION['admin_name']]);
        } elseif (isConsultantLoggedIn()) {
            sendJSON(['loggedIn' => true, 'role' => 'consultant', 'name' => $_SESSION['consultant_name']]);
        } else {
            sendJSON(['loggedIn' => false]);
        }
        break;

    default:
        sendJSON(['error' => true, 'message' => 'Invalid action.']);
}
?>
