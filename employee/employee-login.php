<?php

if (session_status() === PHP_SESSION_NONE) {
    $cookieShelfLife = 7 * 24 * 60 * 60;
    session_set_cookie_params($cookieShelfLife);
    session_start();
}

if (isset($_SESSION['employeeId'])) {
    header("Location: index.php");
    exit;
}

include '../configs/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = "Oopsie! That email isn’t sprinkled correctly. Try again!";
    } else {
        try {
            $stmt = $conn->prepare("
                SELECT E.Id, E.Fullname, E.Password, R.Name AS RoleName
                FROM Employee E
                JOIN Roles R ON E.RoleId = R.Id
                WHERE E.Email = ?");

            $stmt->execute([$email]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($employee && password_verify($password, $employee['Password'])) {
                $statusStmt = $conn->prepare("
                    SELECT S.StatusName 
                    FROM EmployeeStatus ES 
                    JOIN Status S ON ES.StatusId = S.Id 
                    WHERE ES.EmployeeId = ? 
                    ORDER BY ES.DateCreated DESC 
                    LIMIT 1
                ");
                $statusStmt->execute([$employee['Id']]);
                $status = $statusStmt->fetch(PDO::FETCH_ASSOC);

                if (!$status || strtolower($status['StatusName']) !== 'active') {
                    $errorMsg = "Your account status is '{$status['StatusName']}', please contact administrator.";
                } else {
                    $_SESSION['employeeId'] = $employee['Id'];
                    $_SESSION['employee_fullname'] = $employee['Fullname'];
                    $_SESSION['employee_role'] = $employee['RoleName'];
                    $_SESSION['employee_status'] = $status['StatusName'];

                    header("Location: index.php");
                    exit;
                }
            } else {
                $errorMsg = "Oops! Either your email or sprinkle is wrong.";
            }
        } catch (PDOException $e) {
            $errorMsg = "Uh-oh! Our cookie jar had a problem... " . $e->getMessage();
        }
    }
}
?>
<?php include "./includes/header.php"; ?>
<div class="d-flex justify-content-center align-items-center vh-100 bg-light-pink">
    <div class="card shadow p-4 rounded-4" style="max-width: 400px; width: 100%; background-color: #fff0f5;">
        <h2 class="text-center text-pink mb-4">Employee Login</h2>

        <?php if (isset($errorMsg)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="you@cookiejar.com" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Secret Sprinkle (Password)</label>
                <input type="password" name="password" class="form-control" placeholder="Enter your sprinkle" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Log In</button>
        </form>
    </div>
</div>

<?php include "./includes/footer.php"; ?>