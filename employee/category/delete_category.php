<?php
include '../../configs/db.php';
require_once '../utils/redirectMessage.php';
if (isset($_POST['category_id'])) {
    $categoryKey = $_POST['category_id'];

    var_dump($categoryKey);

    $queryDelete = $conn->prepare("DELETE FROM Category WHERE Id = :key");
    $queryDelete->bindParam(':key', $categoryKey, PDO::PARAM_INT);
    $queryDelete->execute();

    redirectWithMessage("../category.php", "Category deleted successfully!", true);
} else {
    redirectWithMessage("../category.php", "Database error");
}
