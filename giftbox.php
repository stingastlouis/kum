<?php
include "includes/header.php";
include './configs/db.php';

try {
    $stmt = $conn->prepare("SELECT * FROM GiftBox");
    $stmt->execute();
    $giftboxes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

?>
<link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Poppins&display=swap" rel="stylesheet">
<link rel="stylesheet" href="./assets/css/giftbox.css">

<div style="background-color: #fff0f6; height: 68vh;">

    <div class="giftbox-list-page container py-4">
        <h2 class="text-center mb-4 cake-title">Our Lovely Gift Boxes</h2>
        <div class="row">
            <?php foreach ($giftboxes as $giftbox): ?>
                <div class="col-md-3 mb-4">
                    <a href="giftbox-selection.php?id=<?= $giftbox['Id'] ?>" class="giftbox-card d-block h-100" data-id="<?= $giftbox['Id'] ?>">
                        <img src="./assets/uploads/giftboxes/<?= htmlspecialchars($giftbox['ImagePath']) ?>" class="w-100" alt="<?= htmlspecialchars($giftbox['Name']) ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($giftbox['Name']) ?></h5>
                            <p class="card-text">Max Cakes: <?= $giftbox['MaxCakes'] ?></p>
                            <p class="card-text">Price: $ <?= $giftbox['Price'] ?></p>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const cart = JSON.parse(localStorage.getItem('cake-cart') || '[]');
        const giftBoxIdsInCart = cart
            .filter(item => item.type === 'giftbox')
            .map(item => String(item.id));

        document.querySelectorAll('.giftbox-card').forEach(card => {
            const giftboxId = card.getAttribute('data-id');
            if (giftBoxIdsInCart.includes(giftboxId)) {
                card.closest('.col-md-3').style.display = 'none';
            }
        });
    });
</script>

<?php include "includes/footer.php"; ?>