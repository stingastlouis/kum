<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';
require_once '../utils/redirectMessage.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = trim($_POST['order_id']);
    $statusId = trim($_POST['status_id']);
    $employeeId = trim($_POST['employee_id']);
    $date = date('Y-m-d H:i:s');

    if (empty($orderId) || empty($statusId) || empty($employeeId)) {
        redirectWithMessage("../delivery.php", "Missing required fields");
    }

    try {
        $conn->beginTransaction();
        $stmt = $conn->prepare("SELECT Id FROM Delivery WHERE OrderId = :orderId LIMIT 1");
        $stmt->bindParam(':orderId', $orderId);
        $stmt->execute();
        $delivery = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$delivery) {
            $conn->rollBack();
            redirectWithMessage("../delivery.php", "Delivery not found for this order");
        }

        $deliveryId = $delivery['Id'];
        $insertStmt = $conn->prepare("
            INSERT INTO DeliveryStatus (DeliveryId, StatusId, EmployeeId, DateCreated)
            VALUES (:deliveryId, :statusId, :employeeId, :dateCreated)
        ");
        $insertStmt->bindParam(':deliveryId', $deliveryId);
        $insertStmt->bindParam(':statusId', $statusId);
        $insertStmt->bindParam(':employeeId', $employeeId);
        $insertStmt->bindParam(':dateCreated', $date);
        $insertStmt->execute();

        $conn->commit();

        redirectWithMessage("../delivery.php", "Delivery status updated successfully!", true);
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        redirectWithMessage("../delivery.php", "Database Error: " . $e->getMessage());
    }
} else {
    header("Location: ../delivery.php");
    exit;
}
