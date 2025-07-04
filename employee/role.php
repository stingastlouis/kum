<?php include 'includes/header.php'; ?>

<?php
include '../configs/db.php';

$success = isset($_GET["success"]) ? $_GET["success"] : null;


$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;


$totalStmt = $conn->prepare("SELECT COUNT(*) FROM Roles");
$totalStmt->execute();
$totalRows = $totalStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

$stmt = $conn->prepare("SELECT * FROM Roles ORDER BY Id DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid" style="height: 90vh;">
    <h3 class="text-dark mb-4">Roles</h3>
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <p class="text-secondary m-0 fw-bold">Role List</p>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped" id="dataTable">
                    <thead class="table-secondary">
                        <tr>
                            <th>Id</th>
                            <th>Role Name</th>
                            <th>Date Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $role): ?>
                            <tr>
                                <td><?= htmlspecialchars($role['Id']) ?></td>
                                <td><?= htmlspecialchars($role['Name']) ?></td>
                                <td><?= htmlspecialchars(date('d M Y, H:i', strtotime($role['DateCreated']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <nav>
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?><?= $success ? '&success=' . urlencode($success) : '' ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= $success ? '&success=' . urlencode($success) : '' ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?><?= $success ? '&success=' . urlencode($success) : '' ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>

            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="addRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addRoleModalLabel">Add Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="role/add_role.php" method="POST">
                    <div class="mb-3">
                        <label for="roleName" class="form-label">Role Name</label>
                        <input type="text" class="form-control" id="roleName" name="role_name" required>
                    </div>
                    <button type="submit" class="btn btn-secondary">Add Role</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="SuccessMessage" tabindex="-1" aria-labelledby="successMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="successMessageModalLabel">Message</h5>
            </div>
            <div class="modal-body">
                Created Successfully
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteRoleModal" tabindex="-1" aria-labelledby="deleteRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteRoleModalLabel">Delete Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this role?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="role/delete_role.php" method="POST">
                    <input type="hidden" id="roleIdToDelete" name="role_id">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    <?php if ($success): ?>
        const successModal = new bootstrap.Modal(document.getElementById('SuccessMessage'));
        successModal.show();

        setTimeout(() => successModal.hide(), 3000);
    <?php endif; ?>

    document.querySelectorAll('.btn-del').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('roleIdToDelete').value = this.getAttribute('data-id');
        });
    });

    document.getElementById('searchInput').addEventListener('input', function() {
        const query = this.value.toLowerCase();
        document.querySelectorAll('#dataTable tbody tr').forEach(row => {
            const roleName = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            row.style.display = roleName.includes(query) ? '' : 'none';
        });
    });
</script>