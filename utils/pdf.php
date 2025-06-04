<?php

require_once './libs/fpdf.php'; // Path to the FPDF file

function generateShortExternalId($length = 8): string {
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $id = '';
    for ($i = 0; $i < $length; $i++) {
        $id .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $id;
}

function createReceiptPDF($externalId, $orderId, $customerId, $totalAmount, $paymentMethod, $conn) {
    $pdf = new FPDF();
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',16);

    // Fetch customer name
    $stmt = $conn->prepare("SELECT FullName FROM Customer WHERE Id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch order items
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

    $pdf->Cell(0,10,"Receipt #$externalId",0,1);
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,10,"Customer: {$customer['FullName']}",0,1);
    $pdf->Cell(0,10,"Order ID: $orderId",0,1);
    $pdf->Cell(0,10,"Payment Method: $paymentMethod",0,1);
    $pdf->Ln(5);

    foreach ($items as $item) {
        $pdf->Cell(0,10,"{$item['ProductName']} ({$item['ProductType']}) - Qty: {$item['Quantity']} - Subtotal: Rs {$item['Subtotal']}",0,1);

        if ($item['ProductType'] === 'giftbox') {
            $giftStmt = $conn->prepare("SELECT GBS.Quantity, C.Name 
                FROM GiftBoxSelection GBS
                JOIN Cakes C ON GBS.CakeId = C.Id
                WHERE GBS.OrderItemId = ?");
            $giftStmt->execute([$item['OrderItemId']]);
            $cakes = $giftStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($cakes as $cake) {
                $pdf->Cell(0,10,"   - {$cake['Name']} x {$cake['Quantity']}",0,1);
            }
        }
    }

    $pdf->Ln(5);
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,10,"Total: Rs $totalAmount",0,1);

    $receiptDir = './assets/upload/receipts/';
    if (!is_dir($receiptDir)) mkdir($receiptDir, 0777, true);

    $filePath = $receiptDir . $externalId . '.pdf';
    $pdf->Output('F', $filePath); 

    return $filePath;
}


?>