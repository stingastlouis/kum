<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';
require_once '../utils/redirectMessage.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $customerId = $_POST['customer_id'];
    $newPassword = $_POST['customer_password'];

    try {
        $conn->beginTransaction();


        if (empty($customerId)) {
            throw new Exception("Customer ID is required");
        }

        if (!preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\W_]).{8,}$/", $newPassword)) {
            throw new Exception("Password must be at least 8 characters long and include a mix of uppercase, lowercase, numbers, and special characters");
        }
        $checkCustomer = $conn->prepare("SELECT COUNT(*) FROM Customer WHERE Id = ?");
        $checkCustomer->execute([$customerId]);
        if ($checkCustomer->fetchColumn() == 0) {
            throw new Exception("Customer member not found");
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE Customer SET Password = ? WHERE Id = ?");
        $stmt->execute([$passwordHash, $customerId]);
        if ($stmt->rowCount() > 0) {
            $conn->commit();
            redirectWithMessage("../customer.php", "Password reset for customer!", true);
            exit;
        } else {
            throw new Exception("Error: Unable to reset the password. No changes were made.");
        }
    } catch (Exception $e) {
        $conn->rollBack();
        $errorMessage = $e->getMessage();
        if (strpos($errorMessage, "Password must be") !== false) {
            redirectWithMessage("../customer.php", "Password weak");
        } else if (strpos($errorMessage, "Customer member not found") !== false) {
            redirectWithMessage("../customer.php", "Customer not found");
        } else {
            redirectWithMessage("../customer.php", "Error");
        }
        exit;
    }
} else {
    header('Location: ../customer.php');
    exit;
}
