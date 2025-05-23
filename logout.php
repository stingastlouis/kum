<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

unset($_SESSION['customerId']);
unset($_SESSION['customer_fullname']);

session_regenerate_id(true);

header("Location: signin.php");
exit;
