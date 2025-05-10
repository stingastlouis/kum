<?php 
include './includes/header.php';
include './configs/db.php';

$cartItems = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$customerId = $_SESSION['customerId'] ?? 1;
$paymentMethodId = 1;

$totalAmount = 0;
$taxRate = 0.15;
foreach ($cartItems as $item) {
    $totalAmount += $item['price'] * $item['quantity'];
}
$taxAmount = $totalAmount * $taxRate;
$grandTotal = $totalAmount + $taxAmount;
?>
<link rel="stylesheet" href="./assets/css/checkout.css">

    <div class="container mt-5" id="checkout-page">
        <h1 class="mb-4 text-center">Checkout</h1>
        <?php if (!empty($cartItems)): ?>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Cake Name</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartItems as $cartItem): ?>
                        <tr>
                            <td><?= htmlspecialchars($cartItem['name']) ?></td>
                            <td><?= htmlspecialchars($cartItem['type']) ?></td>
                            <td><?= intval($cartItem['quantity']) ?></td>
                            <td>USD <?= number_format($cartItem['price'], 2) ?></td>
                            <td>USD <?= number_format($cartItem['price'] * $cartItem['quantity'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end">Tax (<?= $taxRate * 100 ?>%)</th>
                        <th>USD <?= number_format($taxAmount, 2) ?></th>
                    </tr>
                    <tr>
                        <th colspan="3" class="text-end">Total</th>
                        <th>USD <?= number_format($grandTotal, 2) ?></th>
                    </tr>
                </tfoot>
            </table>
            <form id="checkout-form">
                <input type="hidden" name="customerId" value="<?= $customerId ?>">
                <input type="hidden" name="cartItems" value='<?= json_encode($cartItems) ?>'>
                <div id="paypal-button-container"></div>
            </form>
        <?php else: ?>
            <p class="alert alert-warning text-center" style="background-color: #fff0f6; color: #ff69b4; font-weight: bold;">Your cart is empty! 🍰</p>
        <?php endif; ?>
    </div>


<script src="https://www.paypal.com/sdk/js?client-id=AYDMJVEgkRqU66bGWK-uzYtGKsJsLzVfx5OSKIn2j6y_tISbzHdvhEbyDXFU5dngERPjuoT1AUvRVygB&currency=USD"></script>

<script>
    paypal.Buttons({
        createOrder: function(data, actions) {
            return actions.order.create({
                purchase_units: [{
                    amount: {
                        value: '<?= number_format($grandTotal, 2, '.', '') ?>'
                    }
                }]
            });
        },
        onApprove: function(data, actions) {
            return actions.order.capture().then(function(details) {
                fetch('./processcake-order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        cartItems: <?= json_encode($cartItems) ?>,
                        transactionId: details.id,  
                        amount: details.purchase_units[0].amount.value
                    })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        window.location.href = 'cakeorder-success.php';
                    } else {
                        alert('Error: ' + result.message);
                    }
                });
            });
        }
    }).render('#paypal-button-container');
</script>

<?php include './includes/footer.php'?>
