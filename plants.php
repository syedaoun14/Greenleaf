<?php
require_once 'config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? 'get_all';

switch ($action) {

    // ============ GET ALL PLANTS ============
    case 'get_all':
        $conn = getDB();
        $category = clean($_GET['category'] ?? '');
        $search   = clean($_GET['search'] ?? '');

        $sql = "SELECT p.*, c.CategoryName
                FROM Plants p
                LEFT JOIN PlantCategories c ON p.CategoryID = c.CategoryID
                WHERE p.IsActive = 1";

        $params = [];
        if ($category) {
            $sql .= " AND c.CategoryName = ?";
            $params[] = $category;
        }
        if ($search) {
            $sql .= " AND p.PlantName LIKE ?";
            $params[] = "%$search%";
        }
        $sql .= " ORDER BY p.IsFeatured DESC, p.CreatedAt DESC";

        $stmt   = sqlsrv_query($conn, $sql, $params ?: null);
        $plants = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $plants[] = $row;
        }
        sendJSON(['error' => false, 'data' => $plants]);
        break;

    // ============ GET SINGLE PLANT ============
    case 'get_one':
        $id   = (int)($_GET['id'] ?? 0);
        $conn = getDB();
        $sql  = "SELECT p.*, c.CategoryName
                 FROM Plants p
                 LEFT JOIN PlantCategories c ON p.CategoryID = c.CategoryID
                 WHERE p.PlantID = ? AND p.IsActive = 1";
        $stmt = sqlsrv_query($conn, $sql, [$id]);
        $plant = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if ($plant) {
            sendJSON(['error' => false, 'data' => $plant]);
        } else {
            sendJSON(['error' => true, 'message' => 'Plant not found.']);
        }
        break;

    // ============ ADD PLANT (Admin only) ============
    case 'add':
        if (!isAdminLoggedIn()) sendJSON(['error' => true, 'message' => 'Unauthorized.'], 403);

        $name     = clean($_POST['name'] ?? '');
        $catID    = (int)($_POST['category_id'] ?? 0);
        $desc     = clean($_POST['description'] ?? '');
        $price    = (float)($_POST['price'] ?? 0);
        $oldPrice = $_POST['old_price'] ? (float)$_POST['old_price'] : null;
        $stock    = (int)($_POST['stock'] ?? 0);
        $care     = clean($_POST['care_level'] ?? 'Easy');
        $water    = clean($_POST['water_needs'] ?? 'Medium');
        $sun      = clean($_POST['sunlight'] ?? 'Partial');
        $featured = (int)($_POST['is_featured'] ?? 0);

        // Handle image upload
        $image = 'default_plant.jpg';
        if (!empty($_FILES['image']['name'])) {
            $ext      = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'plant_' . time() . '.' . $ext;
            $uploadDir = '../images/plants/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                $image = $filename;
            }
        } elseif (!empty($_POST['image_url'])) {
            $image = clean($_POST['image_url']);
        }

        $conn = getDB();
        $sql  = "INSERT INTO Plants (PlantName, CategoryID, Description, Price, OldPrice, StockQuantity, PlantImage, CareLevel, WaterNeeds, Sunlight, IsFeatured)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = sqlsrv_query($conn, $sql, [$name, $catID, $desc, $price, $oldPrice, $stock, $image, $care, $water, $sun, $featured]);

        if ($stmt) {
            sendJSON(['error' => false, 'message' => 'Plant added successfully.']);
        } else {
            sendJSON(['error' => true, 'message' => 'Failed to add plant.']);
        }
        break;

    // ============ UPDATE PLANT (Admin only) ============
    case 'update':
        if (!isAdminLoggedIn()) sendJSON(['error' => true, 'message' => 'Unauthorized.'], 403);

        $id    = (int)($_POST['id'] ?? 0);
        $name  = clean($_POST['name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $desc  = clean($_POST['description'] ?? '');

        $conn = getDB();
        $sql  = "UPDATE Plants SET PlantName=?, Price=?, StockQuantity=?, Description=? WHERE PlantID=?";
        $stmt = sqlsrv_query($conn, $sql, [$name, $price, $stock, $desc, $id]);

        sendJSON($stmt ? ['error' => false, 'message' => 'Plant updated.'] : ['error' => true, 'message' => 'Update failed.']);
        break;

    // ============ DELETE PLANT (Admin only) ============
    case 'delete':
        if (!isAdminLoggedIn()) sendJSON(['error' => true, 'message' => 'Unauthorized.'], 403);

        $id   = (int)($_POST['id'] ?? 0);
        $conn = getDB();
        $sql  = "UPDATE Plants SET IsActive = 0 WHERE PlantID = ?";
        $stmt = sqlsrv_query($conn, $sql, [$id]);

        sendJSON($stmt ? ['error' => false, 'message' => 'Plant deleted.'] : ['error' => true, 'message' => 'Delete failed.']);
        break;

    // ============ GET CATEGORIES ============
    case 'get_categories':
        $conn = getDB();
        $stmt = sqlsrv_query($conn, "SELECT * FROM PlantCategories WHERE IsActive = 1");
        $cats = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $cats[] = $row;
        }
        sendJSON(['error' => false, 'data' => $cats]);
        break;

    // ============ GET SUPPLIES ============
    case 'get_supplies':
        $conn     = getDB();
        $stmt     = sqlsrv_query($conn, "SELECT * FROM GardenSupplies WHERE IsActive = 1 ORDER BY CreatedAt DESC");
        $supplies = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $supplies[] = $row;
        }
        sendJSON(['error' => false, 'data' => $supplies]);
        break;

    default:
        sendJSON(['error' => true, 'message' => 'Invalid action.']);
}
?>
