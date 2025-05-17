<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statusId = trim($_POST['status_id']);
    $employeeId = trim($_POST['employee_id']);
    $date = date('Y-m-d H:i:s');
    if (empty($statusId) || empty( $employeeId)) {
        echo "<h1>Field missing</h1></center>";
        exit;
    }

    try {
        $date = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO employeestatus (employeeid, statusid, datecreated) VALUES (:employeeid, :statusid, :datecreated)");
        $stmt->bindParam(':employeeid', $employeeId);
        $stmt->bindParam(':statusid', $statusId);
        $stmt->bindParam(':datecreated', $date);

        if ($stmt->execute()) {
            header("Location: ../employee.php?success=1");
            exit;
        } else {
            echo "Error adding employee.";
        }
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage();
    }
} else {
    header("Location: ../employee.php");
    exit;
}
?>
