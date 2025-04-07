<?php 
$cookieShelfLife = 7 * 24 * 60 * 60; 
session_set_cookie_params($cookieShelfLife);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include './configs/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sweetieEmail = trim($_POST['email']);
    $secretSprinkle = trim($_POST['password']);

    if (!filter_var($sweetieEmail, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = "Oopsie! That email isn’t sprinkled correctly 🍬 Try again!";
    } else {
        try {
            $stmt = $conn->prepare("SELECT Id, fullname, password FROM Customer WHERE email = ?");
            $stmt->execute([$sweetieEmail]);
            $cookieLover = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($cookieLover && password_verify($secretSprinkle, $cookieLover['password'])) {
                $_SESSION['customerId'] = $cookieLover['Id'];
                $_SESSION['customer_fullname'] = $cookieLover['fullname'];

                session_regenerate_id(true);

                header("Location: cakes.php");
                exit;
            } else {
                $errorMsg = "Oops! Either your email or sprinkle is wrong 😢";
            }
        } catch (PDOException $e) {
            $errorMsg = "Uh-oh! Our cookie jar had a problem... 🍯 " . $e->getMessage();
        }
    }
}
?>
<?php include "./includes/header.php" ?>
<div class="d-flex justify-content-center align-items-center vh-100 bg-light-pink">
    <div class="card shadow p-4 rounded-4" style="max-width: 400px; width: 100%; background-color: #fff0f5;">
        <h2 class="text-center text-pink mb-4">💗 Cookie Jar Login 💗</h2>

        <?php if (isset($errorMsg)): ?>
            <div class="alert alert-danger"><?= $errorMsg ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="you@yummycookies.com" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Secret Sprinkle (Password)</label>
                <input type="password" name="password" class="form-control" placeholder="Enter your sprinkle" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">🍪 Log In</button>
        </form>
    </div>
</div>
<?php include "./includes/footer.php" ?>

<style>
.text-pink {
    color: #d63384;
}

.bg-light-pink {
    background-color: #fff0f5;
}

.btn-primary {
    background-color: #ff69b4;
    border-color: #ff69b4;
    font-weight: bold;
}

.btn-primary:hover {
    background-color: #ff85c1;
    border-color: #ff85c1;
}

.alert-danger {
    background-color: #ffe0ec;
    color: #d63384;
    border-color: #f8bbd0;
}

</style>