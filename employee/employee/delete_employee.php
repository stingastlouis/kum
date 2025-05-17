<?php 
include '../../configs/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['employee_id'])) {
    $employee_id = $_POST['employee_id'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM Employee WHERE Id = :id");
        $stmt->bindParam(':id', $employee_id, PDO::PARAM_INT);
        $stmt->execute();
        
        header("Location: ../employee.php?success=1");
        exit();
    } catch (Exception $e) {
        header("Location: ../employee.php?error=1&message=" . urlencode($e->getMessage()));
        exit();
    }
} 

header("Location: ../employee.php?error=1");
exit();