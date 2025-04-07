<?php include "includes/header.php" ?>
<style>
    body {
        background-color: #fff0f5;
    }

    .cake-title {
        color: #d63384;
        font-weight: bold;
        font-size: 2.5rem;
    }

    .cake-card {
        background-color: #fff;
        border: 2px solid #f8bbd0;
        border-radius: 1.5rem;
        padding: 1rem;
        height: 100%;
        transition: transform 0.2s ease-in-out;
    }

    .cake-card:hover {
        transform: scale(1.02);
    }

    .cake-card img {
        border-radius: 1rem;
        height: 150px;
        object-fit: cover;
    }

    .btn-primary,
    .btn-secondary {
        border-radius: 1rem;
        font-weight: bold;
    }

    .btn-primary {
        background-color: #ff69b4;
        border-color: #ff69b4;
    }

    .btn-primary:hover {
        background-color: #ff85c1;
        border-color: #ff85c1;
    }

    .btn-secondary {
        background-color: #ffd1dc;
        border-color: #ffd1dc;
        color: #333;
    }

    .btn-secondary:hover {
        background-color: #ffc0cb;
        border-color: #ffc0cb;
    }

    .card-title {
        color: #d63384;
    }

    .card-body p {
        font-size: 0.9rem;
    }

    #cart-container .card {
        border-radius: 1rem;
        border: 2px solid #f8bbd0;
    }
</style>

<div class="container py-4">
    <h1 class="text-center mb-5 cake-title">🎂 Our Yummiest Cakes 🎂</h1>
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
