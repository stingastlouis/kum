<?php
require_once 'auth.php';
requireEmployeeLogin([ROLE_COOK]);
include '../configs/db.php';
include 'includes/header.php';
$employeeId = $_SESSION['employeeId'] ?? null;
$sql = "
SELECT o.Id AS OrderId, o.CustomerId, o.Total, o.DateCreated AS OrderDate, o.ScheduleDate,
       s.StatusName, os.DateCreated AS StatusDate
FROM Orders o
JOIN OrderStatus os ON os.OrderId = o.Id
JOIN Status s ON s.Id = os.StatusId
WHERE os.DateCreated = (
    SELECT MAX(DateCreated)
    FROM OrderStatus
    WHERE OrderId = o.Id
)
AND s.StatusName = 'READY TO BAKE'
ORDER BY os.DateCreated DESC
";
$stmt = $conn->query($sql);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$orderIds = array_column($orders, 'OrderId');
$itemsByOrder = [];
$giftboxCakes = [];

if (count($orderIds) > 0) {
  $placeholders = implode(',', array_fill(0, count($orderIds), '?'));

  $itemsStmt = $conn->prepare("
        SELECT oi.*, 
               c.Name AS CakeName, c.Description AS CakeDescription, c.ImagePath AS CakeImage
        FROM OrderItems oi
        LEFT JOIN Cakes c ON oi.ProductType = 'cake' AND c.Id = oi.ProductId
        WHERE oi.OrderId IN ($placeholders)
    ");
  $itemsStmt->execute($orderIds);
  $orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

  foreach ($orderItems as $item) {
    $itemsByOrder[$item['OrderId']][] = $item;
  }

  $giftboxStmt = $conn->prepare("
        SELECT gbs.OrderItemId, gbs.Quantity, c.Name, c.Description, c.ImagePath
        FROM GiftBoxSelection gbs
        JOIN Cakes c ON c.Id = gbs.CakeId
        WHERE gbs.OrderItemId IN (
            SELECT Id FROM OrderItems WHERE OrderId IN ($placeholders) AND ProductType = 'giftbox'
        )
    ");
  $giftboxStmt->execute($orderIds);
  $giftboxCakesRaw = $giftboxStmt->fetchAll(PDO::FETCH_ASSOC);

  foreach ($giftboxCakesRaw as $row) {
    $giftboxCakes[$row['OrderItemId']][] = $row;
  }
}

$stmtStatus = $conn->prepare("SELECT * FROM Status 
WHERE StatusName = 'BAKED' LIMIT 1;");
$stmtStatus->execute();
$statusBaked = $stmtStatus->fetch(PDO::FETCH_ASSOC);
$statusBakedId = $statusBaked["Id"] ?? null;
?>



<style>
 .bg-pink {
  background-color: #f78fb3 !important;
}
.table-pink {
  background-color: #ffe3ed !important;
}
.bg-light-pink {
  background-color: #fff0f5 !important;
}

</style>
<div class="container mt-5">
  <div class="card shadow rounded-4 border-0">
    <div class="card-header bg-pink text-white text-center rounded-top-4" style="background-color: #f78fb3;">
      <h2 class="mb-0 fw-bold">Customer Orders</h2>
    </div>
    <div class="card-body p-4">
      <table class="table table-hover align-middle table-bordered border-light rounded-4 overflow-hidden">
        <thead class="table-pink text-center text-dark" style="background-color: #ffe3ed;">
          <tr>
            <th>Order ID</th>
            <th>Customer ID</th>
            <th>Total ($)</th>
            <th>Order Date</th>
            <th>Schedule Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $order): ?>
            <tr class="text-center">
              <td><?= htmlspecialchars($order['OrderId']) ?></td>
              <td><?= htmlspecialchars($order['CustomerId']) ?></td>
              <td><span class="badge bg-light text-dark">$<?= number_format($order['Total'], 2) ?></span></td>
              <td><?= htmlspecialchars($order['OrderDate']) ?></td>
              <td><?= htmlspecialchars($order['ScheduleDate']) ?></td>
              <td>
                <span class="badge rounded-pill bg-info text-dark px-3">
                  <?= htmlspecialchars($order['StatusName']) ?>
                </span>
              </td>
              <td>
                <div class="d-flex justify-content-center gap-2">
                  <button class="btn btn-sm btn-outline-primary view-items-btn" data-order-id="<?= $order['OrderId'] ?>">
                    <i class="fas fa-eye me-1"></i> View Items
                  </button>
                  <form method="POST" action="status/add_orderStatus.php" class="d-inline">
                    <input type="hidden" name="order_id" value="<?= $order['OrderId'] ?>">
                    <input type="hidden" name="employee_id" value="<?= $employeeId ?>">
                    <input type="hidden" name="status_id" value="<?= $statusBakedId ?>">
                    <button type="submit" class="btn btn-sm btn-success">
                      <i class="fas fa-birthday-cake me-1"></i> Baked
                    </button>
                  </form>
                </div>
              </td>
            </tr>
            <tr class="order-items-row d-none bg-light-pink" id="items-row-<?= $order['OrderId'] ?>" style="background-color: #fff0f5;">
              <td colspan="7">
                <div class="order-items-container p-3 rounded-3 shadow-sm bg-white">
                  <?php if (!empty($itemsByOrder[$order['OrderId']])): ?>
                    <div class="list-group">
                      <?php foreach ($itemsByOrder[$order['OrderId']] as $item): ?>
                        <div class="list-group-item border-0 border-bottom mb-2 rounded-3 shadow-sm">
                          <strong>Product Type:</strong> <?= htmlspecialchars($item['ProductType']) ?><br>
                          <strong>Quantity:</strong> <?= htmlspecialchars($item['Quantity']) ?><br>
                          <strong>Price:</strong> $<?= number_format($item['Price'], 2) ?><br>

                          <?php if ($item['ProductType'] === 'cake'): ?>
                            <div class="mt-2">
                              <strong>Cake Name:</strong> <?= htmlspecialchars($item['CakeName']) ?><br>
                              <?php if ($item['CakeImage']): ?>
                                <img src="../assets/uploads/cakes/<?= htmlspecialchars($item['CakeImage']) ?>" alt="Cake Image" class="img-thumbnail mt-2" style="max-height: 100px;">
                              <?php endif; ?>
                              <p class="mt-2 text-muted fst-italic"><?= nl2br(htmlspecialchars($item['CakeDescription'])) ?></p>
                            </div>
                          <?php elseif ($item['ProductType'] === 'giftbox'): ?>
                            <div class="mt-2">
                              <strong>Giftbox contains:</strong>
                              <?php $giftItems = $giftboxCakes[$item['Id']] ?? []; ?>
                              <?php if (!empty($giftItems)): ?>
                                <ul class="list-unstyled ms-3">
                                  <?php foreach ($giftItems as $gbCake): ?>
                                    <li class="mb-2">
                                      <?= htmlspecialchars($gbCake['Name']) ?> (Qty: <?= $gbCake['Quantity'] ?>)
                                      <?php if ($gbCake['ImagePath']): ?>
                                        <br><img src="../assets/uploads/cakes/<?= htmlspecialchars($gbCake['ImagePath']) ?>" alt="Cake Image" class="img-thumbnail mt-1" style="max-height: 60px;">
                                      <?php endif; ?>
                                    </li>
                                  <?php endforeach; ?>
                                </ul>
                              <?php else: ?>
                                <p class="text-muted fst-italic">No cakes selected for this giftbox.</p>
                              <?php endif; ?>
                            </div>
                          <?php endif; ?>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  <?php else: ?>
                    <p class="text-muted">No items found for this order.</p>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
<script>
  document.querySelectorAll('.view-items-btn').forEach(button => {
    button.addEventListener('click', function() {
      const orderId = this.dataset.orderId;
      const row = document.getElementById('items-row-' + orderId);
      row.classList.toggle('d-none');
    });
  });
</script>