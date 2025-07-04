<?php
require_once 'auth.php';
requireEmployeeLogin([ROLE_ADMIN, ROLE_COOK]);

$employeeId = $_SESSION['employeeId'] ?? null;
include 'includes/header.php';


include '../configs/db.php';


$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$stmt = $conn->prepare("
    SELECT e.*, s.StatusName AS LatestStatus, c.Name AS CategoryName 
    FROM Cakes e
    LEFT JOIN (
        SELECT es.CakeId, MAX(es.Id) AS LatestStatusId
        FROM CakeStatus es
        GROUP BY es.CakeId
    ) latest_es ON e.Id = latest_es.CakeId
    LEFT JOIN CakeStatus es ON latest_es.LatestStatusId = es.Id
    LEFT JOIN Status s ON es.StatusId = s.Id
    LEFT JOIN Category c ON e.CategoryId = c.Id
    ORDER BY e.Id DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$cakes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalStmt = $conn->query("SELECT COUNT(*) FROM Cakes");
$totalCakes = $totalStmt->fetchColumn();
$totalPages = ceil($totalCakes / $limit);

$stmt2 = $conn->prepare("SELECT * FROM Status WHERE StatusName IN ('ACTIVE', 'INACTIVE')");
$stmt2->execute();
$statuses = $stmt2->fetchAll(PDO::FETCH_ASSOC);

$stmt3 = $conn->prepare("SELECT * FROM Category");
$stmt3->execute();
$categories = $stmt3->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid" style="height: 90vh;">
    <h3 class="text-dark mb-4">Cakes</h3>
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <p class="text-secondary m-0 fw-bold">Cake List</p>
            <?php if (isEmployeeInRole(ROLE_ADMIN)): ?>
                <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#addCakeModal">Add Cake</button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="table-responsive table mt-2" id="dataTable" role="grid" aria-describedby="dataTable_info">
                <table class="table my-0 table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Discount Price</th>
                            <th>Stock</th>
                            <th>Image</th>
                            <th>Latest Status</th>
                            <th>Date Created</th>
                            <?php if (isEmployeeInRole(ROLE_ADMIN)): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cakes as $cake): ?>
                            <tr>
                                <td><?= htmlspecialchars($cake['Id']) ?></td>
                                <td><?= htmlspecialchars($cake['Name']) ?></td>
                                <td><?= htmlspecialchars($cake['CategoryName']) ?></td>
                                <td><?= htmlspecialchars($cake['Description']) ?></td>
                                <td><?= htmlspecialchars($cake['Price']) ?></td>
                                <td><?= htmlspecialchars($cake['DiscountPrice']) ?></td>
                                <td><?= htmlspecialchars($cake['StockCount']) ?></td>
                                <td>
                                    <img src="../assets/uploads/cakes/<?= htmlspecialchars($cake['ImagePath']) ?>" alt="<?= htmlspecialchars($cake['Name']) ?>" style="width: 100px; height: auto;">
                                </td>
                                <td><?= htmlspecialchars($cake['LatestStatus']) ?: 'No Status' ?></td>
                                <td><?= htmlspecialchars($cake['DateCreated']) ?></td>
                                <?php if (isEmployeeInRole(ROLE_ADMIN)): ?>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <button class="btn btn-secondary btn-sm edit-cake-btn"
                                                data-id="<?= $cake['Id'] ?>"
                                                data-name="<?= $cake['Name'] ?>"
                                                data-category-id="<?= $cake['CategoryId'] ?>"
                                                data-description="<?= $cake['Description'] ?>"
                                                data-price="<?= $cake['Price'] ?>"
                                                data-discount="<?= $cake['DiscountPrice'] ?>"
                                                data-stock="<?= $cake['StockCount'] ?>">
                                                Edit
                                            </button>

                                            <button class="btn btn-dark btn-sm btn-del"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteCakeModal"
                                                data-id="<?= $cake['Id'] ?>">
                                                Delete
                                            </button>

                                            <form method="POST" action="status/add_cakeStatus.php" style="margin: 0;">
                                                <input type="hidden" name="cake_id" value="<?= $cake['Id'] ?>">
                                                <input type="hidden" name="employee_id" value="<?= $employeeId ?>">
                                                <select name="status_id" class="form-select form-select-sm"
                                                    style="width: 140px; background-color: #f8f9fa; color: #333; border: 1px solid #ccc;"
                                                    onchange="this.form.submit()">
                                                    <option value="" disabled selected>Change Status</option>
                                                    <?php foreach ($statuses as $status): ?>
                                                        <option value="<?= $status['Id'] ?>"><?= htmlspecialchars($status['StatusName']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </form>
                                        </div>
                                    </td>


                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <nav>
                <ul class="pagination justify-content-center mt-4">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>

        </div>
    </div>
</div>

<div class="modal fade" id="addCakeModal" tabindex="-1" aria-labelledby="addCakeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header ">
                <h5 class="modal-title" id="addCakeModalLabel">Add Cake</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="cake/add_cake.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="employee_id" value="<?= $employeeId ?>">
                    <div class="mb-3">
                        <label for="cakeName" class="form-label">Cake Name</label>
                        <input type="text" class="form-control" id="cakeName" name="cake_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="cakeCategory" class="form-label">Category</label>
                        <select class="form-select" id="cakeCategory" name="cake_category_id" required>
                            <option value="" disabled selected>Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['Id'] ?>"><?= htmlspecialchars($category['Name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="cakeDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="cakeDescription" name="cake_description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="cakePrice" class="form-label">Price</label>
                        <input type="number" step="0.01" class="form-control" id="cakePrice" name="cake_price" required>
                    </div>
                    <div class="mb-3">
                        <label for="cakeDiscountPrice" class="form-label">Discount Price</label>
                        <input type="number" step="0.01" class="form-control" id="cakeDiscountPrice" name="cake_discount">
                    </div>
                    <div class="mb-3">
                        <label for="cakeStock" class="form-label">Stock</label>
                        <input type="number" class="form-control" id="cakeStock" name="cake_stock" required>
                    </div>
                    <div class="mb-3">
                        <label for="cakeImage" class="form-label">Cake Image</label>
                        <input type="file" class="form-control" id="cakeImage" name="cake_image" accept="image/*" required>
                    </div>
                    <button type="submit" class="btn btn-secondary">Add Cake</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteCakeModal" tabindex="-1" aria-labelledby="deleteCakeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCakeModalLabel">Delete Cake</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this cake?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="cake/delete_cake.php" method="POST">
                    <input type="hidden" id="cakeIdToDelete" name="cake_id">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<div class="modal fade" id="editCakeModal" tabindex="-1" aria-labelledby="editCakeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editCakeForm" method="POST" action="cake/modify_cake.php" enctype="multipart/form-data">
                <input type="hidden" name="employee_id" value="<?= $employeeId ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCakeModalLabel">Edit Cake</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="cake_id" id="editCakeId">

                    <div class="mb-3">
                        <label for="editCakeName" class="form-label">Name</label>
                        <input type="text" class="form-control" id="editCakeName" name="cake_name">
                    </div>

                    <div class="mb-3">
                        <label for="editCakeCategory" class="form-label">Category</label>
                        <select class="form-select" id="editCakeCategory" name="cake_category_id">
                            <option value="" disabled>Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['Id'] ?>"><?= htmlspecialchars($category['Name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="editCakeDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editCakeDescription" name="cake_description" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="editCakePrice" class="form-label">Price</label>
                        <input type="number" step="0.01" class="form-control" id="editCakePrice" name="cake_price">
                    </div>

                    <div class="mb-3">
                        <label for="editCakeDiscount" class="form-label">Discount Price</label>
                        <input type="number" step="0.01" class="form-control" id="editCakeDiscount" name="cake_discount">
                    </div>

                    <div class="mb-3">
                        <label for="editCakeStock" class="form-label">Stock</label>
                        <input type="number" class="form-control" id="editCakeStock" name="cake_stock">
                    </div>

                    <div class="mb-3">
                        <label for="editCakeImage" class="form-label">Cake Image</label>
                        <input type="file" class="form-control" id="editCakeImage" name="cake_image" accept="image/*">
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

<div class="modal fade" id="deleteCakeModal" tabindex="-1" aria-labelledby="deleteCakeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCakeModalLabel">Delete Cake</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this cake?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="cake/delete_cake.php" method="POST">
                    <input type="hidden" id="cakeIdToDelete" name="cake_id">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.querySelectorAll('.btn-del').forEach(function(button) {
        button.addEventListener('click', function() {
            var cakeId = this.getAttribute('data-id');
            document.getElementById('cakeIdToDelete').value = cakeId;
        });
    });

    document.querySelectorAll('.edit-cake-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const categoryId = this.getAttribute('data-category-id');
            const description = this.getAttribute('data-description');
            const price = this.getAttribute('data-price');
            const discount = this.getAttribute('data-discount');
            const stock = this.getAttribute('data-stock');

            document.getElementById('editCakeId').value = id;
            document.getElementById('editCakeName').value = name;
            document.getElementById('editCakeCategory').value = categoryId;
            document.getElementById('editCakeDescription').value = description;
            document.getElementById('editCakePrice').value = price;
            document.getElementById('editCakeDiscount').value = discount;
            document.getElementById('editCakeStock').value = stock;

            const modal = new bootstrap.Modal(document.getElementById('editCakeModal'));
            modal.show();
        });
    });
</script>