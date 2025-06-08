<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';
require_once '../utils/redirectMessage.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $giftboxName = $_POST['giftbox_name'];
    $giftboxDescription = $_POST['giftbox_description'];
    $giftboxPrice = $_POST['giftbox_price'];
    $giftboxMaxCakes = $_POST['max_giftBoxes'];
    $categoryId = $_POST["giftbox_category_id"] ?? null;

    if (!empty($_FILES['giftbox_image']['name'])) {
        $uploadDirectory = '../../assets/uploads/giftboxes/';
        $fileName = basename($_FILES['giftbox_image']['name']);
        $filePath = $uploadDirectory . $fileName;

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileMimeType = mime_content_type($_FILES['giftbox_image']['tmp_name']);

        if (in_array($fileMimeType, $allowedMimeTypes)) {
            if (move_uploaded_file($_FILES['giftbox_image']['tmp_name'], $filePath)) {
                try {
                    $conn->beginTransaction();
                    $now = date('Y-m-d H:i:s');
                    $insertGiftbox = $conn->prepare("INSERT INTO GiftBox (Name, Description, CategoryId, Price, MaxCakes, ImagePath, DateCreated) 
                                                   VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $insertGiftbox->execute([$giftboxName, $giftboxDescription, $categoryId, $giftboxPrice, $giftboxMaxCakes, $fileName, $now]);

                    if ($insertGiftbox->rowCount() > 0) {
                        $giftboxId = $conn->lastInsertId();

                        $statusQuery = $conn->prepare("SELECT Id FROM Status WHERE StatusName = 'ACTIVE' LIMIT 1");
                        $statusQuery->execute();
                        $status = $statusQuery->fetch(PDO::FETCH_ASSOC);

                        if ($status) {
                            $statusId = $status['Id'];

                            $insertStatus = $conn->prepare("INSERT INTO GiftBoxStatus (GiftBoxId, StatusId, DateCreated) 
                                                           VALUES (?, ?, ?)");
                            $insertStatus->execute([$giftboxId, $statusId, $now]);

                            $conn->commit();
                            redirectWithMessage("../giftbox.php", "GiftBox added successfully!", true);
                        }
                        throw new Exception("'ACTIVE' status not found.");
                    }
                    throw new Exception("Failed to insert giftbox.");
                } catch (Exception $e) {
                    $conn->rollBack();
                    echo "Error: " . $e->getMessage();
                    redirectWithMessage("../giftbox.php", "Error");
                }
            } else {
                redirectWithMessage("../giftbox.php", "File upload failed.");
            }
        } else {
            redirectWithMessage("../giftbox.php", "Allowed types: JPEG, PNG, GIF.");
        }
    } else {
        redirectWithMessage("../giftbox.php", "Image upload required.");
    }
}
