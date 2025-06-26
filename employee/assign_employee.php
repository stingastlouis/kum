<?php
include '../configs/db.php';
include '../configs/timezoneConfigs.php';
require_once './utils/redirectMessage.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = trim($_POST['order_id']);
    $deliveryGuyId = trim($_POST['deliveryGuy_id']);
    $employeeId = trim($_POST['employee_id']);
    $date = date('Y-m-d H:i:s');

    if (empty($employeeId) || empty($deliveryGuyId) || empty($orderId)) {
        redirectWithMessage("order.php", "Missing required fields");
    }

    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare("UPDATE Delivery SET EmployeeId = :employeeId WHERE OrderId = :orderId");
        $stmt->bindParam(':employeeId', $deliveryGuyId);
        $stmt->bindParam(':orderId', $orderId);
        $stmt->execute();

        $deliveryIdStmt = $conn->prepare("SELECT Id FROM Delivery WHERE OrderId = :orderId LIMIT 1");
        $deliveryIdStmt->bindParam(':orderId', $orderId);
        $deliveryIdStmt->execute();
        $deliveryRow = $deliveryIdStmt->fetch(PDO::FETCH_ASSOC);

        if (!$deliveryRow) {
            $conn->rollBack();
            redirectWithMessage("order.php", "Delivery not found for this order.");
        }

        $deliveryId = $deliveryRow['Id'];
        $statusStmt = $conn->prepare("SELECT Id FROM Status WHERE StatusName = 'PENDING' LIMIT 1");
        $statusStmt->execute();
        $status = $statusStmt->fetch(PDO::FETCH_ASSOC);

        if (!$status) {
            $conn->rollBack();
            redirectWithMessage("order.php", "Pending status not found in database");
        }

        $pendingStatusId = $status['Id'];
        $insertStmt = $conn->prepare("
            INSERT INTO DeliveryStatus (DeliveryId, StatusId, EmployeeId, DateCreated)
            VALUES (:deliveryId, :statusId, :employeeId, :dateCreated)
        ");

        $insertStmt->bindParam(':deliveryId', $deliveryId);
        $insertStmt->bindParam(':statusId', $pendingStatusId);
        $insertStmt->bindParam(':employeeId', $employeeId);
        $insertStmt->bindParam(':dateCreated', $date);
        $insertStmt->execute();
        $conn->commit();

        redirectWithMessage("order.php", "Employee assigned and status set to PENDING successfully!", true);
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
