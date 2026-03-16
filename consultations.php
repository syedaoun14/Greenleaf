<?php
require_once 'config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ============ BOOK CONSULTATION (Customer) ============
    case 'book':
        if (!isCustomerLoggedIn()) sendJSON(['error' => true, 'message' => 'Please login to book.'], 401);

        $customerID = $_SESSION['customer_id'];
        $consultID  = (int)($_POST['consultant_id'] ?? 0);
        $date       = clean($_POST['date'] ?? '');
        $time       = clean($_POST['time_slot'] ?? '');
        $type       = clean($_POST['type'] ?? '');
        $query      = clean($_POST['query'] ?? '');

        if (!$consultID || !$date || !$type) {
            sendJSON(['error' => true, 'message' => 'Please fill all required fields.']);
        }

        // Check slot availability
        $conn  = getDB();
        $check = sqlsrv_query($conn,
            "SELECT ConsultationID FROM Consultations
             WHERE ConsultantID = ? AND ConsultationDate = ? AND TimeSlot = ? AND Status != 'Cancelled'",
            [$consultID, $date, $time]
        );
        if (sqlsrv_fetch($check)) {
            sendJSON(['error' => true, 'message' => 'This slot is already booked. Please choose another time.']);
        }

        $sql  = "INSERT INTO Consultations (CustomerID, ConsultantID, ConsultationDate, TimeSlot, ConsultationType, CustomerQuery)
                 VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = sqlsrv_query($conn, $sql, [$customerID, $consultID, $date, $time, $type, $query]);

        sendJSON($stmt
            ? ['error' => false, 'message' => 'Consultation booked successfully! We will confirm shortly.']
            : ['error' => true, 'message' => 'Booking failed. Please try again.']
        );
        break;

    // ============ MY CONSULTATIONS (Customer) ============
    case 'my_consultations':
        if (!isCustomerLoggedIn()) sendJSON(['error' => true, 'message' => 'Unauthorized.'], 401);

        $conn = getDB();
        $sql  = "SELECT con.*, c.FullName AS ConsultantName, c.Specialization
                 FROM Consultations con
                 LEFT JOIN Consultants c ON con.ConsultantID = c.ConsultantID
                 WHERE con.CustomerID = ?
                 ORDER BY con.ConsultationDate DESC";
        $stmt = sqlsrv_query($conn, $sql, [$_SESSION['customer_id']]);

        $list = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if ($row['ConsultationDate'] instanceof DateTime) {
                $row['ConsultationDate'] = $row['ConsultationDate']->format('Y-m-d');
            }
            $list[] = $row;
        }
        sendJSON(['error' => false, 'data' => $list]);
        break;

    // ============ MY SCHEDULE (Consultant) ============
    case 'my_schedule':
        if (!isConsultantLoggedIn()) sendJSON(['error' => true, 'message' => 'Unauthorized.'], 403);

        $conn = getDB();
        $sql  = "SELECT con.*, cu.FullName AS CustomerName, cu.Phone AS CustomerPhone, cu.Email AS CustomerEmail
                 FROM Consultations con
                 LEFT JOIN Customers cu ON con.CustomerID = cu.CustomerID
                 WHERE con.ConsultantID = ?
                 ORDER BY con.ConsultationDate ASC";
        $stmt = sqlsrv_query($conn, $sql, [$_SESSION['consultant_id']]);

        $list = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if ($row['ConsultationDate'] instanceof DateTime) {
                $row['ConsultationDate'] = $row['ConsultationDate']->format('Y-m-d');
            }
            $list[] = $row;
        }
        sendJSON(['error' => false, 'data' => $list]);
        break;

    // ============ UPDATE NOTES (Consultant) ============
    case 'update_notes':
        if (!isConsultantLoggedIn()) sendJSON(['error' => true, 'message' => 'Unauthorized.'], 403);

        $id    = (int)($_POST['id'] ?? 0);
        $notes = clean($_POST['notes'] ?? '');
        $recs  = clean($_POST['recommendations'] ?? '');

        $conn = getDB();
        $sql  = "UPDATE Consultations SET ConsultantNotes=?, Recommendations=?, Status='Completed' WHERE ConsultationID=?";
        $stmt = sqlsrv_query($conn, $sql, [$notes, $recs, $id]);

        sendJSON($stmt ? ['error' => false, 'message' => 'Notes saved.'] : ['error' => true, 'message' => 'Save failed.']);
        break;

    // ============ ALL CONSULTATIONS (Admin) ============
    case 'all_consultations':
        if (!isAdminLoggedIn()) sendJSON(['error' => true, 'message' => 'Unauthorized.'], 403);

        $conn = getDB();
        $sql  = "SELECT con.*,
                 cu.FullName AS CustomerName,
                 c.FullName AS ConsultantName
                 FROM Consultations con
                 LEFT JOIN Customers cu ON con.CustomerID = cu.CustomerID
                 LEFT JOIN Consultants c ON con.ConsultantID = c.ConsultantID
                 ORDER BY con.ConsultationDate DESC";
        $stmt = sqlsrv_query($conn, $sql);
        $list = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if ($row['ConsultationDate'] instanceof DateTime) {
                $row['ConsultationDate'] = $row['ConsultationDate']->format('Y-m-d');
            }
            $list[] = $row;
        }
        sendJSON(['error' => false, 'data' => $list]);
        break;

    // ============ UPDATE STATUS (Admin) ============
    case 'update_status':
        if (!isAdminLoggedIn()) sendJSON(['error' => true, 'message' => 'Unauthorized.'], 403);

        $id     = (int)($_POST['id'] ?? 0);
        $status = clean($_POST['status'] ?? '');
        $conn   = getDB();
        $stmt   = sqlsrv_query($conn, "UPDATE Consultations SET Status = ? WHERE ConsultationID = ?", [$status, $id]);

        sendJSON($stmt ? ['error' => false, 'message' => 'Status updated.'] : ['error' => true, 'message' => 'Failed.']);
        break;

    // ============ GET CONSULTANTS LIST ============
    case 'get_consultants':
        $conn = getDB();
        $stmt = sqlsrv_query($conn, "SELECT ConsultantID, FullName, Specialization, Rating, IsAvailable, ProfileImage FROM Consultants WHERE IsAvailable = 1");
        $list = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $list[] = $row;
        }
        sendJSON(['error' => false, 'data' => $list]);
        break;

    default:
        sendJSON(['error' => true, 'message' => 'Invalid action.']);
}
?>
