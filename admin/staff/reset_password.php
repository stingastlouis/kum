<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['staff_id']) && !empty($_POST['staff_password'])) {
    $staffId = $_POST['staff_id'];
    $newPassword = $_POST['staff_password'];

    try {
        $conn->beginTransaction();

        if (!filter_var($staffId, FILTER_VALIDATE_INT)) {
            throw new Exception("Invalid staff ID");
        }

        if (!preg_match("/^(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[\W_]).{8,}$/", $newPassword)) {
            throw new Exception("Weak password: Minimum 8 characters, including uppercase, lowercase, number, and special character");
        }

        $stmt = $conn->prepare("SELECT 1 FROM Staff WHERE Id = :staffId");
        $stmt->execute(['staffId' => $staffId]);
        if (!$stmt->fetch()) {
            throw new Exception("Staff member not found");
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        $updateStmt = $conn->prepare("UPDATE Staff SET Password = :passwordHash WHERE Id = :staffId");
        $updateStmt->execute([
            'passwordHash' => $passwordHash,
            'staffId' => $staffId
        ]);

        if ($updateStmt->rowCount() > 0) {
            $conn->commit();
            header('Location: ../staff.php?success=1');
            exit;
        } else {
            throw new Exception("Password reset failed: No changes made");
        }

    } catch (Exception $e) {
        $conn->rollBack();
        header("Location: ../staff.php?error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    header('Location: ../staff.php?error=invalid_request');
    exit;
}
?>
