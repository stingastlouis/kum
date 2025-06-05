<?php

include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';
require_once '../utils/redirectMessage.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryTitle = trim($_POST['category_name']);

    if (empty($categoryTitle)) {
        redirectWithMessage("../category.php", "Category name cannot be empty.");
    }

    try {
        $queryCheck = $conn->prepare("SELECT COUNT(*) FROM Category WHERE Name = :title");
        $queryCheck->bindParam(':title', $categoryTitle);
        $queryCheck->execute();

        $categoryExists = $queryCheck->fetchColumn();
        if ($categoryExists > 0) {
            redirectWithMessage("../category.php", "Category name already exists. Please choose a different name.");
        }

        $currentTimestamp = date('Y-m-d H:i:s');
        $queryInsert = $conn->prepare("INSERT INTO Category (Name, Datecreated) VALUES (:title, :created_at)");
        $queryInsert->bindParam(':title', $categoryTitle);
        $queryInsert->bindParam(':created_at', $currentTimestamp);

        if ($queryInsert->execute()) {
            redirectWithMessage("../category.php", "Category added successfully!", true);
        } else {
            redirectWithMessage("../category.php", "Error adding category.");
        }
    } catch (PDOException $exception) {
        redirectWithMessage("../category.php", "Database error");
    }
} else {
    header("Location: ../category.php");
    exit;
}
