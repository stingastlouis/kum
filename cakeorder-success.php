<?php
include './includes/header.php';
if (!isset($_SESSION['orderSuccess']) || $_SESSION['orderSuccess'] !== true) {
    header("Location: checkout.php");
    exit();
}

unset($_SESSION['orderSuccess']);
?>
    <div class="container mt-5" id="order-success-page">
        <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6">
                <div class="card shadow-sm" id="order-card">
                    <div class="card-header text-center">
                        <h3 class="mb-0 text-pink"> Order Success </h3>
                    </div>
                    <div class="card-body">
                        <h4 class="text-success">Your order has been successfully placed! </h4>
                        <p class="lead">Thank you for your purchase. Your order is being processed, and you will receive an email confirmation shortly.</p>

                        <div class="mb-4">
                            <h5 class="text-pink">Order Summary:</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <strong>Order ID:</strong> <?= $_SESSION['orderId']; ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Payment Method:</strong> PayPal
                                </li>
                                <li class="list-group-item">
                                    <strong>Total Amount:</strong> Rs <?= number_format($_SESSION['totalAmount'], 2); ?>
                                </li>
                            </ul>
                        </div>

                        <p class="text-center">
                            <a href="index.php" class="btn btn-pink">Go to Home</a>
                            <a href="order-history.php" class="btn btn-lightpink">View Order History</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        localStorage.removeItem("cake-cart");
    </script>
<?php include './includes/footer.php'?>
