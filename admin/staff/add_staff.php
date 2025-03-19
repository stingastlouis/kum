<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../staff.php');
    exit;
}

$fullName = $_POST['staff_fullname'];
$email = $_POST['staff_email'];
$phone = $_POST['staff_phone'];
$roleId = $_POST['staff_role_id'];
$password = $_POST['staff_password'];

try {
    $conn->beginTransaction();

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    $checkEmailStmt = $conn->prepare("SELECT COUNT(*) FROM Staff WHERE Email = ?");
    $checkEmailStmt->execute([$email]);
    if ($checkEmailStmt->fetchColumn() > 0) {
        throw new Exception("Email already exists");
    }

    if (!preg_match("/^[0-9+\-\s()]*$/", $phone)) {
        throw new Exception("Invalid phone number format");
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $insertStmt = $conn->prepare(
        "INSERT INTO Staff (Fullname, Email, Phone, RoleId, Password, DateCreated) VALUES (?, ?, ?, ?, ?, NOW())"
    );
    $insertStmt->execute([$fullName, $email, $phone, $roleId, $hashedPassword]);

    if ($insertStmt->rowCount() === 0) {
        throw new Exception("Error: Unable to insert staff member.");
    }

    $staffId = $conn->lastInsertId();

    $statusStmt = $conn->prepare("SELECT Id FROM Status WHERE StatusName = 'ACTIVE' LIMIT 1");
    $statusStmt->execute();
    $statusRow = $statusStmt->fetch(PDO::FETCH_ASSOC);

    if ($statusRow) {
        
        $statusId = $statusRow['Id'];
        $statusInsertStmt = $conn->prepare("INSERT INTO staffstatus (staffid, statusid, datecreated) 
                                            VALUES (?, ?, NOW())");
        $statusInsertStmt->execute([$staffId, $statusId]);
    } else {
        throw new Exception("Error: 'ACTIVE' status not found.");
    }

    $conn->commit();
    header('Location: ../staff.php?success=1');
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
            header("Location: ../staff.php?error=$param");
            exit;
        }
    }
    
    header("Location: ../staff.php?error=general&message=" . urlencode($e->getMessage()));
    exit;
}
