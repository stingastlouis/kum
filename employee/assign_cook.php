<?php
include '../configs/db.php';
include '../configs/timezoneConfigs.php';
require_once './utils/redirectMessage.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = trim($_POST['order_id']);
    $cook_id = trim($_POST['cook_id']);
    $employeeId = trim($_POST['employee_id']);
    $date = date('Y-m-d H:i:s');

    if (empty($employeeId) || empty($cook_id) || empty($orderId)) {
        redirectWithMessage("order.php", "Missing required fields");
    }

    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare("INSERT INTO OrderAssignment (OrderId, CookId, AssignedBy, DateCreated) VALUES (:orderId, :cookId, :employeeId, :dateCreated)");
        $stmt->bindParam(':orderId', $orderId);
        $stmt->bindParam(':cookId', $cook_id);
        $stmt->bindParam(':employeeId', $employeeId);
        $stmt->bindParam(':dateCreated', $date);
        $stmt->execute();


        $statusStmt = $conn->prepare("SELECT Id FROM Status WHERE StatusName = 'READY TO BAKE' LIMIT 1");
        $statusStmt->execute();
        $status = $statusStmt->fetch(PDO::FETCH_ASSOC);

        if (!$status) {
            $conn->rollBack();
            redirectWithMessage("order.php", "READY TO BAKE status not found in database");
        }

        $pendingStatusId = $status['Id'];
        $insertStmt = $conn->prepare("
            INSERT INTO OrderStatus (OrderId, StatusId, EmployeeId, DateCreated)
            VALUES (:orderId, :statusId, :employeeId, :dateCreated)
        ");

        $insertStmt->bindParam(':orderId', $orderId);
        $insertStmt->bindParam(':statusId', $pendingStatusId);
        $insertStmt->bindParam(':employeeId', $employeeId);
        $insertStmt->bindParam(':dateCreated', $date);
        $insertStmt->execute();
        $conn->commit();

        redirectWithMessage("order.php", "Cook assigned and status set to READY TO BAKE successfully!", true);
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        redirectWithMessage("order.php", "Database Error: " . $e->getMessage());
    }
} else {
    header("Location: order.php");
    exit;
}
