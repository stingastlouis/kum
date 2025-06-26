<?php
include './configs/db.php';
include './configs/timezoneConfigs.php';
require_once './libs/fpdf.php';
require_once './utils/pdf.php';
session_start();

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid JSON data"]);
    exit();
}

try {
    $conn->beginTransaction();

    $customerId = $_SESSION['customerId'];
    $paymentMethodId = $data['paymentMethodId'] ?? 1;
    $cartItems = $data['cartItems'];
    $transactionId = $data['transactionId'] ?? null;
    $location = $data['location'] ?? null;
    $_SESSION['orderSuccess'] = true;
    $deliveryIncluded = $data['deliveryIncluded'] ?? false;
    $totalAmount = $data['amount'];
    $scheduleDate = $data['scheduleDate'] ?? null;

    $paymentMethodStmt = $conn->prepare("SELECT Name FROM PaymentMethod WHERE Id = :paymentMethodId");
    $paymentMethodStmt->bindValue(':paymentMethodId', $paymentMethodId, PDO::PARAM_INT);
    $paymentMethodStmt->execute();
    $paymentMethodName = $paymentMethodStmt->fetchColumn();
    if (!$paymentMethodName) {
        throw new Exception("Invalid payment method");
    }

    $now = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("INSERT INTO `Orders` (CustomerId, Total, DateCreated, ScheduleDate) VALUES (:customerId, :totalAmount, :dateNow, :scheduleDate)");
    $stmt->bindValue(':customerId', $customerId);
    $stmt->bindValue(':totalAmount', $totalAmount);
    $stmt->bindValue(':dateNow', $now);
    $stmt->bindValue(':scheduleDate', $scheduleDate);
    $stmt->execute();
    $orderId = $conn->lastInsertId();

    $statusStmt = $conn->prepare("SELECT Id FROM Status WHERE StatusName = 'PROCESSING' LIMIT 1");
    $statusStmt->execute();
    $statusId = $statusStmt->fetchColumn();

    if (!$statusId) {
        throw new Exception("Status 'PROCESSING' not found");
    }

    $orderStatusStmt = $conn->prepare("INSERT INTO OrderStatus (OrderId, StatusId, EmployeeId) VALUES (:orderId, :statusId, NULL)");
    $orderStatusStmt->execute([
        ':orderId' => $orderId,
        ':statusId' => $statusId
    ]);

    if ($location) {
        $deliveryStmt = $conn->prepare("INSERT INTO `Delivery` (OrderId, Location, DateCreated) VALUES (:orderId, :location, :date)");
        $deliveryStmt->bindValue(':orderId', $orderId);
        $deliveryStmt->bindValue(':location', $location);
        $deliveryStmt->bindValue(':date', $now);
        $deliveryStmt->execute();
    }

    foreach ($cartItems as $item) {
        $itemType = $item['type'];
        $productId = $item['id'];
        $quantity = $item['quantity'];
        $unitPrice = $item['price'];
        $subtotal = $unitPrice * $quantity;

        $stmt = $conn->prepare("INSERT INTO `OrderItems` (OrderId, ProductId, Quantity, Price, Subtotal, DateCreated, `ProductType`) VALUES (:orderId, :productId, :quantity, :unitPrice, :subtotal, NOW(), :type)");
        $stmt->execute([
            ':orderId' => $orderId,
            ':productId' => $productId,
            ':quantity' => $quantity,
            ':unitPrice' => $unitPrice,
            ':subtotal' => $subtotal,
            ':type' => $itemType
        ]);
        $orderItemId = $conn->lastInsertId();
        if ($itemType === 'cake') {
            $checkStockStmt = $conn->prepare("SELECT StockCount FROM `Cakes` WHERE Id = :productId FOR UPDATE");
            $checkStockStmt->execute([':productId' => $productId]);
            $productStock = $checkStockStmt->fetchColumn();

            if ($productStock < $quantity) {
                throw new Exception("Not enough stock for Cake");
            }

            $updateStockStmt = $conn->prepare("UPDATE `Cakes` SET StockCount = StockCount - :quantity WHERE Id = :productId");
            $updateStockStmt->execute([':quantity' => $quantity, ':productId' => $productId]);
        } elseif ($itemType === 'giftbox') {
            foreach ($item['cakes'] as $cake) {
                $cakeId = $cake['cakeId'];
                $cakeQuantity = $cake['quantity'] * $quantity;
                $checkStockStmt = $conn->prepare("SELECT StockCount FROM `Cakes` WHERE Id = :cakeId FOR UPDATE");
                $checkStockStmt->execute([':cakeId' => $cakeId]);
                $productStock = $checkStockStmt->fetchColumn();

                if ($productStock < $cakeQuantity) {
                    throw new Exception("Not enough stock for cake in gift box");
                }

                $updateStockStmt = $conn->prepare("UPDATE `Cakes` SET StockCount = StockCount - :cakeQuantity WHERE Id = :cakeId");
                $updateStockStmt->execute([':cakeQuantity' => $cakeQuantity, ':cakeId' => $cakeId]);

                $giftBoxSelectionStmt = $conn->prepare("INSERT INTO `GiftBoxSelection` (OrderItemId, CakeId, Quantity) VALUES (:orderItemId, :cakeId, :quantity)");
                $giftBoxSelectionStmt->execute([
                    ':orderItemId' => $orderItemId,
                    ':cakeId' => $cakeId,
                    ':quantity' => $cakeQuantity
                ]);
            }
        }
    }

    $stmtPayment = $conn->prepare("INSERT INTO `Payment` (CustomerId, OrderId, PaymentMethodId, DateCreated) VALUES (:customerId, :orderId, :paymentMethodId, :dateNow)");
    $stmtPayment->execute([
        ':customerId' => $customerId,
        ':orderId' => $orderId,
        ':paymentMethodId' => $paymentMethodId,
        ':dateNow' => $now
    ]);
    $paymentId = $conn->lastInsertId();

    if (strtolower($paymentMethodName) === 'paypal') {
        $paypalStmt = $conn->prepare("INSERT INTO `PaypalPayment` (PaymentId, TransactionId, DateCreated) VALUES (:paymentId, :transactionId, :dateNow)");
        $paypalStmt->execute([':paymentId' => $paymentId, ':transactionId' => $transactionId, ':dateNow' => $now]);
    } elseif (strtolower($paymentMethodName) === 'cash') {
        $cashStmt = $conn->prepare("INSERT INTO `CashPayment` (PaymentId, DateCreated) VALUES (:paymentId, :dateNow)");
        $cashStmt->execute([':paymentId' => $paymentId, ':dateNow' => $now]);
    }

    $externalId = generateShortExternalId();
    $pdfPath = createReceiptPDF($externalId, $orderId, $customerId, $totalAmount, $paymentMethodName, $conn, $deliveryIncluded);

    $stmtReceipt = $conn->prepare("INSERT INTO Receipt (OrderId, ExternalId, FileName, DateCreated) VALUES (:orderId, :externalId, :path, :dateNow)");
    $stmtReceipt->execute([
        ':orderId' => $orderId,
        ':externalId' => $externalId,
        ':path' => $pdfPath,
        ':dateNow' => $now
    ]);


    $conn->commit();
    echo json_encode(['success' => true, 'orderId' => $orderId]);
} catch (Exception $e) {
    $conn->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
