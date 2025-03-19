<?php include 'includes/header.php'; ?>

<?php
include '../configs/db.php';

$success = isset($_GET["success"]) ? $_GET["success"] : null;
$stmt = $conn->prepare("SELECT * FROM roles ORDER BY Id DESC");
$stmt->execute();
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h3 class="text-dark mb-4">Roles</h3>
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <p class="text-primary m-0 fw-bold">Role List</p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                Add Role
            </button>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="text-md-end dataTables_filter" id="dataTable_filter">
                        <label class="form-label">
                            <input type="search" class="form-control" aria-controls="dataTable" placeholder="Search roles" id="searchInput">
                        </label>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped" id="dataTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Id</th>
                            <th>Role Name</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $role): ?>
                            <tr>
                                <td><?= htmlspecialchars($role['Id']) ?></td>
                                <td><?= htmlspecialchars($role['Name']) ?></td>
                                <td><?= htmlspecialchars(date('d M Y, H:i', strtotime($role['DateCreated']))) ?></td>
                                <td>
                                    <button class="btn btn-danger btn-sm btn-del" data-bs-toggle="modal" data-bs-target="#deleteRoleModal" data-id="<?= $role['Id'] ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
                    <button type="submit" class="btn btn-primary">Add Role</button>
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
        button.addEventListener('click', function () {
            document.getElementById('roleIdToDelete').value = this.getAttribute('data-id');
        });
    });

    document.getElementById('searchInput').addEventListener('input', function () {
        const query = this.value.toLowerCase();
        document.querySelectorAll('#dataTable tbody tr').forEach(row => {
            const roleName = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            row.style.display = roleName.includes(query) ? '' : 'none';
        });
    });
</script>
