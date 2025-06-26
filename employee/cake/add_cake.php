<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';
require_once '../utils/redirectMessage.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['cake_name'] ?? '';
    $employeeId = $_POST['employee_id'] ?? '';
    $categoryId = $_POST['cake_category_id'] ?? '';
    $description = $_POST['cake_description'] ?? '';
    $price = $_POST['cake_price'] ?? '';
    $discount_price = $_POST['cake_discount'] ?? null;
    $stock = $_POST['cake_stock'] ?? '';

    if (empty($employeeId)) {
        redirectWithMessage("../cake.php", "Missing employee ID.");
    }

    if (empty($_FILES['cake_image']['name'])) {
        redirectWithMessage("../cake.php", "Please upload an image.");
    }

    $upload_dir = '../../assets/uploads/cakes/';
    $original_name = $_FILES['cake_image']['name'];
    $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    $unique_name = 'cake_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
    $target_file = $upload_dir . $unique_name;

    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $file_type = mime_content_type($_FILES['cake_image']['tmp_name']);

    if (!in_array($file_type, $allowed_types)) {
        redirectWithMessage("../cake.php", "Only JPEG, PNG, and GIF files are allowed.");
    }

    if (!move_uploaded_file($_FILES['cake_image']['tmp_name'], $target_file)) {
        redirectWithMessage("../cake.php", "Unable to upload the image file.");
    }

    try {
        $conn->beginTransaction();
        $now = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO Cakes (Name, CategoryId, Description, Price, DiscountPrice, StockCount, ImagePath, DateCreated) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $categoryId, $description, $price, $discount_price, $stock, $unique_name, $now]);

        if ($stmt->rowCount() === 0) {
            redirectWithMessage("../cake.php", "Unable to insert the cake into the database.");
        }

        $cakeId = $conn->lastInsertId();

        $statusStmt = $conn->prepare("SELECT Id FROM Status WHERE StatusName = 'ACTIVE' LIMIT 1");
        $statusStmt->execute();
        $statusRow = $statusStmt->fetch(PDO::FETCH_ASSOC);

        if (!$statusRow) {
            redirectWithMessage("../cake.php", "'ACTIVE' status not found.");
        }

        $statusId = $statusRow['Id'];

        $statusInsertStmt = $conn->prepare("INSERT INTO CakeStatus (CakeId, StatusId, EmployeeId, DateCreated) 
                                            VALUES (?, ?, ?, NOW())");
        $statusInsertStmt->execute([$cakeId, $statusId, $employeeId]);

        $conn->commit();
        redirectWithMessage("../cake.php", "Cake added successfully!", true);
    } catch (Exception $e) {
        $conn->rollBack();
        redirectWithMessage("../cake.php", "An error occurred: " . $e->getMessage());
    }
}
