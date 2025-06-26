<?php
include './includes/header.php';
include './configs/db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['customerId'])) {
    header("Location: signin.php");
    exit();
}

$cartItems = $_SESSION['cart'] ?? [];
$customerId = $_SESSION['customerId'];


$totalAmount = 0;
$deliveryFee = 20;

foreach ($cartItems as $item) {
    $totalAmount += $item['price'] * $item['quantity'];
}
$grandTotal = $totalAmount;
$paymentMethods = [];

$stmt = $conn->query("SELECT Id, Name FROM PaymentMethod");
$paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<link rel="stylesheet" href="./assets/css/checkout.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

<div class="container mt-5" id="checkout-page">
    <h1 class="mb-4 text-center">Checkout</h1>
    <?php if (!empty($cartItems)): ?>
        <table class="table table-bordered table-striped" id="summary-table">
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
                        <td>$ <?= number_format($cartItem['price'], 2) ?></td>
                        <td>$ <?= number_format($cartItem['price'] * $cartItem['quantity'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr id="delivery-row" style="display: none;">
                    <th colspan="3" class="text-end">Delivery Fee</th>
                    <th colspan="2" id="delivery-amount">$ <?= number_format($deliveryFee, 2) ?></th>
                </tr>
                <tr>
                    <th colspan="3" class="text-end">Total</th>
                    <th colspan="2" id="total-amount">$ <?= number_format($grandTotal, 2) ?></th>
                </tr>
            </tfoot>
        </table>

        <div class="mb-3">
            <label for="scheduleDate" class="form-label">Choose Schedule Date</label>
            <input type="date" class="form-control" id="scheduleDate" name="scheduleDate" required>
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" value="" id="deliveryCheckbox">
            <label class="form-check-label" for="deliveryCheckbox">
                Add delivery ($ <?= number_format($deliveryFee, 2) ?>)
            </label>
        </div>

        <div id="location-label" class="mb-2 text-primary fw-bold text-center"></div>
        <div id="map" style="height: 300px; display: none;" class="mb-3"></div>

        <div class="mb-3">
            <label for="payment-method" class="form-label">Select Payment Method</label>
            <select class="form-select" id="payment-method" name="paymentMethodId" required>
                <option value="">-- Choose Payment Method --</option>
                <?php foreach ($paymentMethods as $method): ?>
                    <option value="<?= $method['Id'] ?>"><?= htmlspecialchars($method['Name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <form id="checkout-form">
            <input type="hidden" name="customerId" value="<?= $customerId ?>">
            <input type="hidden" name="cartItems" value='<?= json_encode($cartItems) ?>'>
            <input type="hidden" id="finalAmount" name="finalAmount" value="<?= number_format($grandTotal, 2, '.', '') ?>">
            <input type="hidden" id="latitude" name="latitude">
            <input type="hidden" id="longitude" name="longitude">
            <input type="hidden" id="selectedPaymentMethod" name="paymentMethodId">

            <div id="paypal-button-container" style="display:none;"></div>
            <button type="submit" class="btn btn-success w-100" id="proceed-button" style="display:none;">Proceed</button>
        </form>
    <?php else: ?>
        <p class="alert alert-warning text-center" style="background-color: #fff0f6; color: #ff69b4; font-weight: bold;">Your cart is empty! </p>
    <?php endif; ?>
</div>

<div class="modal fade" id="orderResponseModal" tabindex="-1" aria-labelledby="orderResponseLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" id="modalHeader">
                <h5 class="modal-title" id="orderResponseLabel">Order Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="orderResponseMessage">
            </div>
            <div class="modal-footer" id="modalFooter">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a id="successRedirectBtn" href="profile.php#order-history" class="btn btn-success d-none">Go to Order History</a>

            </div>
        </div>
    </div>
</div>


<script src="https://www.paypal.com/sdk/js?client-id=AYDMJVEgkRqU66bGWK-uzYtGKsJsLzVfx5OSKIn2j6y_tISbzHdvhEbyDXFU5dngERPjuoT1AUvRVygB&currency=USD"></script>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
    const scheduleDateInput = document.getElementById('scheduleDate');
    const today = new Date();
    today.setDate(today.getDate() + 2);
    const minDate = today.toISOString().split('T')[0];
    scheduleDateInput.min = minDate;



    const deliveryCheckbox = document.getElementById('deliveryCheckbox');
    const deliveryRow = document.getElementById('delivery-row');
    const deliveryAmount = <?= $deliveryFee ?>;
    const baseTotal = <?= number_format($grandTotal, 2, '.', '') ?>;
    const totalAmountField = document.getElementById('total-amount');
    const finalAmountInput = document.getElementById('finalAmount');
    const mapContainer = document.getElementById('map');
    const locationLabel = document.getElementById('location-label');
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    const paymentMethodSelect = document.getElementById('payment-method');
    const paypalContainer = document.getElementById('paypal-button-container');
    const proceedButton = document.getElementById('proceed-button');
    const selectedPaymentMethodInput = document.getElementById('selectedPaymentMethod');

    let mapInitialized = false;
    let map;
    let marker;

    deliveryCheckbox.addEventListener('change', () => {
        let newTotal = baseTotal;

        if (deliveryCheckbox.checked) {
            deliveryRow.style.display = 'table-row';
            newTotal += deliveryAmount;
            mapContainer.style.display = 'block';

            if (!mapInitialized) {
                map = L.map('map').setView([-20.1654, 57.5012], 10);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                map.on('click', function(e) {
                    if (marker) map.removeLayer(marker);
                    marker = L.marker(e.latlng).addTo(map);
                    latInput.value = e.latlng.lat;
                    lngInput.value = e.latlng.lng;

                    fetch(`https://nominatim.openstreetmap.org/reverse?lat=${e.latlng.lat}&lon=${e.latlng.lng}&format=json`)
                        .then(response => response.json())
                        .then(data => {
                            const displayName = data.display_name || `Lat: ${e.latlng.lat}, Lng: ${e.latlng.lng}`;
                            locationLabel.textContent = `Delivery Location: ${displayName}`;
                            locationLabel.style.display = 'block';
                        })
                        .catch(() => {
                            locationLabel.textContent = `Location selected at [${e.latlng.lat.toFixed(4)}, ${e.latlng.lng.toFixed(4)}]`;
                            locationLabel.style.display = 'block';
                        });
                });

                mapInitialized = true;
            }
        } else {
            deliveryRow.style.display = 'none';
            newTotal -= deliveryAmount;
            mapContainer.style.display = 'none';
            locationLabel.style.display = 'none';
            latInput.value = '';
            lngInput.value = '';
        }

        totalAmountField.textContent = '$ ' + newTotal.toFixed(2);
        finalAmountInput.value = newTotal.toFixed(2);
    });

    paymentMethodSelect.addEventListener('change', function() {
        const methodText = this.options[this.selectedIndex].text.trim().toLowerCase();
        selectedPaymentMethodInput.value = this.value;

        const dateVal = scheduleDateInput.value;
        if (!dateVal) {
            alert('Please select a schedule date before choosing payment.');
            this.value = '';
            return;
        }


        if (methodText === 'paypal') {
            paypalContainer.style.display = 'block';
            proceedButton.style.display = 'none';
        } else if (methodText === 'cash') {
            paypalContainer.style.display = 'none';
            proceedButton.style.display = 'block';
        } else {
            paypalContainer.style.display = 'none';
            proceedButton.style.display = 'none';
        }
    });

    paypal.Buttons({
        createOrder: function(data, actions) {
            return actions.order.create({
                purchase_units: [{
                    amount: {
                        value: finalAmountInput.value
                    }
                }]
            });
        },
        onApprove: function(data, actions) {
            return actions.order.capture().then(function(details) {
                const latLng = latInput.value && lngInput.value ? `${latInput.value},${lngInput.value}` : null;
                fetch('./processcake-order.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            customerId: <?= $customerId ?>,
                            cartItems: <?= json_encode($cartItems) ?>,
                            transactionId: details.id,
                            amount: details.purchase_units[0].amount.value,
                            deliveryIncluded: deliveryCheckbox.checked,
                            location: latLng,
                            paymentMethodId: selectedPaymentMethodInput.value,
                            scheduleDate: scheduleDateInput.value
                        })
                    }).then(res => res.json())
                    .then(response => {
                        localStorage.removeItem("cake-cart");
                        const modal = new bootstrap.Modal(document.getElementById('orderResponseModal'));
                        const messageEl = document.getElementById('orderResponseMessage');
                        const headerEl = document.getElementById('modalHeader');
                        const successBtn = document.getElementById('successRedirectBtn');

                        if (response.success) {
                            messageEl.textContent = "Order placed successfully!";
                            headerEl.classList.remove("bg-danger");
                            headerEl.classList.add("bg-success", "text-white");
                            successBtn.classList.remove("d-none");
                        } else {
                            messageEl.textContent = "Something went wrong. Please try again.";
                            headerEl.classList.remove("bg-success");
                            headerEl.classList.add("bg-danger", "text-white");
                            successBtn.classList.add("d-none");
                        }

                        modal.show();
                    });
            });
        }
    }).render('#paypal-button-container');

    document.getElementById('checkout-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const latLng = latInput.value && lngInput.value ? `${latInput.value},${lngInput.value}` : null;
        fetch('./processcake-order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    customerId: <?= $customerId ?>,
                    cartItems: <?= json_encode($cartItems) ?>,
                    amount: finalAmountInput.value,
                    deliveryIncluded: deliveryCheckbox.checked,
                    location: latLng,
                    paymentMethodId: selectedPaymentMethodInput.value,
                    scheduleDate: scheduleDateInput.value
                })
            }).then(res => res.json())
            .then(response => {
                localStorage.removeItem("cake-cart");
                const modal = new bootstrap.Modal(document.getElementById('orderResponseModal'));
                const messageEl = document.getElementById('orderResponseMessage');
                const headerEl = document.getElementById('modalHeader');
                const successBtn = document.getElementById('successRedirectBtn');

                if (response.success) {
                    localStorage.removeItem("cake-cart");
                    messageEl.textContent = "Order placed successfully!";
                    headerEl.classList.remove("bg-danger");
                    headerEl.classList.add("bg-success", "text-white");
                    successBtn.classList.remove("d-none");
                } else {
                    messageEl.textContent = response.message || "Something went wrong. Please try again.";
                    headerEl.classList.remove("bg-success");
                    headerEl.classList.add("bg-danger", "text-white");
                    successBtn.classList.add("d-none");
                }

                modal.show();
            });
    });


    document.addEventListener('DOMContentLoaded', function() {
        const myModal = document.getElementById('orderResponseModal');

        if (myModal) {
            myModal.addEventListener('shown.bs.modal', function() {
                setTimeout(function() {
                    window.location.href = 'profile.php#order-history';
                }, 3000);
            });
        }
    });
</script>
<br/>
<?php include './includes/footer.php' ?>