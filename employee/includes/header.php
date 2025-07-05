<?php
require_once 'auth.php';
include '../configs/db.php';

$employeeName = $_SESSION['employee_fullname'] ?? null;
$employeeRole = $_SESSION['employee_role'] ?? null;
$unreadCount = 0;

if ($employeeName) {
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM `Messages` WHERE `Read` = 0");
        $stmt->execute();
        $unreadCount = $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("DB Error: " . $e->getMessage());
    }
}

require_once 'popupMessage.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    <title>Dashboard - Delicious Cake</title>
    <link rel="stylesheet" href="../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/Nunito.css">
    <link rel="stylesheet" href="../assets/fonts/fontawesome-all.min.css">
    <link rel="stylesheet" href="../assets/css/dropdown.css">
    <link rel="stylesheet" href="../assets/css/board-image.css">
    <link rel="icon" type="image/png" href="../assets/img/favicon.png">
</head>

<body id="page-top" style="background-color:rgb(233, 211, 222);">
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color:rgb(236, 12, 120);">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="../assets/img/cake.png" width="40" height="40">
                <span style="margin-left: 20px">Delicious Cake</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNavbar" aria-controls="topNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="topNavbar">
                <?php if (isEmployeeLoggedIn()): ?>
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <?php if (isEmployeeInRoles([ROLE_ADMIN])): ?>
                            <li class="nav-item"><a class="nav-link fw-bold text-dark" href="index.php">Dashboard</a></li>
                            <li class="nav-item"><a class="nav-link fw-bold text-dark" href="category.php">Categories</a></li>
                            <li class="nav-item"><a class="nav-link fw-bold text-dark" href="role.php">Roles</a></li>
                            <li class="nav-item"><a class="nav-link fw-bold text-dark" href="employee.php">Employees</a></li>
                            <li class="nav-item"><a class="nav-link fw-bold text-dark" href="customer.php">Customers</a></li>
                            <li class="nav-item"><a class="nav-link fw-bold text-dark" href="order.php">Orders</a></li>
                        <?php endif; ?>
                        <?php if (isEmployeeInRoles([ROLE_ADMIN, ROLE_COOK])): ?>
                            <li class="nav-item"><a class="nav-link fw-bold text-dark" href="giftbox.php">Giftboxes</a></li>
                            <li class="nav-item"><a class="nav-link fw-bold text-dark" href="cake.php">Cakes</a></li>
                        <?php endif; ?>
                        <?php if (isEmployeeInRoles([ROLE_DELIVERY])): ?>
                            <li class="nav-item"><a class="nav-link fw-bold text-dark" href="delivery.php">Deliveries</a></li>
                        <?php endif; ?>
                        <?php if (isEmployeeInRoles([ROLE_COOK])): ?>
                            <li class="nav-item"><a class="nav-link fw-bold text-dark" href="orderToBake.php">Orders</a></li>
                        <?php endif; ?>
                    </ul>

                    <?php if (isEmployeeInRoles([ROLE_ADMIN])): ?>
                        <ul class="navbar-nav mb-2 mb-lg-0">
                            <li class="nav-item d-flex align-items-center me-3">
                                <a class="nav-link position-relative" href="message.php" title="Go to messages">
                                    <i class="fas fa-envelope"></i>
                                    <?php if ($unreadCount > 0): ?>
                                        <span class="badge bg-secondary position-absolute top-1 start-100 translate-middle badge-counter">
                                            <?= $unreadCount ?>
                                        </span>
                                    <?php endif; ?>
                                </a>
                            </li>
                        <?php endif; ?>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="text-white small me-2">
                                    <?php echo $employeeName . " - " . $employeeRole ?>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="employee-logout.php">Logout</a></li>
                                <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            </ul>
                        </li>
                        </ul>
                    <?php endif; ?>
            </div>
        </div>
    </nav>
    <br>
    <div class="d-flex flex-column min-vh-100">
