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
                        <h3 class="mb-0 text-pink">🎂 Order Success 🎉</h3>
                    </div>
                    <div class="card-body">
                        <h4 class="text-success">Your order has been successfully placed! 🍰</h4>
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

<style>
    /* Girly Cake-Themed Styles */
    #order-success-page {
        background-color: #fce0f1; /* Soft pastel pink background */
        color: #d63384; /* Pink text */
        padding: 50px 20px;
        border-radius: 20px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    #order-success-page .card {
        background-color: #ffe6f0; /* Light pink */
        border: none;
        border-radius: 10px;
        padding: 20px;
    }

    #order-success-page .card-header {
        background-color: #ff85c1; /* Soft light pink */
        color: white;
        font-family: 'Pacifico', cursive;
    }

    #order-success-page .card-body {
        font-family: 'Comic Sans MS', cursive, sans-serif;
        color: #d63384;
    }

    #order-success-page h4 {
        color: #ff69b4; /* Bright pink for the success message */
    }

    #order-success-page h5 {
        color: #d63384; /* Pink for subheading */
    }

    #order-success-page .btn {
        padding: 10px 20px;
        font-size: 1.1rem;
        border-radius: 30px;
        cursor: pointer;
    }

    #order-success-page .btn-pink {
        background-color: #ff69b4; /* Bright pink */
        color: white;
        border: none;
    }

    #order-success-page .btn-pink:hover {
        background-color: #ff85c1; /* Lighter pink hover effect */
    }

    #order-success-page .btn-lightpink {
        background-color: #ffb3d9; /* Light pink */
        color: white;
        border: none;
    }

    #order-success-page .btn-lightpink:hover {
        background-color: #ff85c1; /* Lighter pink hover effect */
    }

    #order-success-page .list-group-item {
        background-color: #fff0f6; /* Light pink for list items */
        color: #d63384; /* Pink text for the list */
    }

    #order-success-page .list-group-item strong {
        font-weight: bold;
    }
</style>
