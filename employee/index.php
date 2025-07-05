<?php

include 'auth.php';

if (!isset($_SESSION['employeeId'])) {
    header("Location: ./employee-login.php");
    exit;
}

if (strtolower($_SESSION['employee_role']) == ROLE_DELIVERY) {
    header("Location: ./delivery.php");
}

if (strtolower($_SESSION['employee_role']) == ROLE_COOK) {
    header("Location: ./orderToBake.php");
}


include 'includes/header.php';
?>
<div class="container-fluid" >
    <div class="d-sm-flex justify-content-between align-items-center mb-4">
        <h3 class="text-dark mb-0">Dashboard</h3>
    </div>
    <div class="row">
        <?php require_once 'statistics/currentMonth.php' ?>
        <?php require_once 'statistics/customerCount.php' ?>
        <?php require_once 'statistics/employeeCount.php' ?>
        <?php require_once 'statistics/deliveryFortheMonth.php' ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <div class="row">
        <?php require_once 'statistics/annualIncomeCurve.php' ?>
        <?php require_once 'statistics/annualSourceIncome.php' ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>