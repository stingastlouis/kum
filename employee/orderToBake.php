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
  .order-items-row {
    background-color: #f9f9f9;
  }

  .order-items-container img {
    max-height: 80px;
  }
</style>
<div class="container mt-5">
  <h2 class="mb-4">Customer Orders</h2>
  <table class="table table-bordered table-hover">
    <thead class="table-light">
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
        <tr>
          <td><?= htmlspecialchars($order['OrderId']) ?></td>
          <td><?= htmlspecialchars($order['CustomerId']) ?></td>
          <td><?= htmlspecialchars(number_format($order['Total'], 2)) ?></td>
          <td><?= htmlspecialchars($order['OrderDate']) ?></td>
          <td><?= htmlspecialchars($order['ScheduleDate']) ?></td>
          <td><?= htmlspecialchars($order['StatusName']) ?></td>
          <td>
            <button class="btn btn-primary btn-sm view-items-btn" data-order-id="<?= $order['OrderId'] ?>">
              View Items
            </button>
            <form method="POST" action="status/add_orderStatus.php" style="margin: 0;">
              <input type="hidden" name="order_id" value="<?= $order['OrderId'] ?>">
              <input type="hidden" name="employee_id" value="<?= $employeeId ?>">
              <input type="hidden" name="status_id" value="<?= $statusBakedId ?>">
              <button type="submit">Baked</button>
            </form>
          </td>
        </tr>
        <tr class="order-items-row d-none" id="items-row-<?= $order['OrderId'] ?>">
          <td colspan="7">
            <div class="order-items-container p-2">
              <?php
              if (!empty($itemsByOrder[$order['OrderId']])) {
                echo "<div class='list-group'>";
                foreach ($itemsByOrder[$order['OrderId']] as $item) {
                  echo "<div class='list-group-item'>";
                  echo "<strong>Product Type:</strong> " . htmlspecialchars($item['ProductType']) . "<br>";
                  echo "<strong>Quantity:</strong> " . htmlspecialchars($item['Quantity']) . "<br>";
                  echo "<strong>Price:</strong> $" . number_format($item['Price'], 2) . "<br>";

                  if ($item['ProductType'] === 'cake') {
                    echo "<div class='mt-2'>";
                    echo "<strong>Cake Name:</strong> " . htmlspecialchars($item['CakeName']) . "<br>";
                    if ($item['CakeImage']) {
                      echo "<img src='../assets/uploads/cakes/" . htmlspecialchars($item['CakeImage']) . "' alt='Cake Image' class='mt-2'><br>";
                    }
                    echo "<em>" . nl2br(htmlspecialchars($item['CakeDescription'])) . "</em>";
                    echo "</div>";
                  } elseif ($item['ProductType'] === 'giftbox') {
                    $giftItems = $giftboxCakes[$item['Id']] ?? [];
                    if (!empty($giftItems)) {
                      echo "<div class='mt-2'><strong>Giftbox contains:</strong><ul>";
                      foreach ($giftItems as $gbCake) {
                        echo "<li>";
                        echo htmlspecialchars($gbCake['Name']) . " (Qty: " . $gbCake['Quantity'] . ")";
                        if ($gbCake['ImagePath']) {
                          echo "<br><img src='../assets/uploads/cakes/" . htmlspecialchars($gbCake['ImagePath']) . "' alt='Cake Image' style='max-height:60px;' class='mt-1'>";
                        }
                        echo "</li>";
                      }
                      echo "</ul></div>";
                    } else {
                      echo "<em>No cakes selected for this giftbox.</em>";
                    }
                  }
                  echo "</div>";
                }
                echo "</div>";
              } else {
                echo "<p>No items found for this order.</p>";
              }
              ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
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