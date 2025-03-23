<?php 
include '../../configs/db.php';

if (isset($_POST['giftbox_id'])) {
    $giftboxId = $_POST['giftbox_id'];

    $stmt = $conn->prepare("DELETE FROM Giftbox WHERE ID = :id");
    $stmt->bindParam(':id', $giftboxId, PDO::PARAM_INT);
    $stmt->execute();

    header("Location: ../giftbox.php?success=1");
    exit();
} else {
    header("Location: ../giftbox.php?error=1");
    exit();
}
