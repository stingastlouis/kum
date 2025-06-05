<?php
require_once 'auth.php';
requireEmployeeLogin([ROLE_ADMIN]);
require_once '../configs/db.php';

header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);

$orderId = $input['orderId'] ?? null;
$datePaid = $input['datePaid'] ?? null;

if (!$orderId || !$datePaid) {
    echo json_encode(['success' => false, 'message' => 'Missing orderId or datePaid']);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datePaid)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit;
}

$stmt = $conn->prepare("SELECT cp.Id FROM Payment p
                        JOIN CashPayment cp ON p.Id = cp.PaymentId
                        WHERE p.OrderId = ?");
$stmt->execute([$orderId]);
$cashPaymentId = $stmt->fetchColumn();

if (!$cashPaymentId) {
    echo json_encode(['success' => false, 'message' => 'Cash payment record not found for this order']);
    exit;
}

$update = $conn->prepare("UPDATE CashPayment SET DatePaid = ? WHERE Id = ?");
$res = $update->execute([$datePaid, $cashPaymentId]);

if ($res) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed']);
}
