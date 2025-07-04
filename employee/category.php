<?php
require_once 'auth.php';
requireEmployeeLogin([ROLE_ADMIN]);
include 'includes/header.php';

include '../configs/db.php';

$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$stmt = $conn->prepare("SELECT * FROM Category ORDER BY Id DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$cake_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalStmt = $conn->query("SELECT COUNT(*) FROM Category");
$totalCategories = $totalStmt->fetchColumn();
$totalPages = ceil($totalCategories / $limit);
?>

<div class="container-fluid" style="height: 90vh;">
    <h3 class="text-dark mb-4">Cake Categories</h3>
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <p class="text-secondary m-0 fw-bold">Cake Category List</p>
            <?php if (isEmployeeInRole(ROLE_ADMIN)): ?>
                <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                    Add Category
                </button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="table-responsive table mt-2">
                <table class="table my-0" id="categoryTable">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Category Name</th>
                            <th>Date Created</th>
                            <?php if (isEmployeeInRole(ROLE_ADMIN)): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cake_categories as $category): ?>
                            <tr>
                                <td><?= htmlspecialchars($category['Id']) ?></td>
                                <td><?= htmlspecialchars($category['Name']) ?></td>
                                <td><?= htmlspecialchars($category['DateCreated']) ?></td>
                                <?php if (isEmployeeInRole(ROLE_ADMIN)): ?>
                                    <td>
                                        <button class="btn btn-danger btn-sm btn-del" data-bs-toggle="modal" data-bs-target="#deleteCategoryModal" data-id="<?= $category['Id'] ?>">Delete</button>
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

<script>
    document.getElementById('searchInput').addEventListener('keyup', function() {
        let value = this.value.toLowerCase();
        let rows = document.querySelectorAll('#categoryTable tbody tr');
        rows.forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(value) ? '' : 'none';
        });
    });
</script>


<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCategoryModalLabel">Add a new Cake Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="category/add_category.php" method="POST">
                    <div class="mb-3">
                        <label for="categoryName" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="categoryName" name="category_name" required>
                    </div>
                    <button type="submit" class="btn btn-secondary">Add new Category</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modifyCategoryModal" tabindex="-1" aria-labelledby="modifyCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modifyCategoryModalLabel">Modify Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="category/modify_category.php" method="POST">
                    <div class="mb-3">
                        <label for="modifyCategoryName" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="modifyCategoryName" name="category_name" required>
                        <input type="hidden" name="category_id" id="modifyCategoryId">
                    </div>
                    <button type="submit" class="btn btn-secondary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-labelledby="deleteCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCategoryModalLabel">Delete Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this category?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="category/delete_category.php" method="POST">
                    <input type="hidden" id="categoryIdToDelete" name="category_id">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    var deleteButtons = document.querySelectorAll('.btn-del');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            var categoryId = this.getAttribute('data-id');
            document.getElementById('categoryIdToDelete').value = categoryId;
        });
    });
</script>

<script>
    document.getElementById('searchInput').addEventListener('input', function() {
        var query = this.value.toLowerCase();
        var rows = document.querySelectorAll('#dataTable tbody tr');
        rows.forEach(function(row) {
            var categoryName = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            row.style.display = categoryName.includes(query) ? '' : 'none';
        });
    });
</script>