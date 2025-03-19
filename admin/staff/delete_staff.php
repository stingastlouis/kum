<?php 
include '../../configs/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['staff_id'])) {
    $staff_id = $_POST['staff_id'];
    
    try {
        $stmt = $conn->prepare("DELETE FROM Staff WHERE Id = :id");
        $stmt->bindParam(':id', $staff_id, PDO::PARAM_INT);
        $stmt->execute();
        
        header("Location: ../staff.php?success=1");
        exit();
    } catch (Exception $e) {
        header("Location: ../staff.php?error=1&message=" . urlencode($e->getMessage()));
        exit();
    }
} 

header("Location: ../staff.php?error=1");
exit();