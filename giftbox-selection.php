<?php  
include "includes/header.php";
include './configs/db.php';

$giftboxId = $_GET['id'] ?? null;
if (!$giftboxId) {
    echo "<p class='text-danger'>GiftBox ID not provided.</p>";
    include "includes/footer.php";
    exit;
}

try {
    $stmt = $conn->prepare("SELECT * FROM GiftBox WHERE Id = ?");
    $stmt->execute([$giftboxId]);
    $giftbox = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$giftbox) {
        echo "<p class='text-danger'>GiftBox not found.</p>";
        include "includes/footer.php";
        exit;
    }

    $maxCakes = $giftbox['MaxCakes'];
    $categoryFilter = '';
    $params = [];

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
        $categoryFilter
    ");
    $cakeStmt->execute($params);
    $cakes = $cakeStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    include "includes/footer.php";
    exit;
}
?>

<style>
.giftbox-selection-page {
    background-color: #fff0f6;
    font-family: 'Poppins', sans-serif;
}
.giftbox-selection-page .cake-title {
    color: #d63384;
    font-weight: bold;
    font-family: 'Pacifico', cursive;
}
.giftbox-selection-page .cake-card {
    background-color: #fff;
    border-radius: 20px;
    border: 2px solid #ffc8dd;
    box-shadow: 0 5px 15px rgba(255, 192, 203, 0.3);
    transition: transform 0.3s ease;
    overflow: hidden;
}
.giftbox-selection-page .cake-card:hover {
    transform: scale(1.03);
}
.giftbox-selection-page .card-title {
    color: #ff69b4;
    font-size: 1.2rem;
    font-weight: bold;
    font-family: 'Poppins', cursive;
}
.giftbox-selection-page .card-body p {
    font-size: 0.9rem;
    color: #555;
}
.giftbox-selection-page .card-img-top {
    height: 180px;
    object-fit: cover;
    border-top-left-radius: 20px;
    border-top-right-radius: 20px;
}
.giftbox-selection-page input.cake-quantity {
    border-radius: 10px;
    border: 1px solid #ffc8dd;
    padding: 5px;
}
.giftbox-selection-page .btn-primary {
    background-color: #ff69b4;
    border-color: #ff69b4;
    padding: 10px 20px;
    border-radius: 25px;
    font-weight: bold;
    font-family: 'Poppins', sans-serif;
}
.giftbox-selection-page .btn-primary:hover {
    background-color: #ff85c1;
    border-color: #ff85c1;
}
.giftbox-selection-page #cake-count-info {
    font-size: 1.1rem;
    font-weight: bold;
    color: #d63384;
}
</style>

<div style="background-color: #fff0f6">
    <div class="giftbox-selection-page container py-4">
        <h2 class="cake-title text-center mb-4">Select up to <?= $maxCakes ?> cakes for: <?= htmlspecialchars($giftbox['Name']) ?> Giftbox</h2>
        <h3 class="cake-title text-center mb-4">Price: <?= htmlspecialchars($giftbox['Price']) ?></h3>

        <form id="giftboxForm">
            <input type="hidden" name="giftboxId" value="<?= $giftbox['Id'] ?>">
            <div class="row">
                <?php foreach ($cakes as $cake): ?>
                    <div class="col-md-3 mb-4">
                        <div class="cake-card shadow-sm">
                            <img src="./assets/uploads/<?= htmlspecialchars($cake['ImagePath']) ?>" class="card-img-top mb-2" alt="<?= htmlspecialchars($cake['Name']) ?>">
                            <div class="card-body p-2">
                                <h5 class="card-title"><?= htmlspecialchars($cake['Name']) ?></h5>
                                <p><?= htmlspecialchars($cake['Description']) ?></p>
                                <div class="form-group">
                                    <label>Quantity:</label>
                                    <input type="number" class="form-control cake-quantity" 
                                        name="cake_<?= $cake['Id'] ?>" 
                                        data-cake-id="<?= $cake['Id'] ?>" 
                                        min="0" max="<?= $cake['StockCount'] ?>" 
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
    </div>
</div>

<script>
const maxCakes = <?= $maxCakes ?>;
const quantityInputs = document.querySelectorAll('.cake-quantity');
const infoText = document.getElementById('cake-count-info');
const submitButton = document.getElementById('submitGiftBox');

function updateTotalCount() {
    let total = 0;
    quantityInputs.forEach(input => {
        total += parseInt(input.value || 0);
    });
    infoText.textContent = `Selected: ${total} / ${maxCakes}`;
    submitButton.disabled = total !== maxCakes;
    quantityInputs.forEach(input => {
        const currentVal = parseInt(input.value || 0);
        const maxAttr = parseInt(input.getAttribute('max'));
        input.max = Math.min(maxCakes - (total - currentVal), maxAttr);
    });
}

quantityInputs.forEach(input => {
    input.addEventListener('input', () => {
        let total = 0;
        quantityInputs.forEach(i => {
            total += parseInt(i.value || 0);
        });
        if (total > maxCakes) {
            alert(`You can only select up to ${maxCakes} cakes in total.`);
            input.value = 0;
        }
        updateTotalCount();
    });
});

updateTotalCount();

document.getElementById('giftboxForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const selectedCakes = [];
    quantityInputs.forEach(input => {
        const quantity = parseInt(input.value || 0);
        if (quantity > 0) {
            selectedCakes.push({
                cakeId: input.dataset.cakeId,
                quantity: quantity
            });
        }
    });
    const totalSelected = selectedCakes.reduce((acc, item) => acc + item.quantity, 0);
    if (totalSelected < maxCakes) {
        alert(`You need to select exactly ${maxCakes} cakes.`);
        return;
    }
    const cart = JSON.parse(localStorage.getItem("user-cart")) || [];
    cart.push({
        type: "giftbox",
        giftboxId: <?= $giftbox['Id'] ?>,
        name: <?= json_encode($giftbox['Name']) ?>,
        price: <?= $giftbox['Price'] ?>,
        cakes: selectedCakes
    });
    localStorage.setItem("user-cart", JSON.stringify(cart));
    alert("GiftBox added to cart!");
    window.location.href = "cart.php";
});
</script>

<?php include "includes/footer.php"; ?>
