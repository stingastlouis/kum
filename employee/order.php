<?php
require_once 'auth.php';
requireEmployeeLogin();

$employeeId = $_SESSION['employeeId'] ?? null;
include 'includes/header.php';
include '../configs/db.php';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$stmt = $conn->prepare("
    SELECT
        o.Id AS OrderId,
        o.Total,
        o.ScheduleDate,
        o.DateCreated AS OrderDate,
        pm.Name AS PaymentMethod,
        CASE 
            WHEN pp.Id IS NOT NULL THEN 'Yes'
            WHEN cp.Id IS NOT NULL AND cp.DatePaid IS NOT NULL THEN 'Yes'
            ELSE 'No'
        END AS IsPaid,
        s.StatusName AS LatestOrderStatus,
        d.Location AS DeliveryLocation,
        ds.StatusName AS LatestDeliveryStatus,
        e.Fullname AS DeliveryEmployeeName
    FROM Orders o
    LEFT JOIN Payment p ON o.Id = p.OrderId
    LEFT JOIN PaymentMethod pm ON p.PaymentMethodId = pm.Id
    LEFT JOIN PaypalPayment pp ON p.Id = pp.PaymentId
    LEFT JOIN CashPayment cp ON p.Id = cp.PaymentId
    LEFT JOIN (
        SELECT os.OrderId, st.StatusName
        FROM OrderStatus os
        JOIN Status st ON os.StatusId = st.Id
        WHERE os.Id IN (
            SELECT MAX(Id) FROM OrderStatus GROUP BY OrderId
        )
    ) s ON o.Id = s.OrderId
    LEFT JOIN Delivery d ON o.Id = d.OrderId
    LEFT JOIN Employee e ON d.EmployeeId = e.Id
    LEFT JOIN (
        SELECT ds.DeliveryId, st2.StatusName
        FROM DeliveryStatus ds
        JOIN Status st2 ON ds.StatusId = st2.Id
        WHERE ds.Id IN (
            SELECT MAX(Id) FROM DeliveryStatus GROUP BY DeliveryId
        )
    ) ds ON d.Id = ds.DeliveryId
    ORDER BY o.Id DESC
    LIMIT :limit OFFSET :offset
");


$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$orderIds = array_column($orders, 'OrderId');

$itemsByOrder = [];
if (!empty($orderIds)) {
    $inQuery = implode(',', array_fill(0, count($orderIds), '?'));
    $stmtCakeItems = $conn->prepare("
        SELECT 
            oi.OrderId, 
            c.Name AS ProductName, 
            oi.Quantity, 
            oi.SubTotal,
            'Cake' AS ProductType,
            c.ImagePath
        FROM OrderItems oi
        JOIN Cakes c ON oi.ProductId = c.Id
        WHERE oi.OrderId IN ($inQuery)
    ");
    $stmtCakeItems->execute($orderIds);
    $cakeItems = $stmtCakeItems->fetchAll(PDO::FETCH_ASSOC);

    $stmtGiftBoxItems = $conn->prepare("
        SELECT 
            oi.OrderId, 
            gb.Name AS ProductName, 
            oi.Quantity, 
            oi.SubTotal,
            'GiftBox' AS ProductType,
            gb.ImagePath
        FROM OrderItems oi
        JOIN GiftBox gb ON oi.ProductId = gb.Id
        WHERE oi.OrderId IN ($inQuery)
    ");
    $stmtGiftBoxItems->execute($orderIds);
    $giftBoxItems = $stmtGiftBoxItems->fetchAll(PDO::FETCH_ASSOC);

    $allItems = array_merge($cakeItems, $giftBoxItems);

    foreach ($allItems as $item) {
        $itemsByOrder[$item['OrderId']][] = $item;
    }
}

$stmtStatus = $conn->prepare("SELECT * FROM Status WHERE StatusName IN ('CONFIRMED', 'READY FOR PICKUP', 'OUT FOR DELIVERY', 'DELIVERED', 'CANCELLED')");
$stmtStatus->execute();
$statuses = $stmtStatus->fetchAll(PDO::FETCH_ASSOC);

$stmtDeliveryStatus = $conn->prepare("SELECT * FROM Status WHERE StatusName IN ('OUT FOR DELIVERY', 'DELIVERED', 'CANCELLED')");
$stmtDeliveryStatus->execute();
$Deliverystatuses = $stmtDeliveryStatus->fetchAll(PDO::FETCH_ASSOC);


$stmtEmployyes = $conn->prepare("SELECT Id, Fullname FROM Employee ORDER BY Fullname");
$stmtEmployyes->execute();
$employees = $stmtEmployyes->fetchAll(PDO::FETCH_ASSOC);

$totalStmt = $conn->query("SELECT COUNT(*) FROM Orders");
$totalOrders = $totalStmt->fetchColumn();
$totalPages = ceil($totalOrders / $limit);
?>
<div class="container-fluid">
    <h3 class="text-dark mb-4">Orders</h3>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Total</th>
                <th>Schedule Date</th>
                <th>Created At</th>
                <th>Payment Method</th>
                <th>Paid?</th>
                <th>Latest Status</th>
                <th>Delivery Employee</th>
                <th>Latest Delivery Status</th>
                <th>Items</th>
                <?php if (isEmployeeInRole(ROLE_ADMIN)): ?>
                    <th>Actions</th>
                <?php endif; ?>

            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= htmlspecialchars($order['OrderId']) ?></td>
                    <td><?= htmlspecialchars($order['Total']) ?></td>
                    <td><?= htmlspecialchars($order['ScheduleDate']) ?></td>
                    <td><?= htmlspecialchars($order['OrderDate']) ?></td>
                    <td><?= htmlspecialchars($order['PaymentMethod']) ?: 'N/A' ?></td>
                    <td>

                        <?php if (isEmployeeInRole(ROLE_ADMIN)): ?>
                            <?php
                            $isPaid = $order['IsPaid'] === 'Yes';
                            $isCash = strtolower($order['PaymentMethod']) === 'cash';

                            if ($isCash) {
                                $checked = $isPaid ? 'checked' : '';
                                $disabled = $isPaid ? 'disabled' : '';

                                echo '<input type="checkbox" class="paid-toggle" data-order-id="' . $order['OrderId'] . '" ' . $checked . ' ' . $disabled . '>';
                            } else {
                                echo htmlspecialchars($order['IsPaid']);
                            }
                            ?>
                        <?php else: ?>
                            <?php echo $order['IsPaid']; ?>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($order['LatestOrderStatus']) ?: 'No Status' ?></td>
                    <td><?= htmlspecialchars($order['DeliveryEmployeeName']) ?: 'N/A' ?></td>
                    <td><?= htmlspecialchars($order['LatestDeliveryStatus']) ?: 'N/A' ?></td>
                    <td>
                        <button class="btn btn-secondary btn-sm view-items-btn" data-order-id="<?= $order['OrderId'] ?>">
                            View Items
                        </button>
                    </td>
                    <?php if (isEmployeeInRole(ROLE_ADMIN)): ?>
                        <td>
                            <?php if ($order['LatestOrderStatus'] !== 'CANCELLED' && $order['LatestOrderStatus'] !== 'DELIVERED'): ?>
                                <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                    <form method="POST" action="status/add_orderStatus.php" style="margin: 0;">
                                        <input type="hidden" name="order_id" value="<?= $order['OrderId'] ?>">
                                        <input type="hidden" name="employee_id" value="<?= $employeeId ?>">
                                        <select name="status_id" class="form-select form-select-sm"
                                            style="width: 140px; background-color: #f8f9fa; color: #333; border: 1px solid #ccc;"
                                            onchange="this.form.submit()">
                                            <option value="" disabled selected>Change Order Status</option>
                                            <?php foreach ($statuses as $status): ?>
                                                <option value="<?= $status['Id'] ?>"><?= htmlspecialchars($status['StatusName']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>

                                    <?php if ($order['DeliveryLocation']): ?>
                                        <form method="POST" action="status/add_deliveryStatus.php" style="margin: 0;">
                                            <input type="hidden" name="order_id" value="<?= $order['OrderId'] ?>">
                                            <input type="hidden" name="employee_id" value="<?= $employeeId ?>">
                                            <select name="status_id" class="form-select form-select-sm"
                                                style="width: 140px; background-color: #f8f9fa; color: #333; border: 1px solid #ccc;"
                                                onchange="this.form.submit()">
                                                <option value="" disabled selected>Change Delivery Status</option>
                                                <?php foreach ($Deliverystatuses as $deliveryStatus): ?>
                                                    <option value="<?= $deliveryStatus['Id'] ?>"><?= htmlspecialchars($deliveryStatus['StatusName']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    <?php endif ?>

                                    <form method="POST" action="assign_employee.php" style="margin: 0;">
                                        <input type="hidden" name="order_id" value="<?= $order['OrderId'] ?>" />
                                        <input type="hidden" name="employee_id" value="<?= $employeeId ?>">
                                        <select name="deliveryGuy_id" class="form-select form-select-sm"
                                            style="width: 140px; background-color: #f8f9fa; color: #333; border: 1px solid #ccc;"
                                            onchange="this.form.submit()">
                                            <option value="" disabled selected>Assign Staff</option>
                                            <?php foreach ($employees as $employee): ?>
                                                <option value="<?= $employee['Id'] ?>"><?= htmlspecialchars($employee['Fullname']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                    <?php if ($order['LatestOrderStatus'] == 'CONFIRMED'): ?>
                                        <form method="POST" action="assign_cook.php" style="margin: 0;">
                                            <input type="hidden" name="order_id" value="<?= $order['OrderId'] ?>" />
                                            <input type="hidden" name="employee_id" value="<?= $employeeId ?>">
                                            <select name="cook_id" class="form-select form-select-sm"
                                                style="width: 140px; background-color: #f8f9fa; color: #333; border: 1px solid #ccc;"
                                                onchange="this.form.submit()">
                                                <option value="" disabled selected>Assign Cook</option>
                                                <?php foreach ($employees as $employee): ?>
                                                    <option value="<?= $employee['Id'] ?>"><?= htmlspecialchars($employee['Fullname']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    <?php endif ?>
                                </div>
                            <?php endif ?>
                        </td>
                    <?php endif ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<nav class="d-flex justify-content-center mt-4">
    <ul class="pagination">
        <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
            </li>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <li class="page-item">
                <a class="page-link " href="?page=<?= $page + 1 ?>">Next</a>
            </li>
        <?php endif; ?>
    </ul>
</nav>

<div class="modal fade" id="itemsModal" tabindex="-1" aria-labelledby="itemsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="itemsModalLabel">Order Items</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="itemsList">
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const itemsByOrder = <?= json_encode($itemsByOrder) ?>;

    document.addEventListener('DOMContentLoaded', () => {
        const itemsModal = new bootstrap.Modal(document.getElementById('itemsModal'));
        const itemsListContainer = document.getElementById('itemsList');

        document.querySelectorAll('.view-items-btn').forEach(button => {
            button.addEventListener('click', () => {
                const orderId = button.getAttribute('data-order-id');
                const items = itemsByOrder[orderId] || [];

                if (items.length === 0) {
                    itemsListContainer.innerHTML = '<p>No items found for this order.</p>';
                } else {
                    let html = '<div class="list-group">';
                    items.forEach(item => {
                        const folder = item.ProductType === 'Cake' ? 'cakes' : 'giftboxes';
                        const imageUrl = `../assets/uploads/${folder}/${item.ImagePath}`;

                        html += `
                        <div class="list-group-item d-flex align-items-center">
                            <img src="${imageUrl}" alt="${item.ProductName}" style="width:60px; height:60px; object-fit:cover; margin-right:10px;">
                            <div>
                                <strong>Product:</strong> ${item.ProductName}<br>
                                <strong>Type:</strong> ${item.ProductType}<br>
                                <strong>Quantity:</strong> ${item.Quantity}<br>
                                <strong>Subtotal:</strong> ${item.SubTotal}
                            </div>
                        </div>`;
                    });
                    html += '</div>';

                    itemsListContainer.innerHTML = html;
                }

                itemsModal.show();
            });
        });
    });
</script>


<div class="modal fade" id="paidDateModal" tabindex="-1" aria-labelledby="paidDateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="paidDateForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paidDateModalLabel">Select Payment Date</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="paidOrderId" name="orderId">
                <label for="datePaid">Payment Date:</label>
                <input type="date" id="datePaid" name="datePaid" class="form-control" required>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save Date</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const paidDateModal = new bootstrap.Modal(document.getElementById('paidDateModal'));
        const paidDateForm = document.getElementById('paidDateForm');
        const paidOrderIdInput = document.getElementById('paidOrderId');
        const datePaidInput = document.getElementById('datePaid');

        document.querySelectorAll('.paid-toggle').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                if (checkbox.checked) {
                    paidOrderIdInput.value = checkbox.getAttribute('data-order-id');
                    datePaidInput.value = '';
                    paidDateModal.show();
                }
            });
        });

        paidDateForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const orderId = paidOrderIdInput.value;
            const datePaid = datePaidInput.value;

            if (!datePaid) {
                alert('Please select a payment date.');
                return;
            }

            try {
                const response = await fetch('updateCashPayment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        orderId,
                        datePaid
                    })
                });
                const result = await response.json();

                if (result.success) {
                    paidDateModal.hide();
                    const checkbox = document.querySelector(`.paid-toggle[data-order-id="${orderId}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                        checkbox.disabled = true;
                    }

                    alert('Payment date saved successfully.');
                } else {
                    alert('Error saving payment date: ' + result.message);
                }
            } catch (err) {
                alert('Request failed: ' + err.message);
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>