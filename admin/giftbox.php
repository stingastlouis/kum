<?php include 'includes/header.php'; ?>

<?php
include '../configs/db.php';

$success = $_GET["success"] ?? null;
$stmt = $conn->prepare("
        SELECT e.*, s.StatusName AS LatestStatus, c.Name as CategoryName, e.CategoryId, e.ImagePath
    FROM GiftBox e
    LEFT JOIN (
        SELECT es.GiftBoxId, MAX(es.Id) AS LatestStatusId
        FROM GiftBoxStatus es
        GROUP BY es.GiftBoxId
    ) latest_es ON e.Id = latest_es.GiftBoxId
    LEFT JOIN GiftBoxStatus es ON latest_es.LatestStatusId = es.Id AND latest_es.GiftBoxId = es.GiftBoxId
    LEFT JOIN Status s ON es.StatusId = s.Id
    LEFT JOIN Category c ON e.CategoryId = c.Id;
");
$stmt->execute();
$giftbox = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt->execute();
$giftbox = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt2 = $conn->prepare("SELECT * FROM Status");
$stmt2->execute();
$statuses = $stmt2->fetchAll(PDO::FETCH_ASSOC);

$stmt3 = $conn->prepare("SELECT * FROM Category");
$stmt3->execute();
$categories = $stmt3->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h3 class="text-dark mb-4">Giftboxs</h3>
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <p class="text-secondary m-0 fw-bold">Giftbox List</p>
            <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#addGiftboxModal">Add Giftbox</button>
        </div>
        <div class="card-body">
            <div class="table-responsive table mt-2" id="dataTable" role="grid" aria-describedby="dataTable_info">
                <table class="table my-0">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Discount Price</th>
                            <th>Max Cake</th>
                            <th>Image</th>
                            <th>Latest Status</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($giftbox as $giftbox): ?>
                            <tr>
                                <td><?= htmlspecialchars($giftbox['Id']) ?></td>
                                <td><?= htmlspecialchars($giftbox['Name']) ?></td>
                                <td><?= htmlspecialchars($giftbox['CategoryName']) ?></td>
                                <td><?= htmlspecialchars($giftbox['Description']) ?></td>
                                <td><?= htmlspecialchars($giftbox['Price']) ?></td>
                                <td><?= htmlspecialchars($giftbox['MaxCakes']) ?></td>
                                <td>
                                    <img src="../assets/uploads/<?= htmlspecialchars($giftbox['ImagePath']) ?>" alt="<?= htmlspecialchars($giftbox['Name']) ?>" style="width: 100px; height: auto;">
                                </td>
                                <td><?= htmlspecialchars($giftbox['LatestStatus']) ?: 'No Status' ?></td>
                                <td><?= htmlspecialchars($giftbox['DateCreated']) ?></td>
                                <td>
                                    <button class='btn btn-warning btn-sm edit-giftbox-btn' 
                                        data-id='<?= $giftbox['Id'] ?>' 
                                        data-name='<?= $giftbox['Name'] ?>' 
                                        data-category-id='<?= $giftbox['CategoryId'] ?>' 
                                        data-description='<?= $giftbox['Description'] ?>' 
                                        data-price='<?= $giftbox['Price'] ?>' 
                                        data-max='<?= $giftbox['MaxCakes'] ?>'>Edit</button>
                                    <button class="btn btn-danger btn-sm btn-del" data-bs-toggle="modal" data-bs-target="#deleteGiftboxModal" data-id="<?= $giftbox['Id'] ?>">Delete</button>
                                    <form method="POST" action="status/add_giftboxStatus.php" style="display: inline;">
                                        <input type="hidden" name="giftbox_id" value="<?= $giftbox['Id'] ?>">
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

<div class="modal fade" id="addGiftboxModal" tabindex="-1" aria-labelledby="addGiftboxModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addGiftboxModalLabel">Add Giftbox</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="giftbox/add_box.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="giftboxName" class="form-label">Giftbox Name</label>
                        <input type="text" class="form-control" id="giftboxName" name="giftbox_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="giftboxCategory" class="form-label">Category</label>
                        <select class="form-select" id="giftboxCategory" name="giftbox_category_id" required>
                            <option value="" disabled selected>Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['Id'] ?>"><?= htmlspecialchars($category['Name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="giftboxDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="giftboxDescription" name="giftbox_description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="giftboxPrice" class="form-label">Price</label>
                        <input type="number" step="0.01" class="form-control" id="giftboxPrice" name="giftbox_price" required>
                    </div>
                    <div class="mb-3">
                        <label for="giftboxMax" class="form-label">Stock</label>
                        <input type="number" class="form-control" id="giftboxMax" name="max_giftBoxes" required>
                    </div>
                    <div class="mb-3">
                        <label for="giftboxImage" class="form-label">Giftbox Image</label>
                        <input type="file" class="form-control" id="giftboxImage" name="giftbox_image" accept="image/*" required>
                    </div>
                    <button type="submit" class="btn btn-secondary">Add Giftbox</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteGiftboxModal" tabindex="-1" aria-labelledby="deleteGiftboxModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteGiftboxModalLabel">Delete Giftbox</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this giftbox?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="giftbox/delete_box.php" method="POST">
                    <input type="hidden" id="giftboxIdToDelete" name="giftbox_id">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<div class="modal fade" id="editGiftboxModal" tabindex="-1" aria-labelledby="editGiftboxModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editGiftboxForm" method="POST" action="giftbox/modify_box.php" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="editGiftboxModalLabel">Edit Giftbox</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="giftbox_id" id="editGiftboxId">
                    
                    <div class="mb-3">
                        <label for="editGiftboxName" class="form-label">Name</label>
                        <input type="text" class="form-control" id="editGiftboxName" name="giftbox_name">
                    </div>

                    <div class="mb-3">
                        <label for="editGiftboxCategory" class="form-label">Category</label>
                        <select class="form-select" id="editGiftboxCategory" name="giftbox_category_id">
                            <option value="" disabled>Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['Id'] ?>"><?= htmlspecialchars($category['Name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="editGiftboxDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editGiftboxDescription" name="giftbox_description" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="editGiftboxPrice" class="form-label">Price</label>
                        <input type="number" step="0.01" class="form-control" id="editGiftboxPrice" name="giftbox_price">
                    </div>

                    <div class="mb-3">
                        <label for="editGiftboxDiscount" class="form-label">Discount Price</label>
                        <input type="number" step="0.01" class="form-control" id="editGiftboxDiscount" name="giftbox_discount">
                    </div>

                    <div class="mb-3">
                        <label for="editGiftboxMax" class="form-label">Stock</label>
                        <input type="number" class="form-control" id="editGiftboxMax" name="giftbox_stock">
                    </div>

                    <div class="mb-3">
                        <label for="editGiftboxImage" class="form-label">Giftbox Image</label>
                        <input type="file" class="form-control" id="editGiftboxImage" name="giftbox_image" accept="image/*">
                        <small class="form-text text-muted">Leave empty to keep current image</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-secondary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteGiftboxModal" tabindex="-1" aria-labelledby="deleteGiftboxModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteGiftboxModalLabel">Delete Giftbox</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this giftbox?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="giftbox/delete_giftbox.php" method="POST">
                    <input type="hidden" id="giftboxIdToDelete" name="giftbox_id">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.btn-del').forEach(function(button) {
        button.addEventListener('click', function() {
            var giftboxId = this.getAttribute('data-id');
            document.getElementById('giftboxIdToDelete').value = giftboxId;
        });
    });

    document.querySelectorAll('.edit-giftbox-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const categoryId = this.getAttribute('data-category-id');
            const description = this.getAttribute('data-description');
            const price = this.getAttribute('data-price');
            const discount = this.getAttribute('data-discount');
            const max = this.getAttribute('data-max');

            document.getElementById('editGiftboxId').value = id;
            document.getElementById('editGiftboxName').value = name;
            document.getElementById('editGiftboxCategory').value = categoryId;
            document.getElementById('editGiftboxDescription').value = description;
            document.getElementById('editGiftboxPrice').value = price;
            document.getElementById('editGiftboxDiscount').value = discount;
            document.getElementById('editGiftboxMax').value = max;

            const modal = new bootstrap.Modal(document.getElementById('editGiftboxModal'));
            modal.show();
        });
    });
</script>