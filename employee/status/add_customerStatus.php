<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';
require_once '../utils/redirectMessage.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statusId = trim($_POST['status_id']);
    $customerId = trim($_POST['customer_id']);
    $date = date('Y-m-d H:i:s');
    if (empty($statusId) || empty($customerId)) {
        redirectWithMessage("../customer.php", "Missing required fields");
    }

    try {
        $date = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO CustomerStatus (CustomerId, StatusId, DateCreated) VALUES (:customerid, :statusid, :datecreated)");
        $stmt->bindParam(':customerid', $customerId);
        $stmt->bindParam(':statusid', $statusId);
        $stmt->bindParam(':datecreated', $date);

        if ($stmt->execute()) {
            redirectWithMessage("../customer.php", "Customer updated successfully!", true);
        } else {
            redirectWithMessage("../customer.php", "Database Error");
        }
    } catch (PDOException $e) {
        redirectWithMessage("../customer.php", "Database Error");
    }
} else {
    header("Location: ../customer.php");
    exit;
}
