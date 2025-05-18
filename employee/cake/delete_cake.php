<?php
include '../../configs/db.php';
require_once '../utils/redirectMessage.php';

if (isset($_POST['cake_id'])) {
    $productId = $_POST['cake_id'];

    $stmt = $conn->prepare("DELETE FROM Cakes WHERE ID = :id");
    $stmt->bindParam(':id', $productId, PDO::PARAM_INT);
    $stmt->execute();

    redirectWithMessage("../cake.php", "Cake deleted successfully!", true);
    exit();
}

redirectWithMessage("../cake.php", "Cake id not found");
exit();
