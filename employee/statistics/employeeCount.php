<?php
include '../configs/db.php';

$stmt = $conn->prepare("SELECT COUNT(*) AS totalEmployees FROM Employee");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$totalEmployees = $result['totalEmployees'] ?? 0;
?>

<div class="col-md-6 col-xl-3 mb-4">
    <div class="card shadow border-start-secondary py-2">
        <div class="card-body">
            <div class="row align-items-center no-gutters">
                <div class="col me-2">
                    <div class="text-uppercase text-secondary fw-bold text-xs mb-1">
                        <span>Total Employees</span>
                    </div>
                    <div class="text-dark fw-bold h5 mb-0">
                        <span><?php echo number_format($totalEmployees); ?></span>
                    </div>
                </div>
                <div class="col-auto">
                    <i class="fas fa-users fa-3x text-gray-300"></i>
                </div>
            </div>
        </div>
    </div>
</div>