<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';
require_once '../utils/redirectMessage.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['employee_id']) && !empty($_POST['employee_password'])) {
    $employeeId = $_POST['employee_id'];
    $newPassword = $_POST['employee_password'];

    try {
        $conn->beginTransaction();

        if (!filter_var($employeeId, FILTER_VALIDATE_INT)) {
            throw new Exception("Invalid employee ID");
        }

        if (!preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\W_]).{8,}$/", $newPassword)) {
            throw new Exception("Weak password: Minimum 8 characters, including uppercase, lowercase, number, and special character");
        }

        $stmt = $conn->prepare("SELECT 1 FROM Employee WHERE Id = :employeeId");
        $stmt->execute(['employeeId' => $employeeId]);
        if (!$stmt->fetch()) {
            throw new Exception("Employee member not found");
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        $updateStmt = $conn->prepare("UPDATE Employee SET Password = :passwordHash WHERE Id = :employeeId");
        $updateStmt->execute([
            'passwordHash' => $passwordHash,
            'employeeId' => $employeeId
        ]);

        if ($updateStmt->rowCount() > 0) {
            $conn->commit();
            redirectWithMessage("../employee.php", "Password reset successfully!", true);
        } else {
            throw new Exception("Password reset failed: No changes made");
        }
    } catch (Exception $e) {
        $conn->rollBack();
        redirectWithMessage("../employee.php", "Error");
        exit;
    }
} else {
    header('Location: ../employee.php');
    exit;
}
