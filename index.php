<?php include "includes/header.php" ?>
<header class="bg-light py-5" style="background: linear-gradient(to right, #ffd6e0, #ffe6f0);">
    <div class="container">
        <div class="row align-items-center flex-column-reverse flex-md-row">
            <div class="col-md-6 text-center text-md-start">
                <h1 class="fw-bold text-pink" style="color: #d63384;">Delicious Cakes for Every Occasion</h1>
                <p class="lead text-muted mb-4">Treat yourself or someone special with our handcrafted cakes, baked with love and decorated to perfection.</p>
                <a href="cakes.php" class="btn btn-lg" style="background-color: #ff69b4; color: white; box-shadow: 0 4px 10px rgba(255, 105, 180, 0.4);">Explore Cakes</a>
            </div>
            <div class="col-md-6 text-center">
                <img src="assets/img/cake.png" class="img-fluid rounded" alt="Hero Cake Image" style="max-height: 400px;">
            </div>
        </div>
    </div>
</header>

<?php
include './configs/db.php';
try {
    $stmt = $conn->prepare("SELECT * FROM Cakes ORDER BY DateCreated DESC LIMIT 3");
    $stmt->execute();
    $cakes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

try {
    $giftBoxStmt = $conn->prepare("SELECT * FROM GiftBox ORDER BY DateCreated DESC LIMIT 3");
    $giftBoxStmt->execute();
    $giftboxes = $giftBoxStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<section class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold text-pink" style="color: #d63384;">Latest Cakes</h2>
            <p class="text-muted">Indulge in our latest cake creations. Whether it's for a birthday, wedding, or just a sweet craving, our cakes are baked with love and ready to make your day even more special.</p>
        </div>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($cakes as $cake): ?>
                <div class="col">
                    <div class="card shadow-sm h-100 border-pink" style="border: 1px solid #ffc0cb;">
                        <img
                            src="./assets/uploads/cakes/<?= htmlspecialchars($cake['ImagePath']) ?>"
                            class="card-img-top"
                            alt="<?= htmlspecialchars($cake['Name']) ?>">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= htmlspecialchars($cake['Name']) ?></h5>
                            <p class="card-text"><?= htmlspecialchars($cake['Description']) ?></p>
                            <p class="fw-bold text-pink mt-auto" style="color: #d63384;">$<?= number_format($cake['Price'], 2) ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="py-5" style="background-color: #fff0f5;">
    <div class="container text-center">
        <h2 class="fw-bold text-pink" style="color: #d63384;">Gift Boxes for Every Celebration</h2>
        <p class="mb-4" style="font-size: 1.2rem;">Looking for the perfect gift? Our curated gift boxes combine the best of our cakes and treats, making them the perfect choice for birthdays, anniversaries, or just to brighten someone's day.</p>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($giftboxes as $giftbox): ?>
                <div class="col">
                    <div class="card shadow-sm h-100 border-pink" style="border: 1px solid #ffc0cb;">
                        <img
                            src="./assets/uploads/giftboxes/<?= htmlspecialchars($giftbox['ImagePath']) ?>"
                            class="card-img-top"
                            alt="<?= htmlspecialchars($giftbox['Name']) ?>">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= htmlspecialchars($giftbox['Name']) ?></h5>
                            <p class="card-text">Cake Selection: <?= $giftbox['MaxCakes'] ?></p>
                            <p class="card-text">Price: $ <?= number_format($giftbox['Price'], 2) ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include "includes/footer.php" ?>