<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

unset(
    $_SESSION['employeeId'],
    $_SESSION['employee_fullname'],
    $_SESSION['employee_role'],
    $_SESSION['employee_status']
);

header("Location: employee-login.php");
exit;
