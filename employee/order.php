<?php
require_once 'auth.php';
requireEmployeeLogin([ROLE_ADMIN]);
require_once './utils/orderUtils.php';

$employeeId = $_SESSION['employeeId'] ?? null;
include 'includes/header.php';
include '../configs/db.php';

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$filters = [];
$params = [];

if (!empty($_GET['schedule_date'])) {
    $filters[] = 'DATE(o.ScheduleDate) = :schedule_date';
    $params[':schedule_date'] = $_GET['schedule_date'];
}

if (!empty($_GET['created_at'])) {
    $filters[] = 'DATE(o.DateCreated) = :created_at';
    $params[':created_at'] = $_GET['created_at'];
}

if (!empty($_GET['payment_method'])) {
    $filters[] = 'pm.Name LIKE :payment_method';
    $params[':payment_method'] = '%' . $_GET['payment_method'] . '%';
}

if (!empty($_GET['is_paid'])) {
    if ($_GET['is_paid'] === 'Yes') {
        $filters[] = "(pp.Id IS NOT NULL OR (cp.Id IS NOT NULL AND cp.DatePaid IS NOT NULL))";
    } elseif ($_GET['is_paid'] === 'No') {
        $filters[] = "(pp.Id IS NULL AND (cp.Id IS NULL OR cp.DatePaid IS NULL))";
    }
}

if (!empty($_GET['receipt_name'])) {
    $filters[] = 'r.FileName LIKE :receipt_name';
    $params[':receipt_name'] = '%' . $_GET['receipt_name'] . '%';
}


if (!empty($_GET['cook'])) {
    $filters[] = 'cook.Id = :cook';
    $params[':cook'] = $_GET['cook'];
}

$whereClause = '';
if (!empty($filters)) {
    $whereClause = 'WHERE ' . implode(' AND ', $filters);
}

$sql = "
    SELECT
        o.Id AS OrderId,
        o.Total,
        o.ScheduleDate,
        o.DateCreated AS OrderDate,
        pm.Name AS PaymentMethod,
        r.FileName AS ReceiptFileName,
        CASE 
            WHEN pp.Id IS NOT NULL THEN 'Yes'
            WHEN cp.Id IS NOT NULL AND cp.DatePaid IS NOT NULL THEN 'Yes'
            ELSE 'No'
        END AS IsPaid,
        s.StatusName AS LatestOrderStatus,
        d.Location AS DeliveryLocation,
        ds.StatusName AS LatestDeliveryStatus,
        e.Fullname AS DeliveryEmployeeName,
        cook.Fullname AS CookName
    FROM Orders o
    LEFT JOIN Payment p ON o.Id = p.OrderId
    LEFT JOIN PaymentMethod pm ON p.PaymentMethodId = pm.Id
    LEFT JOIN PaypalPayment pp ON p.Id = pp.PaymentId
    LEFT JOIN CashPayment cp ON p.Id = cp.PaymentId
    LEFT JOIN Receipt r ON o.Id = r.OrderId
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
    LEFT JOIN (
        SELECT oa1.*
        FROM OrderAssignment oa1
        INNER JOIN (
            SELECT OrderId, MAX(DateCreated) AS MaxDate
            FROM OrderAssignment
            GROUP BY OrderId
        ) oa2 ON oa1.OrderId = oa2.OrderId AND oa1.DateCreated = oa2.MaxDate
    ) oa ON o.Id = oa.OrderId
    LEFT JOIN Employee cook ON oa.CookId = cook.Id
    $whereClause
    ORDER BY o.Id DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $conn->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$countStmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM Orders o
    LEFT JOIN Payment p ON o.Id = p.OrderId
    LEFT JOIN PaymentMethod pm ON p.PaymentMethodId = pm.Id
    LEFT JOIN PaypalPayment pp ON p.Id = pp.PaymentId
    LEFT JOIN CashPayment cp ON p.Id = cp.PaymentId
    LEFT JOIN Receipt r ON o.Id = r.OrderId
    LEFT JOIN (
        SELECT oa1.*
        FROM OrderAssignment oa1
        INNER JOIN (
            SELECT OrderId, MAX(DateCreated) AS MaxDate
            FROM OrderAssignment
            GROUP BY OrderId
        ) oa2 ON oa1.OrderId = oa2.OrderId AND oa1.DateCreated = oa2.MaxDate
    ) oa ON o.Id = oa.OrderId
    LEFT JOIN Employee cook ON oa.CookId = cook.Id
    $whereClause
");

foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}

$countStmt->execute();
$totalOrders = $countStmt->fetchColumn();
$totalPages = ceil($totalOrders / $limit);


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

$stmtStatus = $conn->prepare(
    "SELECT * FROM Status 
    WHERE StatusName IN ('CANCELLED', 'CONFIRMED', 'READY FOR DELIVERY', 'OUT FOR DELIVERY', 'COMPLETED', 'DELIVERED', 
    'COLLECTED',
    'READY TO BAKE',
    'READY FOR PICKUP',
    'BAKED')
ORDER BY StatusName ASC"
);
$stmtStatus->execute();
$statuses = $stmtStatus->fetchAll(PDO::FETCH_ASSOC);

$stmtDeliveryStatus = $conn->prepare("SELECT * FROM Status WHERE StatusName IN ('OUT FOR DELIVERY', 'DELIVERED')");
$stmtDeliveryStatus->execute();
$Deliverystatuses = $stmtDeliveryStatus->fetchAll(PDO::FETCH_ASSOC);

$stmtRiders = $conn->prepare("
    SELECT Employee.Id, Employee.Fullname
    FROM Employee
    INNER JOIN Roles ON Employee.RoleId = Roles.Id
    WHERE Roles.Name = 'Rider'
    ORDER BY Employee.Fullname
");
$stmtRiders->execute();
$riders = $stmtRiders->fetchAll(PDO::FETCH_ASSOC);

$stmtCooks = $conn->prepare("
    SELECT Employee.Id, Employee.Fullname
    FROM Employee
    INNER JOIN Roles ON Employee.RoleId = Roles.Id
    WHERE Roles.Name = 'Cook'
    ORDER BY Employee.Fullname
");
$stmtCooks->execute();
$cooks = $stmtCooks->fetchAll(PDO::FETCH_ASSOC);

$totalStmt = $conn->query("SELECT COUNT(*) FROM Orders");
$totalOrders = $totalStmt->fetchColumn();
$totalPages = ceil($totalOrders / $limit);
?>
<div class="container-fluid" style="height: auto;">
    <h3 class="text-dark mb-4">Orders</h3>
    <div>
        <form method="GET" class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-3 mb-4">
            <div class="col">
                <label for="receipt_name" class="form-label">Receipt File Name</label>
                <input type="text" placeholder="Receipt Name" id="receipt_name" name="receipt_name" class="form-control" value="<?= htmlspecialchars($_GET['receipt_name'] ?? '') ?>">
            </div>

            <div class="col">
                <label for="schedule_date" class="form-label">Schedule Date</label>
                <input type="date" id="schedule_date" name="schedule_date" class="form-control" value="<?= htmlspecialchars($_GET['schedule_date'] ?? '') ?>">
            </div>

            <div class="col">
                <label for="created_at" class="form-label">Created At</label>
                <input type="date" id="created_at" name="created_at" class="form-control" value="<?= htmlspecialchars($_GET['created_at'] ?? '') ?>">
            </div>

            <div class="col">
                <label for="payment_method" class="form-label">Payment Method</label>
                <select id="payment_method" name="payment_method" class="form-select">
                    <option value="">All Payment Methods</option>
                    <?php
                    $pmStmt = $conn->query("SELECT Name FROM PaymentMethod ORDER BY Name ASC");
                    foreach ($pmStmt->fetchAll(PDO::FETCH_COLUMN) as $pm) {
                        $selected = (isset($_GET['payment_method']) && $_GET['payment_method'] == $pm) ? 'selected' : '';
                        echo "<option value=\"$pm\" $selected>$pm</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="col">
                <label for="is_paid" class="form-label">Paid?</label>
                <select id="is_paid" name="is_paid" class="form-select">
                    <option value="">Paid?</option>
                    <option value="Yes" <?= ($_GET['is_paid'] ?? '') === 'Yes' ? 'selected' : '' ?>>Yes</option>
                    <option value="No" <?= ($_GET['is_paid'] ?? '') === 'No' ? 'selected' : '' ?>>No</option>
                </select>
            </div>

            <div class="col">
                <label for="cook" class="form-label">Cook Name</label>
                <select id="cook" name="cook" class="form-select">
                    <option value="">All Cooks</option>
                    <?php foreach ($cooks as $cook):
                        $selected = (isset($_GET['cook']) && $_GET['cook'] == $cook) ? 'selected' : '';
                    ?>
                        <option value="<?= htmlspecialchars($cook['Id']) ?>" <?= $selected ?>>
                            <?= htmlspecialchars($cook['Fullname']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
                <a href="?" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </form>


    </div>
    <div class="card shadow-sm border-0">
        <div class="card-body p-3">
            <div class="table-responsive">
                <table class="table table-sm table-hover table-bordered align-middle mb-0 bg-white">
                    <thead class="table-light">
                        <tr>
                            <th>Order ID</th>
                            <th>Total</th>
                            <th>Schedule Date</th>
                            <th>Created At</th>
                            <th>Payment Method</th>
                            <th>Paid?</th>
                            <th>Cook</th>
                            <th>Latest Status</th>
                            <th>Delivery Employee</th>
                            <th>Latest Delivery Status</th>
                            <th>Receipt</th>
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
                                            echo '<input type="checkbox" class="form-check-input paid-toggle" data-order-id="' . $order['OrderId'] . '" ' . $checked . ' ' . $disabled . '>';
                                        } else {
                                            echo htmlspecialchars($order['IsPaid']);
                                        }
                                        ?>
                                    <?php else: ?>
                                        <?= $order['IsPaid']; ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($order['CookName'] ?? '') ?: 'N/A' ?></td>
                                <td><?= htmlspecialchars($order['LatestOrderStatus']) ?: 'No Status' ?></td>
                                <td><?= htmlspecialchars($order['DeliveryEmployeeName'] ?? '') ?: 'N/A' ?></td>
                                <td><?= htmlspecialchars($order['LatestDeliveryStatus'] ?? '') ?: 'N/A' ?></td>
                                <td>
                                    <a href="../<?= htmlspecialchars($order['ReceiptFileName']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">View Receipt</a>
                                </td>
                                <?php if (isEmployeeInRole(ROLE_ADMIN)): ?>
                                    <td>
                                        <?php if (!isOrderAndDeliveCompletedStatus($order['LatestOrderStatus'])): ?>
                                            <div class="d-flex flex-wrap gap-1">
                                                <?php if ($order['LatestOrderStatus'] != 'READY FOR DELIVERY'): ?>
                                                    <form method="POST" action="status/add_orderStatus.php" class="m-0">
                                                        <input type="hidden" name="order_id" value="<?= $order['OrderId'] ?>">
                                                        <input type="hidden" name="employee_id" value="<?= $employeeId ?>">
                                                        <select name="status_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                                            <option value="" disabled selected>Change Order Status</option>
                                                            <?php foreach (getFilteredStatuses($statuses, $order['LatestOrderStatus'], isset($order['DeliveryLocation'])) as $status): ?>
                                                                <option value="<?= $status['Id'] ?>"><?= htmlspecialchars($status['StatusName']) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </form>
                                                <?php endif ?>
                                                <?php if (!empty($order['DeliveryLocation']) && !empty($order['DeliveryEmployeeName'])): ?>
                                                    <form method="POST" action="status/add_deliveryStatus.php" class="m-0">
                                                        <input type="hidden" name="order_id" value="<?= $order['OrderId'] ?>">
                                                        <input type="hidden" name="employee_id" value="<?= $employeeId ?>">
                                                        <select name="status_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                                            <option value="" disabled selected>Change Delivery Status</option>
                                                            <?php foreach (getFilteredStatuses($Deliverystatuses, $order['LatestDeliveryStatus']) as $deliveryStatus): ?>
                                                                <option value="<?= $deliveryStatus['Id'] ?>"><?= htmlspecialchars($deliveryStatus['StatusName']) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </form>
                                                <?php endif ?>
                                                <?php if ($order['LatestOrderStatus'] == 'READY FOR DELIVERY' && $order['LatestDeliveryStatus'] != "OUT FOR DELIVERY"): ?>
                                                    <form method="POST" action="./assign_employee.php" class="m-0">
                                                        <input type="hidden" name="order_id" value="<?= $order['OrderId'] ?>">
                                                        <input type="hidden" name="employee_id" value="<?= $employeeId ?>">
                                                        <select name="deliveryGuy_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                                            <option value="" disabled selected>Assign Rider</option>
                                                            <?php foreach ($riders as $employee): ?>
                                                                <option value="<?= $employee['Id'] ?>"><?= htmlspecialchars($employee['Fullname']) ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </form>
                                                <?php endif ?>
                                                <?php if ($order['LatestOrderStatus'] == 'CONFIRMED'): ?>
                                                    <form method="POST" action="assign_cook.php" class="m-0">
                                                        <input type="hidden" name="order_id" value="<?= $order['OrderId'] ?>">
                                                        <input type="hidden" name="employee_id" value="<?= $employeeId ?>">
                                                        <select name="cook_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                                            <option value="" disabled selected>Assign Cook</option>
                                                            <?php foreach ($cooks as $employee): ?>
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
        </div>
    </div>

    <nav class="d-flex justify-content-center mt-4">
        <ul class="pagination pagination-sm">
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
                    <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

</div>



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