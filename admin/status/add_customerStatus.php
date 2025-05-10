<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statusId = trim($_POST['status_id']);
    $customerId = trim($_POST['customer_id']);
    $date = date('Y-m-d H:i:s');
    if (empty($statusId) || empty( $customerId)) {
        echo "<h1>Field missing</h1></center>";
        exit;
    }

    try {
        $date = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO CustomerStatus (customerid, statusid, datecreated) VALUES (:customerid, :statusid, :datecreated)");
        $stmt->bindParam(':customerid', $customerId);
        $stmt->bindParam(':statusid', $statusId);
        $stmt->bindParam(':datecreated', $date);

        if ($stmt->execute()) {
            header("Location: ../customer.php?success=1");
            exit;
        } else {
            echo "Error adding staff.";
        }
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
    }
} else {
    header("Location: ../customer.php");
    exit;
}
?>
