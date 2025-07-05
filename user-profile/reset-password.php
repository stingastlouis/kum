<?php
session_start();
include '../configs/db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $customerId = $_POST['customerId'];
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];

    if ($newPassword !== $confirmPassword) {
        echo "<script>alert('Passwords do not match.'); window.history.back();</script>";
        exit;
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    try {
        $stmt = $conn->prepare("UPDATE Customer SET Password = :password WHERE Id = :customerId");
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':customerId', $customerId, PDO::PARAM_INT);
        $stmt->execute();


        echo "<script>alert('Password successfully updated.'); window.location.href='../profile.php';</script>";
    } catch (PDOException $e) {
        echo "<script>alert('Error: " . $e->getMessage() . "'); window.history.back();</script>";
    }
}
?>
