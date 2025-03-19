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

    // Handle image upload
    if (!empty($_FILES['cake_image']['name'])) {
        $targetDir = "../../assets/uploads/";
        $imageName = basename($_FILES['cake_image']['name']);
        $targetFilePath = $targetDir . $imageName;

        // Validate and move uploaded file
        if (move_uploaded_file($_FILES['cake_image']['tmp_name'], $targetFilePath)) {
            $imagePath = $imageName;
        } else {
            header("Location: ../cake.php?error=upload_failed");
            exit();
        }
    }

    // Update the cake in the database
    if (isset($imagePath)) {
        $stmt = $conn->prepare("UPDATE Cakes SET Name = :name, Description = :description, ImagePath = :image, Price = :price, DiscountPrice = :discount, StockCount = :stock, CategoryId = :categoryId WHERE Id = :id");
        $stmt->bindParam(':image', $imageName, PDO::PARAM_STR);
    } else {
        $stmt = $conn->prepare("UPDATE Cakes SET Name = :name, Description = :description, Price = :price, DiscountPrice = :discount, StockCount = :stock, CategoryId = :categoryId WHERE Id = :id");
    }

    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $stmt->bindParam(':description', $description, PDO::PARAM_STR);
    $stmt->bindParam(':price', $price, PDO::PARAM_STR);
    $stmt->bindParam(':discount', $discount, PDO::PARAM_STR);
    $stmt->bindParam(':stock', $stock, PDO::PARAM_INT);
    $stmt->bindParam(':categoryId', $categoryId, PDO::PARAM_INT);
    $stmt->bindParam(':id', $cakeId, PDO::PARAM_INT);
    $stmt->execute();

    header("Location: ../cake.php?success=1");
    exit();
} else {
    header("Location: ../cake.php?error=invalid_request");
    exit();
}
?>
