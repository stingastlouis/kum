<?php
include '../../configs/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $giftboxId = $_POST['giftbox_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $discount = $_POST['discount'];
    $categoryId = $_POST["giftbox_category_id"];

    if (!empty($_FILES['image']['name'])) {
        $targetDir = "../../assets/uploads/";
        $imageName = basename($_FILES['image']['name']);
        $targetFilePath = $targetDir . $imageName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFilePath)) {
            $imagePath = $imageName;
        } else {
            header("Location: ../giftbox.php?error=upload_failed");
            exit();
        }
    }

    if (isset($imagePath)) {
        $stmt = $conn->prepare("UPDATE Giftbox SET Name = :name, CategoryId = :cat_id, Description = :description, ImagePath = :image, Price = :price, DiscountPrice = :discount WHERE Id = :id");
        $stmt->bindParam(':image', $imageName, PDO::PARAM_STR);
    } else {
        $stmt = $conn->prepare("UPDATE Giftbox SET Name = :name, CategoryId = :cat_id, Description = :description, Price = :price, DiscountPrice = :discount WHERE Id = :id");
    }

    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $stmt->bindParam(':description', $description, PDO::PARAM_STR);
    $stmt->bindParam(':price', $price, PDO::PARAM_STR);
    $stmt->bindParam(':discount', $discount, PDO::PARAM_STR);
    $stmt->bindParam(':id', $giftboxId, PDO::PARAM_INT);
    $stmt->bindParam(':cat_id', $categoryId, PDO::PARAM_INT);
    $stmt->execute();

    header("Location: ../giftbox.php?success=1");
    exit();
} else {
    header("Location: ../giftbox.php?error=invalid_request");
    exit();
}
