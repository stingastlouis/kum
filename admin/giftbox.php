<?php 
include 'includes/header.php';
include '../configs/db.php';

$success = $_GET["success"] ?? null;
$stmt = $conn->prepare(" 
    SELECT e.*, s.StatusName AS LatestStatus 
    FROM GiftBox e
    LEFT JOIN (
        SELECT es.GiftBoxId, MAX(es.Id) AS LatestStatusId
        FROM GiftBoxStatus es
        GROUP BY es.GiftBoxId
    ) latest_es ON e.Id = latest_es.GiftBoxId
    LEFT JOIN GiftBoxStatus es ON latest_es.LatestStatusId = es.Id
    LEFT JOIN Status s ON es.StatusId = s.Id;");
$stmt->execute();
$giftBoxes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt2 = $conn->prepare("SELECT * FROM Status");
$stmt2->execute();
$statuses = $stmt2->fetchAll(PDO::FETCH_ASSOC);

$stmt3 = $conn->prepare("SELECT * FROM Category");
$stmt3->execute();
$categories = $stmt3->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h3 class="text-dark mb-4">Gift Boxes</h3>
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <p class="text-primary m-0 fw-bold">Gift Box List</p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGiftBoxModal">Add Gift Box</button>
        </div>
        <div class="card-body">
            <div class="table-responsive table mt-2" id="dataTable" role="grid" aria-describedby="dataTable_info">
                <table class="table my-0">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Max Cake</th>
                            <th>Image</th>
                            <th>Latest Status</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($giftBoxes as $giftBox): ?>
                            <tr>
                                <td><?= htmlspecialchars($giftBox['Id']) ?></td>
                                <td><?= htmlspecialchars($giftBox['Name']) ?></td>
                                <td><?= htmlspecialchars($giftBox['Description']) ?></td>
                                <td><?= htmlspecialchars($giftBox['Price']) ?></td>
                                <td><?= htmlspecialchars($giftBox['MaxCakes']) ?></td>
                                <td>
                                    <img src="../assets/uploads/<?= htmlspecialchars($giftBox['ImagePath']) ?>" alt="<?= htmlspecialchars($giftBox['Name']) ?>" style="width: 100px; height: auto;">
                                </td>
                                <td><?= htmlspecialchars($giftBox['LatestStatus']) ?: 'No Status' ?></td>
                                <td><?= htmlspecialchars($giftBox['DateCreated']) ?></td>
                                <td>
                                    <button class='btn btn-warning btn-sm edit-giftbox-btn' 
                                        data-id='<?= $giftBox['Id'] ?>' 
                                        data-name='<?= $giftBox['Name'] ?>' 
                                        data-description='<?= $giftBox['Description'] ?>' 
                                        data-price='<?= $giftBox['Price'] ?>' 
                                        data-discount='<?= $giftBox['MaxCakes'] ?>' 
                                        data-stock='<?= $giftBox['StockCount'] ?>'>Edit</button>
                                    <button class="btn btn-danger btn-sm btn-del" data-bs-toggle="modal" data-bs-target="#deleteGiftBoxModal" data-id="<?= $giftBox['Id'] ?>">Delete</button>
                                    <form method="POST" action="status/add_giftBoxStatus.php" style="display: inline;">
                                        <input type="hidden" name="giftbox_id" value="<?= $giftBox['Id'] ?>">
                                        <select name="status_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="" disabled selected>Change Status</option>
                                            <?php foreach ($statuses as $status): ?>
                                                <option value="<?= $status['Id'] ?>"><?= htmlspecialchars($status['StatusName']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addGiftBoxModal" tabindex="-1" aria-labelledby="addGiftBoxModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addGiftBoxModalLabel">Add Gift Box</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="giftbox/add_box.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="giftBoxName" class="form-label">Gift Box Name</label>
                        <input type="text" class="form-control" id="giftBoxName" name="giftbox_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="giftBoxDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="giftBoxDescription" name="giftbox_description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="giftBoxPrice" class="form-label">Price</label>
                        <input type="number" step="0.01" class="form-control" id="giftBoxPrice" name="giftbox_price" required>
                    </div>
                    <div class="mb-3">
                        <label for="maxCakes" class="form-label">Max Cakes</label>
                        <input type="number" class="form-control" id="maxCakes" name="max_cakes" required>
                    </div>
                    <div class="mb-3">
                        <label for="giftBoxImage" class="form-label">Gift Box Image</label>
                        <input type="file" class="form-control" id="giftBoxImage" name="giftbox_image" accept="image/*" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Gift Box</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteGiftBoxModal" tabindex="-1" aria-labelledby="deleteGiftBoxModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteGiftBoxModalLabel">Delete Gift Box</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this gift box?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="giftbox/delete_box.php" method="POST">
                    <input type="hidden" id="giftBoxIdToDelete" name="giftbox_id">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<!-- Edit GiftBox Modal -->
<div class="modal fade" id="editGiftBoxModal" tabindex="-1" aria-labelledby="editGiftBoxModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editGiftBoxForm" method="POST" action="giftbox/modify_    box.php" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="editGiftBoxModalLabel">Edit Gift Box</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="giftbox_id" id="editGiftBoxId">
                    
                    <div class="mb-3">
                        <label for="editGiftBoxName" class="form-label">Name</label>
                        <input type="text" class="form-control" id="editGiftBoxName" name="giftbox_name">
                    </div>
                    <div class="mb-3">
                        <label for="editGiftBoxCategory" class="form-label">Category</label>
                        <select class="form-select" id="editGiftBoxCategory" name="giftbox_category_id">
                            <option value="" disabled>Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['Id'] ?>"><?= htmlspecialchars($category['Name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editGiftBoxDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editGiftBoxDescription" name="giftbox_description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editGiftBoxPrice" class="form-label">Price</label>
                        <input type="number" step="0.01" class="form-control" id="editGiftBoxPrice" name="giftbox_price">
                    </div>
                    <div class="mb-3">
                        <label for="editGiftBoxDiscount" class="form-label">Discount Price</label>
                        <input type="number" step="0.01" class="form-control" id="editGiftBoxDiscount" name="giftbox_discount">
                    </div>
                    <div class="mb-3">
                        <label for="editGiftBoxStock" class="form-label">Stock</label>
                        <input type="number" class="form-control" id="editGiftBoxStock" name="giftbox_stock">
                    </div>
                    <div class="mb-3">
                        <label for="editGiftBoxImage" class="form-label">Gift Box Image</label>
                        <input type="file" class="form-control" id="editGiftBoxImage" name="giftbox_image" accept="image/*">
                        <small class="form-text text-muted">Leave empty to keep current image</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
