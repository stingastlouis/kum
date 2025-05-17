<?php
include '../../configs/db.php';

if (isset($_POST['category_id'])) {
    $categoryKey = $_POST['category_id'];

    var_dump($categoryKey);
    
    $queryDelete = $conn->prepare("DELETE FROM category WHERE Id = :key");
    $queryDelete->bindParam(':key', $categoryKey, PDO::PARAM_INT);
    $queryDelete->execute();

    header("Location: ../category.php?success=1");
    exit();
} else {
    header("Location: ../category.php?error=1");
    exit();
}
