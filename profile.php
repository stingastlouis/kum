<?php include "includes/header.php"; ?>
<link rel="stylesheet" href="./assets/css/profile.css">

<div class="container py-4">
    <h1 class="text-center mb-4">Welcome to Your Cake Profile </h1>

    <div class="profile-tabs mb-4">
        <ul class="nav nav-tabs justify-content-center" id="profileTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="user-info-tab" data-bs-toggle="tab" data-bs-target="#user-info" type="button" role="tab">User Info</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="order-history-tab" data-bs-toggle="tab" data-bs-target="#order-history" type="button" role="tab">Order History</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="queries-tab" data-bs-toggle="tab" data-bs-target="#queries" type="button" role="tab">Queries</button>
            </li>
        </ul>
    </div>

    <div class="tab-content" id="profileTabContent">
        <div class="tab-pane fade show active" id="user-info" role="tabpanel">
            <div class="card p-4">
                <?php
                include './configs/db.php';
                if (isset($_SESSION['customerId'])) {
                    try {
                        $stmt = $conn->prepare("SELECT * FROM Customer WHERE Id = :customerId");
                        $stmt->bindParam(':customerId', $_SESSION['customerId'], PDO::PARAM_INT);
                        $stmt->execute();
                        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($customer) {
                            echo "<h3>User Information</h3>";
                            echo "<p><strong>Name:</strong> " . htmlspecialchars($customer['Fullname']) . "</p>";
                            echo "<p><strong>Email:</strong> " . htmlspecialchars($customer['Email']) . "</p>";
                            echo "<p><strong>Phone:</strong> " . htmlspecialchars($customer['Phone']) . "</p>";
                            echo "<p><strong>Address:</strong> " . nl2br(htmlspecialchars($customer['Address'])) . "</p>";
                            echo "<p><strong>Account Created On:</strong> " . date("F j, Y", strtotime($customer['DateCreated'])) . "</p>";
                        } else {
                            echo "<p>Customer not found or session expired.</p>";
                        }
                    } catch (PDOException $e) {
                        echo "Error: " . $e->getMessage();
                    }
                } else {
                    echo "<p>Please log in to view your profile.</p>";
                }
                ?>
            </div>
        </div>

        <div class="tab-pane fade" id="order-history" role="tabpanel">
            <div class="card p-4">
                <h3>Order History</h3>
                <table class="table table-striped">
                    <thead class="table-pink">
                        <tr>
                            <th>#</th>
                            <th>Order Date</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    if (isset($_SESSION['customerId'])) {
                        try {
                            $stmt2 = $conn->prepare("
                             SELECT o.Id AS OrderId, o.Total, o.DateCreated AS OrderDate, 
                                    os.StatusId, s.StatusName, 
                                    oi.ProductId, p.Name AS ProductName, oi.Quantity, oi.Price, oi.Subtotal
                                FROM Orders o
                                LEFT JOIN OrderStatus os ON o.Id = os.OrderId 
                                LEFT JOIN Status s ON os.StatusId = s.Id
                                LEFT JOIN OrderItems oi ON o.Id = oi.OrderId
                                LEFT JOIN Cakes p ON oi.ProductId = p.Id
                                WHERE o.CustomerId = :customerId
                                AND (
                                    os.Id = (
                                        SELECT MAX(os_inner.Id) 
                                        FROM OrderStatus os_inner 
                                        WHERE os_inner.OrderId = o.Id
                                    ) OR os.Id IS NULL
                                )
                                ORDER BY o.DateCreated DESC;

                            ");

                            $stmt2->bindParam(':customerId', $_SESSION['customerId'], PDO::PARAM_INT);
                            $stmt2->execute();

                            $orders = $stmt2->fetchAll(PDO::FETCH_ASSOC);
                            if (!empty($orders)) {
                                $orderCount = 1;
                                $orderItems = [];
                                $orderStatuses = [];

                                foreach ($orders as $order) {
                                    $orderItems[$order['OrderId']][] = [
                                        'productName' => $order['ProductName'],
                                        'quantity' => $order['Quantity']
                                    ];
                                    $orderStatuses[$order['OrderId']] = $order['StatusName'] ?? 'Not Available';
                                }

                                foreach ($orderItems as $orderId => $items) {
                                    $itemList = '<ul>';
                                    foreach ($items as $item) {
                                        $itemList .= "<li>{$item['productName']} x {$item['quantity']}</li>";
                                    }
                                    $itemList .= '</ul>';
                                    $orderDate = (new DateTime($orders[0]['OrderDate']))->format('Y-m-d');

                                    $totalAmount = 0;
                                    foreach ($orders as $order) {
                                        if ($order['OrderId'] == $orderId) {
                                            $totalAmount = $order['Total'];
                                            break;
                                        }
                                    }

                                    echo "
                                        <tr>
                                            <td>{$orderCount}</td>
                                            <td>{$orderDate}</td>
                                            <td>{$itemList}</td>
                                            <td>Rs " . number_format($totalAmount, 2) . "</td>
                                            <td>{$orderStatuses[$orderId]}</td>
                                        </tr>
                                    ";
                                    $orderCount++;
                                }
                            } else {
                                echo '<tr><td colspan="5" class="text-center">No orders found.</td></tr>';
                            }
                        } catch (PDOException $e) {
                            echo "Error: " . $e->getMessage();
                        }
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="tab-pane fade" id="queries" role="tabpanel">
            <div class="card p-4">
                <h3>Queries</h3>
                <p>If you have any questions or queries, please feel free to ask below:</p>
                <form action="submit_query.php" method="POST">
                    <div class="mb-3">
                        <label for="query" class="form-label">Your Query</label>
                        <textarea class="form-control" id="query" name="query" rows="4" placeholder="Describe your query..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Query</button>
                </form>

                <h4 class="mt-4">Previous Queries</h4>
                <ul class="list-group">
                    <li class="list-group-item">Query 1: Order not received</li>
                    <li class="list-group-item">Query 2: Incorrect item delivered</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>
