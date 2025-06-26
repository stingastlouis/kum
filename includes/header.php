<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delicious Cake</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Montserrat:400,400i,700,700i,600,600i&amp;display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/baguettebox.js/1.11.1/baguetteBox.min.css">
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/cart.css">
    <link rel="icon" type="image/png" href="assets/img/favicon.png">


</head>

<body>
    <?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $isLoggedIn = isset($_SESSION['customerId']);
    ?>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm py-3">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/kum">Delicious Cake</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? ' active' : ''; ?>" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'cakes.php' ? ' active' : ''; ?>" href="cakes.php">Cakes</a></li>
                    <li class="nav-item"><a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'giftbox.php' ? ' active' : ''; ?>" href="giftbox.php">GiftBox</a></li>
                    <li class="nav-item"><a class="nav-link<?php echo basename($_SERVER['PHP_SELF']) == 'contact.php' ? ' active' : ''; ?>" href="contact.php">Contact</a></li>
                </ul>
                <div class="d-flex align-items-center gap-3">
                        <a href="#" id="cart-icon" class="btn btn-light-pink text-dark position-relative rounded-pill shadow-sm">
                            My Cart
                            <span id="cart-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-pink text-white">
                                0
                            </span>
                        </a>
                    <?php if ($isLoggedIn): ?>
                       

                        <a href="profile.php" class="text-primary text-decoration-none fw-semibold <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? ' active' : ''; ?>" style="cursor: pointer;">
                            Profile
                        </a>
                        <a href="logout.php" class="text-danger text-decoration-none fw-semibold" style="cursor: pointer;">
                            Logout
                        </a>

                    <?php else: ?>
                        <a href="signin.php" class="btn btn-outline-primary">Sign In</a>
                        <a href="signup.php" class="btn btn-primary">Sign Up</a>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </nav>
    <?php include './cart-ui.php';
