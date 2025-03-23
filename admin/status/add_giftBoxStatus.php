<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statusId = trim($_POST['status_id']);
    $giftboxId = trim($_POST['giftbox_id']);
    $date = date('Y-m-d H:i:s');
    if (empty($statusId) || empty($giftboxId)) {
        echo "<h1>Field missing</h1></center>";
        exit;
    }

    try {
        $date = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO giftboxstatus (giftboxid, statusid, datecreated) VALUES (:giftboxId, :statusid, :datecreated)");
        $stmt->bindParam(':giftboxId', $giftboxId);
        $stmt->bindParam(':statusid', $statusId);
        $stmt->bindParam(':datecreated', $date);

        if ($stmt->execute()) {
            header("Location: ../giftbox.php?success=1");
            exit;
        } else {
            echo "Error adding Giftbox.";
        }
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
    }
} else {
    header("Location: ../giftbox.php");
    exit;
}
?>
