<?php
include '../../configs/db.php';
require_once '../utils/redirectMessage.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['read'])) {
    $id = (int) $_POST['id'];
    $read = (int) $_POST['read'];

    try {
        $conn->beginTransaction();

        $stmt = $conn->prepare("UPDATE `Messages` SET `Read` = :read WHERE Id = :id");
        $stmt->execute([':read' => $read, ':id' => $id]);
        $conn->commit();

        redirectWithMessage("../message.php", "Message Status modified successfully!", true);
    } catch (PDOException $e) {
        $conn->rollBack();
        redirectWithMessage('../message.php', 'Error updating message read status.');
    }
}
