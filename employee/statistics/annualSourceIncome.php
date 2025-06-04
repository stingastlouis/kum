<?php
include '../configs/db.php';

$currentYear = date('Y');

$revenueByType = [
    'cake' => 0,
    'giftbox' => 0
];

$sql = "
    SELECT ProductType, SUM(SubTotal) AS totalRevenue
    FROM OrderItems
    WHERE YEAR(DateCreated) = :currentYear
    GROUP BY ProductType
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(':currentYear', $currentYear, PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);


foreach ($results as $row) {
    $type = strtolower($row['ProductType']);
    if (isset($revenueByType[$type])) {
        $revenueByType[$type] = (float)$row['totalRevenue'];
    }
}

$sourceIncomeLabels = ['Cake', 'Giftbox'];
$data = [
    $revenueByType['cake'],
    $revenueByType['giftbox']
];

$sourceIncomeLabelsJson = json_encode($sourceIncomeLabels);
$dataJson = json_encode($data);
?>

<div class="col-lg-5 col-xl-4">
    <div class="card shadow mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="text-secondary fw-bold m-0">Revenue Sources - <?php echo $currentYear; ?></h6>
        </div>
        <div class="card-body">
            <div class="chart-area">
                <canvas id="revenueChart"></canvas>
            </div>
            <div class="text-center small mt-4">
                <span class="me-2"><i class="fas fa-circle" style="color: #4e73df;"></i>&nbsp;Cake</span>
                <span class="me-2"><i class="fas fa-circle" style="color: #1cc88a;"></i>&nbsp;Giftbox</span>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const ctxRevenue = document.getElementById('revenueChart').getContext('2d');
    const sourceIncomeLabels = <?php echo $sourceIncomeLabelsJson; ?>;
    const data = <?php echo $dataJson; ?>;

    const revenueChart = new Chart(ctxRevenue, {
        type: 'doughnut',
        data: {
            labels: sourceIncomeLabels,
            datasets: [{
                label: 'Revenue',
                data: data,
                backgroundColor: ['#4e73df', '#1cc88a'],
                borderColor: ['#ffffff', '#ffffff'],
                borderWidth: 2,
                hoverOffset: 30
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            let value = context.parsed;
                            return `${label}: $${value.toLocaleString()}`;
                        }
                    }
                }
            }
        }
    });
</script>