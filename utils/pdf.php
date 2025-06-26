<?php

require_once './libs/fpdf.php';

function generateShortExternalId($length = 8): string
{
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $id = '';
    for ($i = 0; $i < $length; $i++) {
        $id .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $id;
}

function createReceiptPDF($externalId, $orderId, $customerId, $totalAmount, $paymentMethodName, $conn, $deliveryIncluded = false)
{
    $pdf = new FPDF();
    $pdf->AddPage();

    $pdf->SetFont('Arial', 'B', 18);
    $pdf->Cell(0, 10, "KUM Cake Shop Receipt", 0, 1, 'C');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 6, "Receipt #: $externalId", 0, 1, 'C');
    $pdf->Cell(0, 6, "Date: " . date('d M Y, H:i'), 0, 1, 'C');
    $pdf->Ln(4);
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(5);

    $stmt = $conn->prepare("SELECT FullName FROM Customer WHERE Id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, "Customer: {$customer['FullName']}", 0, 1);
    $pdf->Cell(0, 8, "Order ID: $orderId", 0, 1);
    $pdf->Cell(0, 8, "Payment Method: $paymentMethodName", 0, 1);
    $pdf->Ln(5);

    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(80, 8, "Item", 0);
    $pdf->Cell(30, 8, "Quantity", 0);
    $pdf->Cell(40, 8, "Type", 0);
    $pdf->Cell(40, 8, "Subtotal ($)", 0);
    $pdf->Ln();

    if ($deliveryIncluded) {
        $pdf->Cell(80, 10, 'Delivery Fee', 0);
        $pdf->Cell(30, 10, '', 0);
        $pdf->Cell(40, 10, '', 0);
        $pdf->Cell(40, 10, '20.00', 0, 1, '$');
    }

    $itemStmt = $conn->prepare("SELECT OI.Quantity, OI.Price, OI.Subtotal, OI.ProductType, 
        CASE WHEN OI.ProductType = 'cake' THEN C.Name
             WHEN OI.ProductType = 'giftbox' THEN G.Name
             ELSE 'Unknown'
        END AS ProductName,
        OI.Id AS OrderItemId
        FROM OrderItems OI
        LEFT JOIN Cakes C ON (OI.ProductType = 'cake' AND OI.ProductId = C.Id)
        LEFT JOIN GiftBox G ON (OI.ProductType = 'giftbox' AND OI.ProductId = G.Id)
        WHERE OI.OrderId = ?");
    $itemStmt->execute([$orderId]);
    $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);

    $pdf->SetFont('Arial', '', 11);

    foreach ($items as $item) {
        $pdf->Cell(80, 8, $item['ProductName'], 0);
        $pdf->Cell(30, 8, $item['Quantity'], 0);
        $pdf->Cell(40, 8, ucfirst($item['ProductType']), 0);
        $pdf->Cell(40, 8, number_format($item['Subtotal'], 2), 0);
        $pdf->Ln();

        if ($item['ProductType'] === 'giftbox') {
            $giftStmt = $conn->prepare("SELECT GBS.Quantity, C.Name 
                FROM GiftBoxSelection GBS
                JOIN Cakes C ON GBS.CakeId = C.Id
                WHERE GBS.OrderItemId = ?");
            $giftStmt->execute([$item['OrderItemId']]);
            $cakes = $giftStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($cakes as $cake) {
                $pdf->Cell(5);
                $pdf->Cell(170, 6, "- {$cake['Name']} x {$cake['Quantity']}", 0, 1);
            }
        }
    }

    $pdf->Ln(5);
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->Cell(0, 10, "Grand Total: $ " . number_format($totalAmount, 2), 0, 1, 'R');

    $receiptDir = './assets/uploads/receipts/';
    if (!is_dir($receiptDir)) mkdir($receiptDir, 0777, true);

    $filePath = $receiptDir . $externalId . '.pdf';
    $pdf->Output('F', $filePath);

    return $filePath;
}
