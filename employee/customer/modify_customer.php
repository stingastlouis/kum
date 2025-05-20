<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';
require_once '../utils/redirectMessage.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customerId = $_POST['customer_id'];
    $fullname = $_POST['customer_fullname'];
    $email = $_POST['customer_email'];
    $phone = $_POST['customer_phone'];
    $address = $_POST['customer_address'];

    try {
        $conn->beginTransaction();

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        $checkEmail = $conn->prepare("SELECT COUNT(*) FROM Customer WHERE Email = ? AND Id != ?");
        $checkEmail->execute([$email, $customerId]);
        if ($checkEmail->fetchColumn() > 0) {
            throw new Exception("Email already exists");
        }

        if (!preg_match("/^[0-9+\-\s()]*$/", $phone)) {
            throw new Exception("Invalid phone number format");
        }

        $checkCustomer = $conn->prepare("SELECT COUNT(*) FROM Customer WHERE Id = ?");
        $checkCustomer->execute([$customerId]);
        if ($checkCustomer->fetchColumn() == 0) {
            throw new Exception("Customer member not found");
        }

        $stmt = $conn->prepare("UPDATE Customer 
                               SET Fullname = ?, 
                                   Email = ?, 
                                   Phone = ?, 
                                   Address = ?
                               WHERE Id = ?");

        $stmt->execute([
            $fullname,
            $email,
            $phone,
            $address,
            $customerId
        ]);

        if ($stmt->rowCount() >= 0) {
            $conn->commit();
            redirectWithMessage("../customer.php", "Customer updated successfully!", true);
        } else {
            throw new Exception("Error: Unable to update the customer member in the database.");
        }
    } catch (Exception $e) {
        $conn->rollBack();
        $errorMessage = $e->getMessage();
        if (strpos($errorMessage, "Invalid email") !== false) {
            redirectWithMessage("../customer.php", "Invalid Email");
        } else if (strpos($errorMessage, "Email already exists") !== false) {
            redirectWithMessage("../customer.php", "Email already exists");
        } else if (strpos($errorMessage, "Invalid phone") !== false) {
            redirectWithMessage("../customer.php", "Invalid mpbile number");
        } else if (strpos($errorMessage, "Customer member not found") !== false) {
            redirectWithMessage("../customer.php", "Customer not found");
        } else {
            header('Location: ../customer.php?error=general&message=' . urlencode($errorMessage));
            redirectWithMessage("../customer.php", "Error");
        }
        exit;
    }
} else {
    header('Location: ../customer.php');
    exit;
}
