<?php
include '../../configs/db.php';
include '../../configs/timezoneConfigs.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $name = $_POST['cake_name'];
    $categoryId = $_POST['cake_category_id'];
    $description = $_POST['cake_description'];
    $price = $_POST['cake_price'];
    $discount_price = $_POST['cake_discount'];
    $stock = $_POST['cake_stock'];
    

    // File upload handling
    if (!empty($_FILES['cake_image']['name'])) {
        $upload_dir = '../../assets/uploads/';
        $file_name = basename($_FILES['cake_image']['name']);
        $target_file = $upload_dir . $file_name;

        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($_FILES['cake_image']['tmp_name']);

        if (in_array($file_type, $allowed_types)) {
            // Move the file to the uploads directory
            if (move_uploaded_file($_FILES['cake_image']['tmp_name'], $target_file)) {
                try {
                    // Start transaction
                    $conn->beginTransaction();

                    // Insert cake into the database
                    $stmt = $conn->prepare("INSERT INTO Cakes (Name, CategoryId, Description, Price, DiscountPrice, StockCount, ImagePath, DateCreated) 
                                            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$name, $categoryId, $description, $price, $discount_price, $stock, $file_name]);

                    // Check if the insertion was successful
                    if ($stmt->rowCount() > 0) {
                        // Get the ID of the newly created cake
                        $cakeId = $conn->lastInsertId();

                        // Fetch the ID of the "ACTIVE" status
                        $statusStmt = $conn->prepare("SELECT Id FROM Status WHERE StatusName = 'ACTIVE' LIMIT 1");
                        $statusStmt->execute();
                        $statusRow = $statusStmt->fetch(PDO::FETCH_ASSOC);

                        if ($statusRow) {
                            $statusId = $statusRow['Id'];

                            // Insert into cakestatus table
                            $statusInsertStmt = $conn->prepare("INSERT INTO cakestatus (cakeid, statusid, datecreated) 
                                                                VALUES (?, ?, NOW())");
                            $statusInsertStmt->execute([$cakeId, $statusId]);

                            // Commit transaction
                            $conn->commit();

                            // Redirect to the cake list page with success message
                            header('Location: ../cake.php?success=1');
                            exit;
                        } else {
                            throw new Exception("Error: 'ACTIVE' status not found.");
                        }
                    } else {
                        throw new Exception("Error: Unable to insert the cake into the database.");
                    }
                } catch (Exception $e) {
                    // Rollback transaction on error
                    $conn->rollBack();
                    echo "Error: " . $e->getMessage();
                }
            } else {
                echo "Error: Unable to upload the file.";
            }
        } else {
            echo "Error: Only JPEG, PNG, and GIF files are allowed.";
        }
    } else {
        echo "Error: Please upload an image.";
    }
}
?>
