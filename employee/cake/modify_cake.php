<?php 
include '../../configs/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cakeId = $_POST['cake_id'];
    $name = $_POST['cake_name'];
    $description = $_POST['cake_description'];
    $price = $_POST['cake_price'];
    $discount = $_POST['cake_discount'];
    $stock = $_POST['cake_stock'];
    $categoryId = $_POST['cake_category_id'];

    if (!empty($_FILES['cake_image']['name'])) {
        $targetDir = "../../assets/uploads/";
        $imageName = basename($_FILES['cake_image']['name']);
        $targetFilePath = $targetDir . $imageName;

        if (!move_uploaded_file($_FILES['cake_image']['tmp_name'], $targetFilePath)) {
            header("Location: ../cake.php?error=upload_failed");
            exit();
        }
        $imagePath = $imageName;
    }

    $sql = "UPDATE Cakes SET Name = :name, Description = :description, Price = :price, DiscountPrice = :discount, StockCount = :stock, CategoryId = :categoryId" . (isset($imagePath) ? ", ImagePath = :image" : "") . " WHERE Id = :id";
    $stmt = $conn->prepare($sql);

    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $stmt->bindParam(':description', $description, PDO::PARAM_STR);
    $stmt->bindParam(':price', $price, PDO::PARAM_STR);
    $stmt->bindParam(':discount', $discount, PDO::PARAM_STR);
    $stmt->bindParam(':stock', $stock, PDO::PARAM_INT);
    $stmt->bindParam(':categoryId', $categoryId, PDO::PARAM_INT);
    $stmt->bindParam(':id', $cakeId, PDO::PARAM_INT);

    if (isset($imagePath)) {
        $stmt->bindParam(':image', $imageName, PDO::PARAM_STR);
    }

    $stmt->execute();

    header("Location: ../cake.php?success=1");
    exit();
}

header("Location: ../cake.php?error=invalid_request");
exit();
