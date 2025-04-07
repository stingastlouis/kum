<?php include "includes/header.php" ?>

<!-- Hero Section: Cake-Themed -->
<header class="bg-light py-5" style="background: linear-gradient(to right, #ffd6e0, #ffe6f0);">
    <div class="container">
        <div class="row align-items-center flex-column-reverse flex-md-row">
            <div class="col-md-6 text-center text-md-start">
                <h1 class="fw-bold text-pink" style="color: #d63384;">Delicious Cakes for Every Occasion</h1>
                <p class="lead text-muted mb-4">Treat yourself or someone special with our handcrafted cakes, baked with love and decorated to perfection.</p>
                <a href="cakes.php" class="btn btn-lg" style="background-color: #ff69b4; color: white; box-shadow: 0 4px 10px rgba(255, 105, 180, 0.4);">Explore Cakes</a>
            </div>
            <div class="col-md-6 text-center">
                <img src="assets/img/cake-hero.png" class="img-fluid rounded" alt="Hero Cake Image" style="max-height: 400px;">
            </div>
        </div>
    </div>
</header>

<?php 
include './configs/db.php';

try {
    $stmt = $conn->prepare("
        SELECT p.*, 
               ps.StatusId, 
               s.Name AS StatusName
        FROM Products p
        LEFT JOIN ProductStatus ps ON p.Id = ps.ProductId
        LEFT JOIN Status s ON ps.StatusId = s.Id
        WHERE ps.Id = (
            SELECT MAX(ps_inner.Id) 
            FROM ProductStatus ps_inner 
            WHERE ps_inner.ProductId = p.Id
        )
        AND LOWER(s.Name) = 'active'
        ORDER BY p.DateCreated DESC
        LIMIT 3;
    ");

    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!-- Latest Products -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold text-pink" style="color: #d63384;">Latest Cakes</h2>
            <p class="text-muted">Discover our newest cake creations, made with love and perfect for every sweet moment.</p>
        </div>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($products as $product): ?>
                <div class="col">
                    <div class="card shadow-sm h-100 border-pink" style="border: 1px solid #ffc0cb;">
                        <img 
                            src="./assets/uploads/<?= htmlspecialchars($product['ImagePath']) ?>" 
                            class="card-img-top" 
                            alt="<?= htmlspecialchars($product['Name']) ?>"
                        >
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= htmlspecialchars($product['Name']) ?></h5>
                            <p class="card-text"><?= htmlspecialchars($product['Description']) ?></p>
                            <p class="fw-bold text-pink mt-auto" style="color: #d63384;">Rs<?= number_format($product['Price'], 2) ?></p>
                            <a href="product.php" class="btn btn-sm" style="background-color: #ff69b4; color: white;">Go to Product</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php
try {
    $eventStmt = $conn->prepare("
        SELECT * FROM Event
        ORDER BY DateCreated DESC
        LIMIT 2
    ");
    $eventStmt->execute();
    $events = $eventStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($events as &$event) {
        $productStmt = $conn->prepare("
            SELECT p.Name, ep.Quantity
            FROM EventProducts ep
            INNER JOIN Products p ON ep.ProductId = p.Id
            WHERE ep.EventId = ?
        ");
        $productStmt->execute([$event['Id']]);
        $event['Products'] = $productStmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!-- Bundle Offers -->
<section class="py-5" style="background-color: #fff0f5;">
    <div class="container text-center">
        <h2 class="fw-bold text-pink" style="color: #d63384;">Gift Box Offers</h2>
        <p class="mb-4" style="font-size: 1.2rem;">Sweeten your celebrations with our beautiful cake bundles and gift boxes!</p>
        <div class="row row-cols-1 row-cols-md-2 g-4">
            <?php foreach ($events as $event): ?>
                <div class="col">
                    <div class="card shadow-sm h-100 border-pink" style="border: 1px solid #ffc0cb;">
                        <img src="./assets/uploads/<?= htmlspecialchars($event['ImagePath']) ?>" class="card-img-top" alt="<?= htmlspecialchars($event['Name']) ?>">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= htmlspecialchars($event['Name']) ?></h5>
                            <p class="card-text"><?= htmlspecialchars($event['Description']) ?></p>

                            <?php if (!empty($event['Products'])): ?>
                                <div class="text-start mb-3">
                                    <strong>Includes:</strong>
                                    <ul class="mb-0">
                                        <?php foreach ($event['Products'] as $prod): ?>
                                            <li><?= htmlspecialchars($prod['Name']) ?> (x<?= $prod['Quantity'] ?>)</li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <p class="fw-bold text-pink mt-auto" style="color: #d63384;">
                                <?= $event['DiscountPrice'] ? '<span class="text-decoration-line-through text-muted me-2">Rs' . number_format($event['Price'], 2) . '</span><span>Rs' . number_format($event['DiscountPrice'], 2) . '</span>' : 'Rs' . number_format($event['Price'], 2) ?>
                            </p>
                            <a href="event.php" class="btn btn-sm" style="background-color: #ff69b4; color: white;">Go to Event</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include "includes/footer.php" ?>
