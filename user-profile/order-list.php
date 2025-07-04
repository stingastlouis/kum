<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
unset($_SESSION['cart']);
?>
<div class="tab-pane fade" id="order-history" role="tabpanel">
    <div class="card p-4">
        <h3>Order History</h3>
        <table class="table table-striped">
            <thead class="table-pink">
                <tr>
                    <th>#</th>
                    <th>Order Date</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Receipt</th>
                </tr>
            </thead>

            <tbody>
                <?php
                include 'configs/db.php';
                if (isset($_SESSION['customerId'])) {
                    try {
                        $limit = 5;
                        $page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
                        $offset = ($page - 1) * $limit;

                        $countStmt = $conn->prepare("SELECT COUNT(DISTINCT o.Id) FROM Orders o WHERE o.CustomerId = :customerId");
                        $countStmt->bindParam(':customerId', $_SESSION['customerId'], PDO::PARAM_INT);
                        $countStmt->execute();
                        $totalOrders = $countStmt->fetchColumn();
                        $totalPages = ceil($totalOrders / $limit);

                        $stmt = $conn->prepare("
            SELECT o.Id AS OrderId, o.Total, o.ScheduleDate AS OrderDate, 
                os.StatusId, s.StatusName, 
                r.FileName
            FROM Orders o
            LEFT JOIN OrderStatus os ON o.Id = os.OrderId 
            LEFT JOIN Status s ON os.StatusId = s.Id
            LEFT JOIN Receipt r ON o.Id = r.OrderId
            WHERE o.CustomerId = :customerId
            AND (
                os.Id = (
                    SELECT MAX(os_inner.Id) 
                    FROM OrderStatus os_inner 
                    WHERE os_inner.OrderId = o.Id
                ) OR os.Id IS NULL
            )
            ORDER BY o.Id DESC
            LIMIT :limit OFFSET :offset
        ");
                        $stmt->bindParam(':customerId', $_SESSION['customerId'], PDO::PARAM_INT);
                        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
                        $stmt->execute();

                        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        if (!empty($orders)) {
                            $index = $offset + 1;
                            foreach ($orders as $order) {
                                $orderDate = (new DateTime($order['OrderDate']))->format('Y-m-d');
                                $downloadBtn = $order['FileName']
                                    ? "<a href='./{$order['FileName']}' target='_blank' class='btn btn-sm btn-primary'>Download Receipt</a>"
                                    : 'Not Available';

                                echo "
                    <tr>
                        <td>{$index}</td>
                        <td>{$orderDate}</td>
                        <td>$ " . number_format($order['Total'], 2) . "</td>
                        <td>{$order['StatusName']}</td>
                        <td>{$downloadBtn}</td>
                    </tr>
                ";
                                $index++;
                            }
                        } else {
                            echo '<tr><td colspan="5" class="text-center">No orders found.</td></tr>';
                        }
                    } catch (PDOException $e) {
                        echo "<tr><td colspan='5'>Error: " . $e->getMessage() . "</td></tr>";
                    }
                }
                ?>

            </tbody>
        </table>

        <?php if ($totalPages > 1): ?>
            <nav>
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>#order-history"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>
<style>
    .pagination .page-item.active .page-link {
        background-color: #ff69b4;
        border-color: #ff69b4;
    }
</style>