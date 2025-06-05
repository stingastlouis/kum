<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../role.php?error=1");
    exit;
}

$roleName = trim($_POST['role_name']);

if (empty($roleName)) {
    die("<h1>Role name cannot be empty.</h1>");
}

try {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM Roles WHERE Name = :name");
    $stmt->execute([':name' => $roleName]);

    if ($stmt->fetchColumn() > 0) {
        die("<div style='background-color: grey; color:red; top: 25vw; position: relative; text-align: center;'><h1>Role name already exists. Please choose a different name.</h1></div>");
    }

    $stmt = $conn->prepare("INSERT INTO Roles (Name, DateCreated) VALUES (:name, NOW())");
    if ($stmt->execute([':name' => $roleName])) {
        header("Location: ../role.php?success=1");
        exit;
    }

    die("Error adding role.");
} catch (PDOException $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}
