<?php include "./includes/header.php" ?>
<link rel="stylesheet" href="./assets/css/signup.css">
<?php
include './configs/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sweetieFirst = trim($_POST['firstname']);
    $sweetieLast = trim($_POST['lastname']);
    $sweetieName = $sweetieFirst . ' ' . $sweetieLast;
    $sweetieEmail = trim($_POST['email']);
    $sweetieAddress = trim($_POST['address']);
    $sweetiePhone = trim($_POST['phone']);
    $secretSprinkle = trim($_POST['password']);

    if (!filter_var($sweetieEmail, FILTER_VALIDATE_EMAIL)) {
        echo "<div class='alert alert-danger'>Oops! That email doesn't look yummy. Try again!</div>";
    } elseif (
        strlen($secretSprinkle) < 8 ||
        !preg_match('/[A-Z]/', $secretSprinkle) ||
        !preg_match('/[a-z]/', $secretSprinkle) ||
        !preg_match('/[0-9]/', $secretSprinkle) ||
        !preg_match('/[!@#$%^&*]/', $secretSprinkle)
    ) {
        echo "<div class='alert alert-danger'>Your secret sprinkle (password) needs to be stronger! Add some uppercase, lowercase, numbers & special sugar </div>";
    } else {
        $hashedSprinkle = password_hash($secretSprinkle, PASSWORD_DEFAULT);

        try {
            $stmt = $conn->prepare("SELECT Id FROM Customer WHERE Email = ?");
            $stmt->execute([$sweetieEmail]);

            if ($stmt->rowCount() > 0) {
                echo "<div class='alert alert-danger'>Oh no! That email is already in our cookie jar.</div>";
            } else {
                $stmt = $conn->prepare("INSERT INTO Customer (fullname, email, address, phone, password) VALUES (?, ?, ?, ?, ?)");
                $success = $stmt->execute([$sweetieName, $sweetieEmail, $sweetieAddress, $sweetiePhone, $hashedSprinkle]);

                if ($success) {

                    echo "<div class='alert alert-success'>Yay! You're officially part of our cookie club</div>";
                    echo "<meta http-equiv='refresh' content='3;url=signin.php'>";
                    exit;
                } else {
                    echo "<div class='alert alert-danger'>Oh crumbs! Something went wrong while baking your account.</div>";
                }
            }
        } catch (PDOException $e) {
            echo "<div class='alert alert-danger'>Uh-oh, kitchen mess! DB error: " . $e->getMessage() . "</div>";
        }
    }
}
?>



<style>

</style>

<div class="register-container">
    <h2> Join Our Cake Club!</h2>
    <form method="POST" action="">
        <div class="mb-3">
            <label class="form-label">First Name</label>
            <input type="text" name="firstname" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Last Name</label>
            <input type="text" name="lastname" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Address</label>
            <input type="text" name="address" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
            <small class="text-muted">At least 8 characters, with upper/lowercase, number & symbol.</small>
        </div>
        <div class="text-center">
            <button type="submit" class="btn btn-pink">Register Now </button>
        </div>
    </form>
</div>

<?php include "./includes/footer.php" ?>