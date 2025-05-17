<?php
include '../configs/db.php';

$monthlyEarnings = array_fill(1, 12, 0);
$currentYear = date('Y');
$sql = "
    SELECT MONTH(DateCreated) AS month, SUM(SubTotal) AS total
    FROM OrderItems
    WHERE YEAR(DateCreated) = :currentYear
    GROUP BY month
    ORDER BY month
";

$stmt = $conn->prepare($sql);
$stmt->bindValue(':currentYear', $currentYear, PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $row) {
    $monthlyEarnings[(int)$row['month']] = (float)$row['total'];
}
$earningsJson = json_encode(array_values($monthlyEarnings));
?>


<div class="col-lg-7 col-xl-8">
    <div class="card shadow mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="text-secondary fw-bold m-0">Earnings Overview - <?php echo $currentYear; ?></h6>
        </div>
        <div class="card-body">
            <div class="chart-area">
                <canvas id="earningsChart"></canvas>
            </div>
        </div>
    </div>
</div>




<script>
    const ctx = document.getElementById('earningsChart').getContext('2d');

    const earningsData = <?php echo $earningsJson; ?>;
    const labels = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Earnings',
                data: earningsData,
                fill: true,
                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                borderColor: 'rgba(78, 115, 223, 1)',
                tension: 0.3,
                pointRadius: 3,
                pointHoverRadius: 5,
                borderWidth: 2
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                x: {
                    grid: {
                        color: 'rgb(234, 236, 244)',
                        borderDash: [2],
                        drawOnChartArea: false,
                    },
                    ticks: {
                        color: '#858796',
                        padding: 20,
                    }
                },
                y: {
                    grid: {
                        color: 'rgb(234, 236, 244)',
                        borderDash: [2],
                    },
                    ticks: {
                        color: '#858796',
                        padding: 20,
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    },
                    beginAtZero: true
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let val = context.parsed.y;
                            return `Earnings: $${val.toLocaleString()}`;
                        }
                    }
                }
            }
        }
    });
</script>