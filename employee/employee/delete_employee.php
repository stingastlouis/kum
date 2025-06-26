<?php
include '../../configs/db.php';
require_once '../utils/redirectMessage.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['employee_id'])) {
    $employee_id = $_POST['employee_id'];

    try {
        $stmt = $conn->prepare("DELETE FROM Employee WHERE Id = :id");
        $stmt->bindParam(':id', $employee_id, PDO::PARAM_INT);
        $stmt->execute();

        redirectWithMessage("../employee.php", "Employee deleted successfully!", true);
    } catch (Exception $e) {
        redirectWithMessage("../employee.php", "Error");
    }
}

header("Location: ../employee.php");
exit();
