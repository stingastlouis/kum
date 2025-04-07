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

    <div class="container mt-5" id="checkout-page">
        <h1 class="mb-4 text-center">🍰 Checkout</h1>
        <?php if (!empty($cartItems)): ?>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Cake Name</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cartItems as $cartItem): ?>
                        <tr>
                            <td><?= htmlspecialchars($cartItem['name']) ?></td>
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
    
    <style>
        /* Checkout Page Styles */
        #checkout-page {
            font-family: 'Comic Sans MS', cursive, sans-serif;
            background-color: #fce0f1; /* Soft pastel pink background */
            color: #d63384; /* Pink text */
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
        }

        #checkout-page h1 {
            color: #ff69b4; /* Pink color */
            font-family: 'Pacifico', cursive; /* Fun, cute font */
            font-size: 2.5rem;
        }

        #checkout-page table {
            width: 100%;
            margin-top: 20px;
            border: 2px solid #ff69b4; /* Pink border */
            border-radius: 10px;
            background-color: #ffe6f0; /* Light pink */
        }

        #checkout-page th, #checkout-page td {
            padding: 12px 15px;
            text-align: center;
        }

        #checkout-page th {
            background-color: #ff85c1; /* Light pink header */
            color: white;
        }

        #checkout-page .table-bordered {
            border-radius: 8px;
            border: 2px solid #ff69b4; /* Pink border */
        }

        #checkout-page .table td {
            background-color: #fff0f6; /* Very soft pink for table rows */
            color: #d63384;
            font-weight: bold;
        }

        #checkout-page .alert-warning {
            background-color: #fff0f6; /* Light pink */
            color: #ff69b4;
            font-weight: bold;
        }

        #checkout-page button {
            background-color: #ff69b4; /* Button color */
            color: white;
            border: none;
            border-radius: 30px; /* Rounded edges for a cute look */
            font-size: 1.2rem;
            padding: 10px 20px;
            cursor: pointer;
            width: 100%;
        }

        #checkout-page button:hover {
            background-color: #ff85c1; /* Light pink hover effect */
        }

        #checkout-page .container {
            background: #fff0f6; /* Soft background for the whole container */
            border-radius: 15px;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
            padding: 40px;
        }

        #paypal-button-container {
            text-align: center;
            margin-top: 20px;
        }

        #paypal-button-container button {
            background-color: #ff69b4; /* Match PayPal button style */
            padding: 10px 20px;
            border-radius: 30px;
        }
    </style>

<script src="https://www.paypal.com/sdk/js?client-id=AYDMJVEgkRqU66bGWK-uzYtGKsJsLzVfx5OSKIn2j6y_tISbzHdvhEbyDXFU5dngERPjuoT1AUvRVygB&currency=USD"></script>

<script>
    paypal.Buttons({
        createOrder: function(data, actions) {
            return actions.order.create({
                purchase_units: [{
                    amount: {
                        value: '<?= number_format($grandTotal, 2, '.', '') ?>' // Grand total
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
