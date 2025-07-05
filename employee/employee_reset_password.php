<?php

require_once 'auth.php';
require_once './utils/redirectMessage.php';
requireEmployeeLogin([ROLE_ADMIN, ROLE_COOK, ROLE_DELIVERY]);

include '../configs/db.php';

$redirectUrl = $_SERVER['HTTP_REFERER'] ?? '../profile.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = $_POST['employeeId'] ?? null;
    $newPassword = $_POST['newPassword'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    if (empty($employeeId) || empty($newPassword) || empty($confirmPassword)) {
        redirectWithMessage($redirectUrl, "Missing required fields");
    }

    if ($newPassword !== $confirmPassword) {
        redirectWithMessage($redirectUrl, "Passwords do not match.");
    }

    try {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE Employee SET Password = :password WHERE Id = :id");
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':id', $employeeId, PDO::PARAM_INT);

        if ($stmt->execute()) {
            redirectWithMessage($redirectUrl, "Password updated successfully.", true);
        } else {
            redirectWithMessage($redirectUrl, "Failed to update password.");
        }
    } catch (PDOException $e) {
        redirectWithMessage($redirectUrl, "Database error: " . $e->getMessage());
    }

} else {
    redirectWithMessage($redirectUrl, "Invalid request method.");
}
