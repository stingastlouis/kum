<?php
include '../../configs/db.php';

if (isset($_POST['customer_id'])) {
    $customerId = $_POST['customer_id'];

    $stmt = $conn->prepare("DELETE FROM Customer WHERE Id = :id");
    $stmt->bindParam(':id', $customerId, PDO::PARAM_INT);
    $stmt->execute();

    redirectWithMessage("../customer.php", "Customer deleted successfully!", true);
} else {
    header("Location: ../customer.php");
    exit();
}
