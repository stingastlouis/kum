<?php
include '../configs/db.php';

$firstDayOfMonth = date('Y-m-01 00:00:00');
$lastDayOfMonth = date('Y-m-t 23:59:59');

$sql = "SELECT COUNT(*) AS totalDeliveries 
        FROM Delivery 
        WHERE DateCreated BETWEEN :startDate AND :endDate";

$stmt = $conn->prepare($sql);
$stmt->bindValue(':startDate', $firstDayOfMonth);
$stmt->bindValue(':endDate', $lastDayOfMonth);
$stmt->execute();

$result = $stmt->fetch(PDO::FETCH_ASSOC);
$totalDeliveries = $result['totalDeliveries'] ?? 0;
?>
<div class="col-md-6 col-xl-3 mb-4">
    <div class="card shadow border-start-secondary py-2">
        <div class="card-body">
            <div class="row align-items-center no-gutters">
                <div class="col me-2">
                    <div class="text-uppercase text-secondary fw-bold text-xs mb-1">
                        <span>Delivery (monthly)</span>
                    </div>
                    <div class="text-dark fw-bold h5 mb-0">
                        <span><?php echo number_format($totalDeliveries); ?></span>
                    </div>
                </div>
                <div class="col-auto">
                    <i class="fas fa-truck fa-3x text-gray-300"></i>
                </div>
            </div>
        </div>
    </div>
</div>