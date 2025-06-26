<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';
require_once '../utils/redirectMessage.php';

$redirectUrl = $_SERVER['HTTP_REFERER'] ?? '../order.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = trim($_POST['order_id']);
    $statusId = trim($_POST['status_id']);
    $employeeId = trim($_POST['employee_id']);
    $date = date('Y-m-d H:i:s');

    if (empty($orderId) || empty($statusId) || empty($employeeId)) {
        redirectWithMessage($redirectUrl, "Missing required fields");
    }

    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare("SELECT Id FROM Delivery WHERE OrderId = :orderId LIMIT 1");
        $stmt->bindParam(':orderId', $orderId);
        $stmt->execute();
        $delivery = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$delivery) {
            $conn->rollBack();
            redirectWithMessage($redirectUrl, "Delivery not found for this order");
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

        $statusCheckStmt = $conn->prepare("SELECT StatusName FROM Status WHERE Id = ?");
        $statusCheckStmt->execute([$statusId]);
        $status = $statusCheckStmt->fetch(PDO::FETCH_ASSOC);

        if (!$status) {
            $conn->rollBack();
            redirectWithMessage($redirectUrl, "Invalid status selected");
        }

        if (strtoupper(trim($status['StatusName'])) === 'DELIVERED') {
            $paymentStmt = $conn->prepare("SELECT Id, PaymentMethodId FROM Payment WHERE OrderId = ?");
            $paymentStmt->execute([$orderId]);
            $payment = $paymentStmt->fetch(PDO::FETCH_ASSOC);

            if ($payment) {
                $cashCheckStmt = $conn->prepare("SELECT Id FROM CashPayment WHERE PaymentId = ?");
                $cashCheckStmt->execute([$payment['Id']]);
                $cashPayment = $cashCheckStmt->fetch(PDO::FETCH_ASSOC);

                if ($cashPayment) {
                    $updateCashStmt = $conn->prepare("UPDATE CashPayment SET DatePaid = :datePaid WHERE Id = :id");
                    $updateCashStmt->bindParam(':datePaid', $date);
                    $updateCashStmt->bindParam(':id', $cashPayment['Id']);
                    $updateCashStmt->execute();
                }
            }

            $completedStatusStmt = $conn->prepare("SELECT Id FROM Status WHERE UPPER(StatusName) = 'COMPLETED'");
            $completedStatusStmt->execute();
            $completedStatus = $completedStatusStmt->fetch(PDO::FETCH_ASSOC);

            if ($completedStatus) {
                $orderStatusInsertStmt = $conn->prepare("
                INSERT INTO OrderStatus (OrderId, StatusId, EmployeeId, DateCreated)
                VALUES (:orderId, :statusId, :employeeId, :dateCreated)
                ");
                $orderStatusInsertStmt->bindParam(':orderId', $orderId);
                $orderStatusInsertStmt->bindParam(':statusId', $completedStatus['Id']);
                $orderStatusInsertStmt->bindParam(':employeeId', $employeeId);
                $orderStatusInsertStmt->bindParam(':dateCreated', $date);
                $orderStatusInsertStmt->execute();
            }
        }

        $conn->commit();
        redirectWithMessage($redirectUrl, "Delivery status updated successfully!", true);
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        redirectWithMessage($redirectUrl, "Database Error: " . $e->getMessage());
    }
} else {
    header("Location: $redirectUrl");
    exit;
}
