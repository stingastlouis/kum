<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';
require_once '../utils/redirectMessage.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../employee.php');
    exit;
}

$fullName = $_POST['employee_fullname'];
$email = $_POST['employee_email'];
$phone = $_POST['employee_phone'];
$roleId = $_POST['employee_role_id'];
$password = $_POST['employee_password'];

try {
    $conn->beginTransaction();

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    $checkEmailStmt = $conn->prepare("SELECT COUNT(*) FROM Employee WHERE Email = ?");
    $checkEmailStmt->execute([$email]);
    if ($checkEmailStmt->fetchColumn() > 0) {
        throw new Exception("Email already exists");
    }

    if (!preg_match("/^[0-9+\-\s()]*$/", $phone)) {
        throw new Exception("Invalid phone number format");
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $now = date('Y-m-d H:i:s');
    $insertStmt = $conn->prepare(
        "INSERT INTO Employee (Fullname, Email, Phone, RoleId, Password, DateCreated) VALUES (?, ?, ?, ?, ?, ?)"
    );
    $insertStmt->execute([$fullName, $email, $phone, $roleId, $hashedPassword, $now]);

    if ($insertStmt->rowCount() === 0) {
        throw new Exception("Error: Unable to insert employee member.");
    }

    $employeeId = $conn->lastInsertId();

    $statusStmt = $conn->prepare("SELECT Id FROM Status WHERE StatusName = 'ACTIVE' LIMIT 1");
    $statusStmt->execute();
    $statusRow = $statusStmt->fetch(PDO::FETCH_ASSOC);

    if ($statusRow) {

        $statusId = $statusRow['Id'];
        $statusInsertStmt = $conn->prepare("INSERT INTO EmployeeStatus (EmployeeId, StatusId, DateCreated) 
                                            VALUES (?, ?, ?)");
        $statusInsertStmt->execute([$employeeId, $statusId, $now]);
    } else {
        throw new Exception("Error: 'ACTIVE' status not found.");
    }

    $conn->commit();
    redirectWithMessage("../employee.php", "Employee added successfully!", true);
    exit;
} catch (Exception $e) {
    $conn->rollBack();

    $errorMessages = [
        "Invalid email" => "invalid_email",
        "Email already exists" => "email_exists",
        "Invalid phone" => "invalid_phone"
    ];

    foreach ($errorMessages as $key => $param) {
        if (strpos($e->getMessage(), $key) !== false) {
            redirectWithMessage("../employee.php", "$param");
            exit;
        }
    }

    redirectWithMessage("../employee.php", "Error");
}
