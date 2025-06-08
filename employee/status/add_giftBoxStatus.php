<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';
require_once '../utils/redirectMessage.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statusId = trim($_POST['status_id']);
    $giftboxId = trim($_POST['giftbox_id']);
    $date = date('Y-m-d H:i:s');
    if (empty($statusId) || empty($giftboxId)) {
        redirectWithMessage("../giftbox.php", "Missing required fields");
    }

    try {
        $date = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO GiftBoxStatus (GiftBoxId, StatusId, DateCreated) VALUES (:giftboxId, :statusid, :datecreated)");
        $stmt->bindParam(':giftboxId', $giftboxId);
        $stmt->bindParam(':statusid', $statusId);
        $stmt->bindParam(':datecreated', $date);

        if ($stmt->execute()) {
            redirectWithMessage("../giftbox.php", "Giftbox updated successfully!", true);
            exit;
        } else {
            redirectWithMessage("../giftbox.php", "Database error");
        }
    } catch (PDOException $e) {
        redirectWithMessage("../giftbox.php", "Error");
    }
} else {
    header("Location: ../giftbox.php");
    exit;
}
