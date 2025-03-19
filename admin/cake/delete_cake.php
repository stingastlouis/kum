<?php 
include '../../configs/db.php';

if (isset($_POST['cake_id'])) {
    $productId = $_POST['cake_id'];

    $stmt = $conn->prepare("DELETE FROM Cakes WHERE ID = :id");
    $stmt->bindParam(':id', $productId, PDO::PARAM_INT);
    $stmt->execute();

    header("Location: ../cake.php?success=1");
    exit();
} else {
    header("Location: ../cake.php?error=1");
    exit();
}
