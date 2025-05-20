<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';
require_once '../utils/redirectMessage.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employeeId = $_POST['employee_id'];
    $fullname = $_POST['employee_fullname'];
    $email = $_POST['employee_email'];
    $phone = $_POST['employee_phone'];
    $roleId = $_POST['employee_role_id'];

    try {
        $conn->beginTransaction();

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        $checkEmail = $conn->prepare("SELECT COUNT(*) FROM Employee WHERE Email = ? AND Id != ?");
        $checkEmail->execute([$email, $employeeId]);
        if ($checkEmail->fetchColumn() > 0) {
            throw new Exception("Email already exists");
        }

        if (!preg_match("/^[0-9+\-\s()]*$/", $phone)) {
            throw new Exception("Invalid phone number format");
        }

        $checkEmployee = $conn->prepare("SELECT COUNT(*) FROM Employee WHERE Id = ?");
        $checkEmployee->execute([$employeeId]);
        if ($checkEmployee->fetchColumn() == 0) {
            throw new Exception("Employee member not found");
        }

        $stmt = $conn->prepare("UPDATE Employee 
                               SET Fullname = ?, 
                                   Email = ?, 
                                   Phone = ?, 
                                   RoleId = ?
                               WHERE Id = ?");

        $stmt->execute([
            $fullname,
            $email,
            $phone,
            $roleId,
            $employeeId
        ]);

        if ($stmt->rowCount() >= 0) {
            $conn->commit();

            redirectWithMessage("../employee.php", "Employee updated successfully!", true);
        } else {
            throw new Exception("Error: Unable to update the employee member in the database.");
        }
    } catch (Exception $e) {
        $conn->rollBack();

        $errorMessage = $e->getMessage();
        if (strpos($errorMessage, "Invalid email") !== false) {
            redirectWithMessage("../employee.php", "Email invalid");
        } else if (strpos($errorMessage, "Email already exists") !== false) {
            redirectWithMessage("../employee.php", "Email already Exists");
        } else if (strpos($errorMessage, "Invalid phone") !== false) {
            redirectWithMessage("../employee.php", "Invalid Phone Number");
        } else if (strpos($errorMessage, "Employee member not found") !== false) {
            redirectWithMessage("../employee.php", "Employee not found");
        } else {
            redirectWithMessage("../employee.php", "Error");
        }
        exit;
    }
} else {
    header('Location: ../employee.php');
    exit;
}
