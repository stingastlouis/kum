<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $giftboxName = $_POST['giftbox_name'];
    $giftboxDescription = $_POST['giftbox_description'];
    $giftboxPrice = $_POST['giftbox_price'];
    $giftboxMaxCakes = $_POST['max_giftBoxes'];
    $categoryId = $_POST["giftbox_category_id"] ?? null;
    
    if (!empty($_FILES['giftbox_image']['name'])) {
        $uploadDirectory = '../../assets/uploads/';
        $fileName = basename($_FILES['giftbox_image']['name']);
        $filePath = $uploadDirectory . $fileName;
        
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileMimeType = mime_content_type($_FILES['giftbox_image']['tmp_name']);
        
        if (in_array($fileMimeType, $allowedMimeTypes)) {
            if (move_uploaded_file($_FILES['giftbox_image']['tmp_name'], $filePath)) {
                try {
                    $conn->beginTransaction();
                    
                    $insertGiftbox = $conn->prepare("INSERT INTO Giftbox (Name, Description, CategoryId, Price, MaxCakes, ImagePath, DateCreated) 
                                                   VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $insertGiftbox->execute([$giftboxName, $giftboxDescription, $categoryId, $giftboxPrice, $giftboxMaxCakes, $fileName]);
                    
                    if ($insertGiftbox->rowCount() > 0) {
                        $giftboxId = $conn->lastInsertId();
                        
                        $statusQuery = $conn->prepare("SELECT Id FROM Status WHERE StatusName = 'ACTIVE' LIMIT 1");
                        $statusQuery->execute();
                        $status = $statusQuery->fetch(PDO::FETCH_ASSOC);
                        
                        if ($status) {
                            $statusId = $status['Id'];
                            
                            $insertStatus = $conn->prepare("INSERT INTO giftboxstatus (giftboxid, statusid, datecreated) 
                                                           VALUES (?, ?, NOW())");
                            $insertStatus->execute([$giftboxId, $statusId]);
                            
                            $conn->commit();
                            header('Location: ../giftbox.php?success=1');
                            exit;
                        }
                        throw new Exception("'ACTIVE' status not found.");
                    }
                    throw new Exception("Failed to insert giftbox.");
                } catch (Exception $e) {
                    $conn->rollBack();
                    echo "Error: " . $e->getMessage();
                }
            } else {
                echo "Error: File upload failed.";
            }
        } else {
            echo "Error: Invalid file format. Allowed types: JPEG, PNG, GIF.";
        }
    } else {
        echo "Error: Image upload required.";
    }
}
?>
