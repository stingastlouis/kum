<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';
require_once '../utils/redirectMessage.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statusId = trim($_POST['status_id']);
    $orderId = trim($_POST['order_id']);
    $employeeId = trim($_POST['employee_id']);
    $date = date('Y-m-d H:i:s');

    if (empty($statusId) || empty($orderId) || empty($employeeId)) {
        redirectWithMessage("../order.php", "Missing required fields");
    }

    try {
        $conn->beginTransaction();
        $statusCheckStmt = $conn->prepare("SELECT StatusName FROM Status WHERE Id = ?");
        $statusCheckStmt->execute([$statusId]);
        $status = $statusCheckStmt->fetch(PDO::FETCH_ASSOC);

        if (!$status) {
            $conn->rollBack();
            redirectWithMessage("../order.php", "Invalid status selected");
        }

        $insertStmt = $conn->prepare("INSERT INTO OrderStatus (OrderId, StatusId, EmployeeId, Datecreated) 
                                      VALUES (:orderid, :statusid, :employeeId, :datecreated)");
        $insertStmt->bindParam(':orderid', $orderId);
        $insertStmt->bindParam(':statusid', $statusId);
        $insertStmt->bindParam(':employeeId', $employeeId);
        $insertStmt->bindParam(':datecreated', $date);

        if (!$insertStmt->execute()) {
            $conn->rollBack();
            redirectWithMessage("../order.php", "Failed to update order status");
        }

        if (strtoupper($status['StatusName']) === 'CANCELLED') {
            $itemsStmt = $conn->prepare("SELECT Id, ProductType, ProductId, Quantity FROM OrderItems WHERE OrderId = ?");
            $itemsStmt->execute([$orderId]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($items as $item) {
                if (strtoupper($item['ProductType']) === 'CAKE') {
                    $updateCakeStockStmt = $conn->prepare("UPDATE Cakes SET StockCount = StockCount + :qty WHERE Id = :cakeId");
                    $updateCakeStockStmt->bindParam(':qty', $item['Quantity']);
                    $updateCakeStockStmt->bindParam(':cakeId', $item['ProductId']);
                    $updateCakeStockStmt->execute();
                } elseif (strtoupper($item['ProductType']) === 'GIFTBOX') {
                    $giftItemsStmt = $conn->prepare("SELECT CakeId, Quantity FROM GiftBoxSelection WHERE OrderItemId = ?");
                    $giftItemsStmt->execute([$item['Id']]);
                    $giftCakes = $giftItemsStmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($giftCakes as $cake) {
                        $restoreCakeStmt = $conn->prepare("UPDATE Cakes SET StockCount = StockCount + :qty WHERE Id = :cakeId");
                        $restoreCakeStmt->bindParam(':qty', $cake['Quantity']);
                        $restoreCakeStmt->bindParam(':cakeId', $cake['CakeId']);
                        $restoreCakeStmt->execute();
                    }
                }
            }
        }

        $conn->commit();
        redirectWithMessage("../order.php", "Order updated successfully!", true);
    } catch (PDOException $e) {
        $conn->rollBack();
        redirectWithMessage("../order.php", "Database error {$e}");
    }
} else {
    header("Location: ../order.php");
    exit;
}
