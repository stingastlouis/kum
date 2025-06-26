<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';
require_once '../utils/redirectMessage.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cakeId = $_POST['cake_id'] ?? '';
    $name = $_POST['cake_name'] ?? '';
    $employeeId = $_POST['employee_id'] ?? null;
    $description = $_POST['cake_description'] ?? '';
    $price = $_POST['cake_price'] ?? '';
    $discount = isset($_POST['cake_discount']) && is_numeric($_POST['cake_discount']) && (int)$_POST['cake_discount'] > 0
        ? (int)$_POST['cake_discount']
        : null;

    $stock = $_POST['cake_stock'] ?? '';
    $categoryId = $_POST['cake_category_id'] ?? '';

    if (!$employeeId) {
        redirectWithMessage("../cake.php", "Missing employee ID.");
    }

    $imagePath = null;

    if (!empty($_FILES['cake_image']['name'])) {
        $upload_dir = '../../assets/uploads/cakes/';
        $original_name = $_FILES['cake_image']['name'];
        $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $unique_name = 'cake_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
        $target_file = $upload_dir . $unique_name;

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($_FILES['cake_image']['tmp_name']);

        if (!in_array($file_type, $allowed_types)) {
            redirectWithMessage("../cake.php", "Only JPEG, PNG, and GIF images are allowed.");
        }

        if (!move_uploaded_file($_FILES['cake_image']['tmp_name'], $target_file)) {
            redirectWithMessage("../cake.php", "Failed to upload image.");
        }

        $imagePath = $unique_name;
    }

    try {
        $sql = "UPDATE Cakes SET 
                    Name = :name, 
                    Description = :description, 
                    Price = :price, 
                    DiscountPrice = :discount, 
                    StockCount = :stock, 
                    CategoryId = :categoryId"
            . ($imagePath ? ", ImagePath = :image" : "") .
            " WHERE Id = :id";

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':discount', $discount);
        $stmt->bindParam(':stock', $stock);
        $stmt->bindParam(':categoryId', $categoryId);
        $stmt->bindParam(':id', $cakeId);

        if ($imagePath) {
            $stmt->bindParam(':image', $imagePath);
        }

        $stmt->execute();

        $statusStmt = $conn->prepare("SELECT Id FROM Status WHERE StatusName = 'ACTIVE' LIMIT 1");
        $statusStmt->execute();
        $statusRow = $statusStmt->fetch(PDO::FETCH_ASSOC);

        if (!$statusRow) {
            throw new Exception("ACTIVE status not found.");
        }

        $statusId = $statusRow['Id'];
        $now = date('Y-m-d H:i:s');
        $statusInsertStmt = $conn->prepare("INSERT INTO CakeStatus (CakeId, StatusId, EmployeeId, DateCreated) 
                                            VALUES (?, ?, ?, ?)");
        $statusInsertStmt->execute([$cakeId, $statusId, $employeeId, $now]);

        redirectWithMessage("../cake.php", "Cake modify successfully!", true);
    } catch (Exception $e) {
        redirectWithMessage("../cake.php", "An error occurred: " . $e->getMessage());
    }
}

redirectWithMessage("../cake.php", "Invalid request.");
