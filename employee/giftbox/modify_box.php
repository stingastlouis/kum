<?php
include '../../configs/db.php';
require_once '../utils/redirectMessage.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $giftboxId = $_POST['giftbox_id'];
    $name = $_POST['giftbox_name'];
    $description = $_POST['giftbox_description'];
    $price = $_POST['giftbox_price'];
    $maxCake = $_POST['giftbox_selection'];
    $categoryId = $_POST["giftbox_category_id"];

    if (!empty($_FILES['image']['name'])) {
        $targetDir = "../../assets/uploads/giftboxes/";
        $imageName = basename($_FILES['image']['name']);
        $targetFilePath = $targetDir . $imageName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFilePath)) {
            $imagePath = $imageName;
        } else {
            redirectWithMessage("../giftbox.php", "File upload failed.");
        }
    }

    if (isset($imagePath)) {
        $stmt = $conn->prepare("UPDATE GiftBox SET MaxCakes = :max, Name = :name, CategoryId = :cat_id, Description = :description, ImagePath = :image, Price = :price WHERE Id = :id");
        $stmt->bindParam(':image', $imageName, PDO::PARAM_STR);
    } else {
        $stmt = $conn->prepare("UPDATE GiftBox SET MaxCakes = :max, Name = :name, CategoryId = :cat_id, Description = :description, Price = :price WHERE Id = :id");
    }

    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $stmt->bindParam(':description', $description, PDO::PARAM_STR);
    $stmt->bindParam(':price', $price, PDO::PARAM_STR);
    $stmt->bindParam(':id', $giftboxId, PDO::PARAM_INT);
    $stmt->bindParam(':max', $maxCake, PDO::PARAM_INT);
    $stmt->bindParam(':cat_id', $categoryId, PDO::PARAM_INT);
    $stmt->execute();

    redirectWithMessage("../giftbox.php", "GiftBox updated successfully!", true);
} else {
    header("Location: ../giftbox.php");
    exit();
}
