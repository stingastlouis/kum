<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statusId = trim($_POST['status_id']);
    $cakeId = trim($_POST['cake_id']);
    $date = date('Y-m-d H:i:s');
    if (empty($statusId) || empty($cakeId)) {
        echo "<h1>Field missing</h1></center>";
        exit;
    }

    try {
        $date = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO cakestatus (cakeid, statusid, datecreated) VALUES (:cakeid, :statusid, :datecreated)");
        $stmt->bindParam(':cakeid', $cakeId);
        $stmt->bindParam(':statusid', $statusId);
        $stmt->bindParam(':datecreated', $date);

        if ($stmt->execute()) {
            header("Location: ../cake.php?success=1");
            exit;
        } else {
            echo "Error adding product.";
        }
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
    }
} else {
    header("Location: ../cake.php");
    exit;
}
?>
