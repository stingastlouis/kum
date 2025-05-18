<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statusId = trim($_POST['status_id']);
    $cakeId = trim($_POST['cake_id']);
    $employeeId = trim($_POST['employee_id']);
    $date = date('Y-m-d H:i:s');

    if (empty($statusId) || empty($cakeId) || empty($employeeId)) {
        header("Location: ../cake.php?error=Missing+required+fields.");
        exit;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO CakeStatus (Cakeid, Statusid, EmployeeId, Datecreated) 
                                VALUES (:cakeid, :statusid, :employeeId, :datecreated)");

        $stmt->bindParam(':cakeid', $cakeId);
        $stmt->bindParam(':statusid', $statusId);
        $stmt->bindParam(':employeeId', $employeeId);
        $stmt->bindParam(':datecreated', $date);

        if ($stmt->execute()) {
            header("Location: ../cake.php?success=Cake+status+updated+successfully.");
            exit;
        } else {
            header("Location: ../cake.php?error=Failed+to+update+cake+status.");
            exit;
        }
    } catch (PDOException $e) {
        $message = urlencode("Database error: " . $e->getMessage());
        header("Location: ../cake.php?error=$message");
        exit;
    }
} else {
    header("Location: ../cake.php?error=Invalid+request+method.");
    exit;
}
