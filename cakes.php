<?php include "includes/header.php" ?>
<link rel="stylesheet" href="assets/css/cart-side.css">
<div class="container py-4">

    <h1 class="text-center mb-4">Cake Page</h1>
    <div class="row">
        <?php
        include './configs/db.php';

        try {
            $stmt = $conn->prepare("
                SELECT p.*, 
                       ps.StatusId, 
                       s.StatusName
                FROM Cakes p
                LEFT JOIN CakeStatus ps ON p.Id = ps.CakeId
                LEFT JOIN Status s ON ps.StatusId = s.Id
                WHERE s.StatusName != 'INACTIVE'
                ORDER BY p.DateCreated DESC;
            ");

            $stmt->execute();
            $cakes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($cakes)) {
                echo '<div class="row">';
                foreach ($cakes as $cake) {
                    $hasDiscount = !empty($cake['DiscountPrice']) && $cake['DiscountPrice'] > 0;
                    $isOutOfStock = empty($cake['StockCount']) || $cake['StockCount'] <= 0;

                    echo ' 
                        <div class="col-md-3 mb-4">
                            <div class="card" style="height: 350px; display: flex; flex-direction: column; justify-content: space-between; padding: 10px;">
                                <img src="./assets/uploads/' . htmlspecialchars($cake['ImagePath']) . '" class="card-img-top" alt="' . htmlspecialchars($cake['Name']) . '" style="object-fit: cover; height: 150px;">
                                <div class="card-body d-flex flex-column p-2">
                                    <h5 class="card-title" style="font-size: 1.1rem;">' . htmlspecialchars($cake['Name']) . '</h5>
                                    <p class="card-text" style="font-size: 0.9rem;">' . htmlspecialchars($cake['Description']) . '</p>
                                    <p class="card-text">
                                        ' . ($hasDiscount ? '
                                            <strong style="font-size: 1.2rem; color: red;">$' . number_format($cake['DiscountPrice'], 2) . '</strong>
                                            <span style="text-decoration: line-through; color: grey; font-size: 1rem;">$' . number_format($cake['Price'], 2) . '</span>
                                        ' : '
                                            <strong>$' . number_format($cake['Price'], 2) . '</strong>
                                        ') . '
                                    </p>
                                    <p class="card-text" style="font-size: 0.8rem;">
                                        <strong>Stock:</strong> ' . intval($cake['StockCount']) . '
                                    </p>
                                    <div class="d-flex align-items-center mt-auto">
                                        <input type="number" class="form-control me-2 quantity-input" min="1" max="' . intval($cake['StockCount']) . '" value="1" style="width: 60px;" ' . ($isOutOfStock ? 'disabled' : '') . '>
                                        <button class="btn btn-secondary add-to-cart" 
                                            data-id="' . htmlspecialchars($cake['Id']) . '" 
                                            data-name="' . htmlspecialchars($cake['Name']) . '" 
                                            data-price="' . number_format($hasDiscount ? $cake['DiscountPrice'] : $cake['Price'], 2) . '" 
                                            data-stock="' . intval($cake['StockCount']) . '" 
                                            ' . ($isOutOfStock ? 'disabled' : '') . '>
                                            Add to Cart
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>';
                }
                echo '</div>';
            } else {
                echo '<p>No cakes available.</p>';
            }
        } catch (PDOException $e) {
            echo 'Error: ' . $e->getMessage();
        }
        ?>
    </div>
</div>

<div id="cart-container" class="mt-4">
    <h4 class="text-center mb-4">Your Cart</h4>

    <div class="card shadow-sm">
        <div class="card-body">
            <ul id="cart-items" class="list-group mb-4">
            </ul>

            <div class="d-flex justify-content-between align-items-center">
                <strong class="h5">Total:</strong>
                <span id="cart-total" class="h5 text-success">$0.00</span>
            </div>

            <div class="d-flex justify-content-between mt-3">
                <button id="checkout-button" class="btn btn-primary w-48 py-2">Checkout</button>
                <button id="clear-cart-button" class="btn btn-secondary w-48 py-2" onclick="clearCart()">Clear Cart</button>
            </div>
        </div>
    </div>
</div>

<script src="./cart/cart.js"></script>


<?php include "includes/footer.php" ?>
