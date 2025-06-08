<?php
include '../../configs/db.php';
require_once '../utils/redirectMessage.php';

if (isset($_POST['giftbox_id'])) {
    $giftboxId = $_POST['giftbox_id'];

    $stmt = $conn->prepare("DELETE FROM GiftBox WHERE ID = :id");
    $stmt->bindParam(':id', $giftboxId, PDO::PARAM_INT);
    $stmt->execute();

    redirectWithMessage("../giftbox.php", "GiftBox deleted successfully!", true);
} else {
    redirectWithMessage("../giftbox.php", "Error");
}
