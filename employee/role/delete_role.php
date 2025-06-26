<?php
include '../../configs/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['role_id'])) {
    header("Location: ../role.php?error=1");
    exit;
}

$roleId = filter_var($_POST['role_id'], FILTER_VALIDATE_INT);

if (!$roleId) {
    header("Location: ../role.php?error=1");
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM Roles WHERE Id = :id");
    $stmt->execute([':id' => $roleId]);
    header("Location: ../role.php?success=1");
    exit;
} catch (PDOException $e) {
    header("Location: ../role.php?error=1");
    exit;
}
