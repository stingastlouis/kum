<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';
require_once '../utils/redirectMessage.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statusId = trim($_POST['status_id']);
    $cakeId = trim($_POST['cake_id']);
    $employeeId = trim($_POST['employee_id']);
    $date = date('Y-m-d H:i:s');

    if (empty($statusId) || empty($cakeId) || empty($employeeId)) {
        redirectWithMessage("../cake.php", "Missing required fields");
    }

    try {
        $stmt = $conn->prepare("INSERT INTO CakeStatus (Cakeid, Statusid, EmployeeId, Datecreated) 
                                VALUES (:cakeid, :statusid, :employeeId, :datecreated)");

        $stmt->bindParam(':cakeid', $cakeId);
        $stmt->bindParam(':statusid', $statusId);
        $stmt->bindParam(':employeeId', $employeeId);
        $stmt->bindParam(':datecreated', $date);

        if ($stmt->execute()) {
            redirectWithMessage("../cake.php", "Cake updated successfully!", true);
        } else {
            redirectWithMessage("../cake.php", "Failed to update cake status");
        }
    } catch (PDOException $e) {
        redirectWithMessage("../cake.php", "Database Error");
    }
} else {
    header("Location: ../cake.php");
    exit;
}
