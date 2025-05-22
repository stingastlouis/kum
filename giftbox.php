<?php
include "includes/header.php";
include './configs/db.php';

try {
    $stmt = $conn->prepare("SELECT * FROM GiftBox");
    $stmt->execute();
    $giftboxes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    include "includes/footer.php";
    exit;
}
?>
<link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Poppins&display=swap" rel="stylesheet">
<style>
    .giftbox-list-page {
        background-color: #fff0f6;
        font-family: 'Poppins', sans-serif;
    }

    .giftbox-list-page .giftbox-card {
        cursor: pointer;
        background-color: #fff;
        border-radius: 20px;
        border: 2px solid #ffc8dd;
        box-shadow: 0 5px 15px rgba(255, 192, 203, 0.3);
        overflow: hidden;
        transition: transform 0.2s ease;
        text-decoration: none;
        color: inherit;
    }

    .giftbox-list-page .giftbox-card:hover {
        transform: scale(1.02);
        box-shadow: 0 8px 20px rgba(255, 105, 180, 0.4);
    }

    .giftbox-list-page .giftbox-card img {
        height: 200px;
        object-fit: cover;
        border-top-left-radius: 20px;
        border-top-right-radius: 20px;
    }

    .giftbox-list-page .card-body {
        padding: 15px;
    }

    .giftbox-list-page .card-title {
        color: #d63384;
        font-weight: bold;
        font-size: 1.2rem;
        font-family: 'Pacifico', cursive;
        margin-bottom: 0.5rem;
    }

    .giftbox-list-page .card-text {
        font-size: 0.95rem;
        color: #333;
    }
</style>

<div style="background-color: #fff0f6;">

    <div class="giftbox-list-page container py-4">
        <h2 class="text-center mb-4 cake-title">Our Lovely Gift Boxes 💝</h2>
        <div class="row">
            <?php foreach ($giftboxes as $giftbox): ?>
                <div class="col-md-3 mb-4">
                    <a href="giftbox-selection.php?id=<?= $giftbox['Id'] ?>" class="giftbox-card d-block h-100">
                        <img src="./assets/uploads/<?= htmlspecialchars($giftbox['ImagePath']) ?>" class="w-100" alt="<?= htmlspecialchars($giftbox['Name']) ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($giftbox['Name']) ?></h5>
                            <p class="card-text">Max Cakes: <?= $giftbox['MaxCakes'] ?></p>
                            <p class="card-text">Price: USD <?= $giftbox['Price'] ?></p>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>
<?php include "includes/footer.php"; ?>