<?php 
include './configs/db.php';
session_start();

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["success" => false, "message" => "Invalid JSON data"]);
    exit();
}

$customerId = $_SESSION['customerId'];
$paymentMethodId = $data['paymentMethodId'] ?? 1;
$cartItems = $data['cartItems'];
$transactionId = $data['transactionId'] ?? null;
$location = $data['location'] ?? null;
$_SESSION['orderSuccess'] = true;
$totalAmount = $data['amount'];

// Validate that the payment method ID exists
$paymentMethodQuery = "SELECT Name FROM PaymentMethod WHERE Id = :paymentMethodId";
$paymentMethodStmt = $conn->prepare($paymentMethodQuery);
$paymentMethodStmt->bindValue(':paymentMethodId', $paymentMethodId, PDO::PARAM_INT);
$paymentMethodStmt->execute();
$paymentMethodName = $paymentMethodStmt->fetchColumn();
if (!$paymentMethodName) {
    echo json_encode(['success' => false, 'message' => 'Invalid payment method']);
    exit();
}

$query = "INSERT INTO `Orders` (CustomerId,Total, DateCreated) 
          VALUES (:customerId, :totalAmount, NOW())";
$stmt = $conn->prepare($query);
$stmt->bindValue(':customerId', $customerId, PDO::PARAM_INT);
$stmt->bindValue(':totalAmount', $totalAmount, PDO::PARAM_STR);

if ($stmt->execute()) {
    $orderId = $conn->lastInsertId(); 

    if ($location) {
        $deliveryQuery = "INSERT INTO `Delivery` (OrderId, Location, DateCreated)
                          VALUES (:orderId, :location, NOW())";
        $deliveryStmt = $conn->prepare($deliveryQuery);
        $deliveryStmt->bindValue(':orderId', $orderId, PDO::PARAM_INT);
        $deliveryStmt->bindValue(':location', $location, PDO::PARAM_STR);
        $deliveryStmt->execute();
    }

    foreach ($cartItems as $item) {
        $itemType = $item['type'];
        $productId = $item['id'];
        $quantity = $item['quantity'];
        $unitPrice = $item['price'];
        $subtotal = $unitPrice * $quantity;

        $query = "INSERT INTO `OrderItems` (OrderId, ProductId, Quantity, Price, Subtotal, DateCreated, `ProductType`) 
                  VALUES (:orderId, :productId, :quantity, :unitPrice, :subtotal, NOW(), :type)";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':orderId', $orderId, PDO::PARAM_INT);
        $stmt->bindValue(':productId', $productId, PDO::PARAM_INT);
        $stmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
        $stmt->bindValue(':unitPrice', $unitPrice, PDO::PARAM_STR);
        $stmt->bindValue(':subtotal', $subtotal, PDO::PARAM_STR);
        $stmt->bindValue(':type', $itemType, PDO::PARAM_STR);
        $stmt->execute();

        if ($itemType === 'cake') {
            $checkStockQuery = "SELECT StockCount FROM `Cakes` WHERE Id = :productId";
            $checkStockStmt = $conn->prepare($checkStockQuery);
            $checkStockStmt->bindValue(':productId', $productId, PDO::PARAM_INT);
            $checkStockStmt->execute();
            $productStock = $checkStockStmt->fetch(PDO::FETCH_ASSOC)['StockCount'];

            if ($productStock >= $quantity) {
                $updateStockQuery = "UPDATE `Cakes` SET StockCount = StockCount - :quantity WHERE Id = :productId";
                $updateStockStmt = $conn->prepare($updateStockQuery);
                $updateStockStmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
                $updateStockStmt->bindValue(':productId', $productId, PDO::PARAM_INT);
                $updateStockStmt->execute();
            } else {
                echo json_encode(['success' => false, 'message' => 'Not enough stock for Cake']);
                exit();
            }

        } elseif ($itemType === 'giftbox') {
            $giftBoxId = $item['id'];
            
            foreach ($item['cakes'] as $cake) {
                $cakeId = $cake['cakeId'];
                $cakeQuantity = $cake['quantity'] * $quantity;
        
                $checkStockQuery = "SELECT StockCount FROM `Cakes` WHERE Id = :cakeId";
                $checkStockStmt = $conn->prepare($checkStockQuery);
                $checkStockStmt->bindValue(':cakeId', $cakeId, PDO::PARAM_INT);
                $checkStockStmt->execute();
                $productStock = $checkStockStmt->fetch(PDO::FETCH_ASSOC)['StockCount'];
        
                if ($productStock >= $cakeQuantity) {
                    $updateStockQuery = "UPDATE `Cakes` SET StockCount = StockCount - :cakeQuantity WHERE Id = :cakeId";
                    $updateStockStmt = $conn->prepare($updateStockQuery);
                    $updateStockStmt->bindValue(':cakeQuantity', $cakeQuantity, PDO::PARAM_INT);
                    $updateStockStmt->bindValue(':cakeId', $cakeId, PDO::PARAM_INT);
                    $updateStockStmt->execute();
                } else {
                    echo json_encode(['success' => false, 'message' => 'Not enough stock for cake in gift box']);
                    exit();
                }

                $giftBoxSelectionQuery = "INSERT INTO `GiftBoxSelection` (OrderItemId, CakeId, Quantity) 
                                          VALUES (:orderItemId, :cakeId, :quantity)";
                $giftBoxSelectionStmt = $conn->prepare($giftBoxSelectionQuery);
                $giftBoxSelectionStmt->bindValue(':orderItemId', $orderId, PDO::PARAM_INT);
                $giftBoxSelectionStmt->bindValue(':cakeId', $cakeId, PDO::PARAM_INT);
                $giftBoxSelectionStmt->bindValue(':quantity', $cakeQuantity, PDO::PARAM_INT);
                $giftBoxSelectionStmt->execute();
            }
        }
    }

    $queryPayment = "INSERT INTO `Payment` (CustomerId, OrderId, PaymentMethodId, DateCreated) 
              VALUES (:customerId, :orderId, :paymentMethodId, NOW())";
    $stmtPayment = $conn->prepare($queryPayment);
    $stmtPayment->bindValue(':customerId', $customerId, PDO::PARAM_INT);
    $stmtPayment->bindValue(':orderId', $orderId, PDO::PARAM_INT);
    $stmtPayment->bindValue(':paymentMethodId', $paymentMethodId, PDO::PARAM_INT);
    $stmtPayment->execute();

    if ($stmtPayment->execute()) {
        $paymentId = $conn->lastInsertId();

       switch (strtolower($paymentMethodName)) {
        case 'paypal':
            $paypalQuery = "INSERT INTO `PaypalPayment` (PaymentId, TransactionId, DateCreated) 
                            VALUES (:paymentId, :transactionId, NOW())";
            $paypalStmt = $conn->prepare($paypalQuery);
            $paypalStmt->bindValue(':paymentId', $paymentId, PDO::PARAM_INT);
            $paypalStmt->bindValue(':transactionId', $transactionId, PDO::PARAM_STR);
            $paypalStmt->execute();
            break;

        case 'cash':
            $cashQuery = "INSERT INTO `CashPayment` (PaymentId, DateCreated) 
                          VALUES (:paymentId, NOW())";
            $cashStmt = $conn->prepare($cashQuery);
            $cashStmt->bindValue(':paymentId', $paymentId, PDO::PARAM_INT);
            $cashStmt->execute();
            break;
        default:
            error_log("Unsupported payment method: $paymentMethodName");
            break;
    }

    }

    echo json_encode(['success' => true, 'orderId' => $orderId]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to process the order']);
}


