<?php include "includes/header.php" ?>

<link rel="stylesheet" href="./assets/css/cakes.css">

<div class="container py-4">
    <h1 class="text-center mb-5 cake-title"> Our Yummiest Cakes </h1>
    <div class="row">
        <?php
        include './configs/db.php';

        try {
            $stmt = $conn->prepare("
                SELECT c.*, cs.StatusId, s.StatusName
                FROM Cakes c
                LEFT JOIN CakeStatus cs ON c.Id = cs.CakeId
                LEFT JOIN Status s ON cs.StatusId = s.Id
                WHERE cs.DateCreated = (
                    SELECT MAX(cs_inner.DateCreated)
                    FROM CakeStatus cs_inner
                    WHERE cs_inner.CakeId = c.Id
                )
                AND s.StatusName != 'INACTIVE'
                ORDER BY c.DateCreated DESC;
            ");

            $stmt->execute();
            $cakes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($cakes)) {
                foreach ($cakes as $cake) {
                    $hasDiscount = !empty($cake['DiscountPrice']) && $cake['DiscountPrice'] > 0;
                    $isOutOfStock = empty($cake['StockCount']) || $cake['StockCount'] <= 0;

                    echo ' 
                        <div class="col-md-3 mb-4">
                            <div class="cake-card shadow-sm d-flex flex-column">
                                <img src="./assets/uploads/' . htmlspecialchars($cake['ImagePath']) . '" class="card-img-top mb-2" alt="' . htmlspecialchars($cake['Name']) . '">
                                <div class="card-body d-flex flex-column p-2">
                                    <h5 class="card-title">' . htmlspecialchars($cake['Name']) . '</h5>
                                    <p>' . htmlspecialchars($cake['Description']) . '</p>
                                    <p>
                                        ' . ($hasDiscount ? '
                                            <strong class="text-danger">$' . number_format($cake['DiscountPrice'], 2) . '</strong>
                                            <span style="text-decoration: line-through; color: grey;">$' . number_format($cake['Price'], 2) . '</span>
                                        ' : '
                                            <strong>$' . number_format($cake['Price'], 2) . '</strong>
                                        ') . '
                                    </p>
                                    <p style="font-size: 0.85rem;">
                                        <strong>Stock:</strong> ' . intval($cake['StockCount']) . '
                                    </p>
                                    <div class="d-flex align-items-center mt-auto">
                                        <input type="number" class="form-control me-2 quantity-input" min="1" max="' . intval($cake['StockCount']) . '" value="1" style="width: 60px;" ' . ($isOutOfStock ? 'disabled' : '') . '>
                                        <button class="btn btn-secondary add-to-cart" 
                                            data-id="' . htmlspecialchars($cake['Id']) . '" 
                                            data-name="' . htmlspecialchars($cake['Name']) . '" 
                                            data-type="cake" 
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
            } else {
                echo '<p>No cakes available.</p>';
            }
        } catch (PDOException $e) {
            echo 'Error: ' . $e->getMessage();
        }
        ?>
    </div>
</div>

<?php include "includes/footer.php" ?>
