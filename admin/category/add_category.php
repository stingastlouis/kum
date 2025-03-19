<?php

include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryTitle = trim($_POST['category_name']);
    
    if (empty($categoryTitle)) {
        echo "<h1>Category name cannot be empty.</h1></center>";
        exit;
    }

    try {
        $queryCheck = $conn->prepare("SELECT COUNT(*) FROM category WHERE name = :title");
        $queryCheck->bindParam(':title', $categoryTitle);
        $queryCheck->execute();
        
        $categoryExists = $queryCheck->fetchColumn();
        if ($categoryExists > 0) {
            echo "<div style='background-color: grey; color:red; top: 25vw; position: relative;'><center><h1>Category name already exists. Please choose a different name.</h1></center></div>";
            exit;
        }

        $currentTimestamp = date('Y-m-d H:i:s');
        $queryInsert = $conn->prepare("INSERT INTO category (name, datecreated) VALUES (:title, :created_at)");
        $queryInsert->bindParam(':title', $categoryTitle);
        $queryInsert->bindParam(':created_at', $currentTimestamp);

        if ($queryInsert->execute()) {
            header("Location: ../category.php?success=1");
            exit;
        } else {
            echo "Error adding category.";
        }
    } catch (PDOException $exception) {
        echo "Database error: " . $exception->getMessage();
    }
} else {
    header("Location: ../category.php");
    exit;
}
?>
