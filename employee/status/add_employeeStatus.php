<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';
require_once '../utils/redirectMessage.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statusId = trim($_POST['status_id']);
    $employeeId = trim($_POST['employee_id']);
    $date = date('Y-m-d H:i:s');
    if (empty($statusId) || empty($employeeId)) {
        redirectWithMessage("../employee.php", "Missing required fields");
    }

    try {
        $date = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO EmployeeStatus (EmployeeId, StatusId, DateCreated) VALUES (:employeeid, :statusid, :datecreated)");
        $stmt->bindParam(':employeeid', $employeeId);
        $stmt->bindParam(':statusid', $statusId);
        $stmt->bindParam(':datecreated', $date);

        if ($stmt->execute()) {
            redirectWithMessage("../employee.php", "Employee updated successfully!", true);
        } else {
            redirectWithMessage("../employee.php", "Database error");
        }
    } catch (PDOException $e) {
        redirectWithMessage("../employee.php", "Error");
    }
} else {
    header("Location: ../employee.php");
    exit;
}
