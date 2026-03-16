<?php
require_once 'config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ============ PLACE ORDER ============
    case 'place_order':
        if (!isCustomerLoggedIn()) sendJSON(['error' => true, 'message' => 'Please login to place an order.'], 401);

        $customerID = $_SESSION['customer_id'];
        $address    = clean($_POST['address'] ?? '');
        $payment    = clean($_POST['payment_method'] ?? 'Cash on Delivery');
        $notes      = clean($_POST['notes'] ?? '');
        $items      = json_decode($_POST['items'] ?? '[]', true);

        if (empty($items)) {
            sendJSON(['error' => true, 'message' => 'Cart is empty.']);
        }

        $total = 0;
        foreach ($items as $item) {
            $total += (float)$item['price'] * (int)$item['qty'];
        }

        $conn = getDB();

        // Insert order
        $sql  = "INSERT INTO Orders (CustomerID, TotalAmount, DeliveryAddress, PaymentMethod, Notes)
                 OUTPUT INSERTED.OrderID
                 VALUES (?, ?, ?, ?, ?)";
        $stmt = sqlsrv_query($conn, $sql, [$customerID, $total, $address, $payment, $notes]);

        if (!$stmt) {
            sendJSON(['error' => true, 'message' => 'Failed to place order.']);
        }

        $row     = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        $orderID = $row['OrderID'];

        // Insert order details
        foreach ($items as $item) {
            $dSql = "INSERT INTO OrderDetails (OrderID, ItemType, ItemID, ItemName, Quantity, UnitPrice)
                     VALUES (?, ?, ?, ?, ?, ?)";
            sqlsrv_query($conn, $dSql, [
                $orderID,
                $item['type'],
                $item['id'],
                $item['name'],
                (int)$item['qty'],
                (float)$item['price']
            ]);

            // Reduce stock
            if ($item['type'] === 'Plant') {
                sqlsrv_query($conn, "UPDATE Plants SET StockQuantity = StockQuantity - ? WHERE PlantID = ?", [(int)$item['qty'], $item['id']]);
            } else {
                sqlsrv_query($conn, "UPDATE GardenSupplies SET StockQuantity = StockQuantity - ? WHERE SupplyID = ?", [(int)$item['qty'], $item['id']]);
            }
        }

        sendJSON(['error' => false, 'message' => 'Order placed successfully!', 'order_id' => $orderID]);
        break;

    // ============ GET MY ORDERS (Customer) ============
    case 'my_orders':
        if (!isCustomerLoggedIn()) sendJSON(['error' => true, 'message' => 'Unauthorized.'], 401);

        $conn = getDB();
        $sql  = "SELECT o.*, 
                 (SELECT COUNT(*) FROM OrderDetails WHERE OrderID = o.OrderID) AS ItemCount
                 FROM Orders o
                 WHERE o.CustomerID = ?
                 ORDER BY o.OrderDate DESC";
        $stmt = sqlsrv_query($conn, $sql, [$_SESSION['customer_id']]);

        $orders = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if ($row['OrderDate'] instanceof DateTime) {
                $row['OrderDate'] = $row['OrderDate']->format('Y-m-d H:i');
            }
            $orders[] = $row;
        }
        sendJSON(['error' => false, 'data' => $orders]);
        break;

    // ============ GET ORDER DETAILS ============
    case 'order_detail':
        $orderID = (int)($_GET['id'] ?? 0);
        $conn    = getDB();
        $stmt    = sqlsrv_query($conn, "SELECT * FROM OrderDetails WHERE OrderID = ?", [$orderID]);
        $details = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $details[] = $row;
        }
        sendJSON(['error' => false, 'data' => $details]);
        break;

    // ============ ALL ORDERS (Admin) ============
    case 'all_orders':
        if (!isAdminLoggedIn()) sendJSON(['error' => true, 'message' => 'Unauthorized.'], 403);

        $conn = getDB();
        $sql  = "SELECT o.*, c.FullName AS CustomerName, c.Email AS CustomerEmail
                 FROM Orders o
                 LEFT JOIN Customers c ON o.CustomerID = c.CustomerID
                 ORDER BY o.OrderDate DESC";
        $stmt = sqlsrv_query($conn, $sql);
        $orders = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if ($row['OrderDate'] instanceof DateTime) {
                $row['OrderDate'] = $row['OrderDate']->format('Y-m-d H:i');
            }
            $orders[] = $row;
        }
        sendJSON(['error' => false, 'data' => $orders]);
        break;

    // ============ UPDATE ORDER STATUS (Admin) ============
    case 'update_status':
        if (!isAdminLoggedIn()) sendJSON(['error' => true, 'message' => 'Unauthorized.'], 403);

        $id     = (int)($_POST['order_id'] ?? 0);
        $status = clean($_POST['status'] ?? '');
        $conn   = getDB();
        $stmt   = sqlsrv_query($conn, "UPDATE Orders SET Status = ? WHERE OrderID = ?", [$status, $id]);

        sendJSON($stmt ? ['error' => false, 'message' => 'Status updated.'] : ['error' => true, 'message' => 'Update failed.']);
        break;

    // ============ ADMIN DASHBOARD STATS ============
    case 'dashboard_stats':
        if (!isAdminLoggedIn()) sendJSON(['error' => true, 'message' => 'Unauthorized.'], 403);

        $conn   = getDB();
        $stats  = [];

        $r = sqlsrv_query($conn, "SELECT COUNT(*) AS cnt FROM Orders WHERE Status != 'Cancelled'");
        $stats['total_orders'] = sqlsrv_fetch_array($r)['cnt'];

        $r = sqlsrv_query($conn, "SELECT SUM(TotalAmount) AS total FROM Orders WHERE Status = 'Delivered'");
        $stats['total_revenue'] = sqlsrv_fetch_array($r)['total'] ?? 0;

        $r = sqlsrv_query($conn, "SELECT COUNT(*) AS cnt FROM Customers WHERE IsActive = 1");
        $stats['total_customers'] = sqlsrv_fetch_array($r)['cnt'];

        $r = sqlsrv_query($conn, "SELECT COUNT(*) AS cnt FROM Plants WHERE IsActive = 1");
        $stats['total_plants'] = sqlsrv_fetch_array($r)['cnt'];

        $r = sqlsrv_query($conn, "SELECT COUNT(*) AS cnt FROM Consultations WHERE Status = 'Pending'");
        $stats['pending_consultations'] = sqlsrv_fetch_array($r)['cnt'];

        $r = sqlsrv_query($conn, "SELECT COUNT(*) AS cnt FROM Orders WHERE Status = 'Pending'");
        $stats['pending_orders'] = sqlsrv_fetch_array($r)['cnt'];

        sendJSON(['error' => false, 'data' => $stats]);
        break;

    default:
        sendJSON(['error' => true, 'message' => 'Invalid action.']);
}
?>
