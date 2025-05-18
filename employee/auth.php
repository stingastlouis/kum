<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireRole(array $allowedRoles)
{
    if (!isEmployeeInRoles($allowedRoles)) {
        header('Location: ../unauthorized.php');
        exit;
    }
}

function isEmployeeInRole(string $role): bool
{
    return isset($_SESSION['employeeId']) &&
        strtolower($_SESSION['employee_role']) === strtolower($role);
}

function isEmployeeInRoles(array $roles): bool
{
    if (!isset($_SESSION['employeeId'])) {
        return false;
    }

    $userRole = strtolower($_SESSION['employee_role']);
    $normalizedRoles = array_map('strtolower', $roles);

    return in_array($userRole, $normalizedRoles);
}

function getEmployeeRole(): ?string
{
    return $_SESSION['employee_role'] ?? null;
}

function isEmployeeLoggedIn(): bool
{
    return isset($_SESSION['employeeId']);
}

function requireEmployeeLogin()
{
    if (!isset($_SESSION['employeeId'])) {
        header("Location: ./employee-login.php");
        exit;
    }
}


define('ROLE_ADMIN', 'admin');
define('ROLE_VIEWER', 'viewer');
define('ROLE_DELIVERY', 'delivery');
