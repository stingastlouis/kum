<?php
include "includes/header.php";
include './configs/db.php';

$giftboxId = $_GET['id'] ?? null;

if (!$giftboxId || !is_numeric($giftboxId)) {
    echo "<div class='alert alert-danger'>Invalid or missing GiftBox ID.</div>";
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM GiftBox WHERE Id = ?");
    $stmt->execute([$giftboxId]);
    $giftbox = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$giftbox) {
        echo "<div class='alert alert-warning'>GiftBox not found.</div>";
        exit;
    }

    $maxCakes = (int)$giftbox['MaxCakes'];
    $params = [];
    $categoryFilter = '';

    if (!empty($giftbox['CategoryId'])) {
        $categoryFilter = 'AND c.CategoryId = ?';
        $params[] = $giftbox['CategoryId'];
    }

    $cakeStmt = $conn->prepare("
    SELECT c.*
    FROM Cakes c
    LEFT JOIN CakeStatus cs ON c.Id = cs.CakeId
    LEFT JOIN Status s ON cs.StatusId = s.Id
    WHERE cs.DateCreated = (
        SELECT MAX(cs_inner.DateCreated)
        FROM CakeStatus cs_inner
        WHERE cs_inner.CakeId = c.Id
    )
    AND s.StatusName != 'INACTIVE'
    AND c.StockCount > 0
        $categoryFilter
    ");

    $cakeStmt->execute($params);
    $cakes = $cakeStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
    exit;
}
?>

<link rel="stylesheet" href="assets/css/giftboxselection.css">
<div class="giftbox-selection-page" style="background-color: #fff0f6; height: 100vh;">
    <div class="container py-4">
        <?php if (!empty($cakes)): ?>
            <h2 class="cake-title text-center mb-3">
                Select up to <?= htmlspecialchars($maxCakes) ?> cakes for: <?= htmlspecialchars($giftbox['Name']) ?> Giftbox
            </h2>
            <h4 class="cake-title text-center mb-4">
                Price: $<?= htmlspecialchars(number_format($giftbox['Price'], 2)) ?>
            </h4>

            <form id="giftboxForm" novalidate>
                <input type="hidden" name="giftboxId" value="<?= htmlspecialchars($giftbox['Id']) ?>">
                <div class="row">
                    <?php foreach ($cakes as $cake): ?>
                        <div class="col-sm-6 col-lg-3 mb-4">
                            <div class="cake-card shadow-sm h-100 d-flex flex-column justify-content-between">
                                <img src="./assets/uploads/cakes/<?= htmlspecialchars($cake['ImagePath']) ?>" class="card-img-top" alt="<?= htmlspecialchars($cake['Name']) ?>">
                                <div class="card-body p-2">
                                    <h5 class="card-title"><?= htmlspecialchars($cake['Name']) ?></h5>
                                    <p><?= htmlspecialchars($cake['Description']) ?></p>
                                    <div class="form-group">
                                        <label for="cake-<?= $cake['Id'] ?>">Quantity:</label>
                                        <input type="number"
                                            class="form-control cake-quantity"
                                            id="cake-<?= $cake['Id'] ?>"
                                            name="cake_<?= $cake['Id'] ?>"
                                            data-cake-id="<?= $cake['Id'] ?>"
                                            min="0"
                                            value="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="text-center mt-4">
                    <p id="cake-count-info">Selected: 0 / <?= $maxCakes ?></p>
                    <button type="submit" class="btn btn-primary" id="submitGiftBox" disabled>Add GiftBox to Cart</button>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-warning text-center shadow-sm p-4" style="background-color: #fff8f8; color: #d63384; font-weight: 500;">
                <h4>No cakes available at the moment!</h4>
                <p>Please check back later or choose a different gift box.</p>
            </div>
        <?php endif; ?>
    </div>
</div>


<script>
    const maxCakes = <?= $maxCakes ?>;
    const quantityInputs = document.querySelectorAll('.cake-quantity');
    const infoText = document.getElementById('cake-count-info');
    const submitButton = document.getElementById('submitGiftBox');

    function updateTotalCount() {
        let total = Array.from(quantityInputs).reduce((sum, input) => sum + (parseInt(input.value) || 0), 0);
        infoText.textContent = `Selected: ${total} / ${maxCakes}`;
        submitButton.disabled = total !== maxCakes;
    }

    quantityInputs.forEach(input => {
        input.addEventListener('input', () => {
            let total = Array.from(quantityInputs).reduce((sum, inp) => sum + (parseInt(inp.value) || 0), 0);

            if (total > maxCakes) {
                alert(`You can only select up to ${maxCakes} cakes.`);
                input.value = 0;
            }

            updateTotalCount();
        });
    });

    document.getElementById('giftboxForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const selectedCakes = Array.from(quantityInputs).map(input => {
            const quantity = parseInt(input.value || 0);
            return quantity > 0 ? {
                cakeId: input.dataset.cakeId,
                quantity
            } : null;
        }).filter(Boolean);

        const totalSelected = selectedCakes.reduce((acc, item) => acc + item.quantity, 0);

        if (totalSelected !== maxCakes) {
            alert(`You need to select exactly ${maxCakes} cakes.`);
            return;
        }

        const cart = JSON.parse(localStorage.getItem("cake-cart")) || [];
        cart.push({
            type: "giftbox",
            id: <?= json_encode($giftbox['Id']) ?>,
            name: <?= json_encode($giftbox['Name']) ?>,
            quantity: 1,
            price: <?= json_encode($giftbox['Price']) ?>,
            cakes: selectedCakes
        });

        localStorage.setItem("cake-cart", JSON.stringify(cart));
        updateCartUI(cart);

        quantityInputs.forEach(input => input.value = 0);
        updateTotalCount();
        window.location.href = "giftbox.php"
    });

    updateTotalCount();
</script>

<?php include "includes/footer.php"; ?>