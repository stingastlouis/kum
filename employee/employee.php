<?php
require_once 'auth.php';
requireEmployeeLogin([ROLE_ADMIN]);

include 'includes/header.php';
include '../configs/db.php';

$successMessage = $_GET["success"] ?? null;

$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$countStmt = $conn->query("SELECT COUNT(*) FROM Employee");
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

$query = "
    SELECT 
        s.*, 
        r.Name AS role_name,
        st.StatusName AS latest_status
    FROM Employee s
    LEFT JOIN Roles r ON s.RoleId = r.Id
    LEFT JOIN (
        SELECT ss.EmployeeId, MAX(ss.Id) AS latest_status_id
        FROM EmployeeStatus ss
        GROUP BY ss.EmployeeId
    ) latest_ss ON s.Id = latest_ss.EmployeeId
    LEFT JOIN EmployeeStatus ss ON latest_ss.latest_status_id = ss.Id
    LEFT JOIN Status st ON ss.StatusId = st.Id
    ORDER BY s.Id DESC
    LIMIT :limit OFFSET :offset
";

$stmt = $conn->prepare($query);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$employeeMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$roleStmt = $conn->prepare("SELECT * FROM Roles");
$roleStmt->execute();
$roles = $roleStmt->fetchAll(PDO::FETCH_ASSOC);

$statusStmt = $conn->prepare("SELECT * FROM Status WHERE StatusName IN ('ACTIVE','INACTIVE');");
$statusStmt->execute();
$statuses = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h3 class="text-dark mb-4">Employees</h3>

    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <p class="text-secondary m-0 fw-bold">Employee List</p>
            <?php if (isEmployeeInRole(ROLE_ADMIN)): ?>
                <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                    Add Employee
                </button>
            <?php endif; ?>
        </div>

        <div class="card-body">
            <div class="table-responsive mt-2">
                <table class="table table-striped" id="dataTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Latest Status</th>
                            <th>Date Created</th>
                            <?php if (isEmployeeInRole(ROLE_ADMIN)): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employeeMembers as $employee): ?>
                            <tr>
                                <td><?= htmlspecialchars($employee['Id']) ?></td>
                                <td><?= htmlspecialchars($employee['Fullname']) ?></td>
                                <td><?= htmlspecialchars($employee['Email']) ?></td>
                                <td><?= htmlspecialchars($employee['Phone']) ?></td>
                                <td><?= htmlspecialchars($employee['role_name']) ?></td>
                                <td><?= htmlspecialchars($employee['latest_status'] ?? 'No Status') ?></td>
                                <td><?= htmlspecialchars($employee['DateCreated']) ?></td>
                                <?php if (isEmployeeInRole(ROLE_ADMIN)): ?>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">


                                            <button class="btn btn-secondary btn-sm edit-employee-btn"
                                                data-id="<?= $employee['Id'] ?>"
                                                data-fullname="<?= htmlspecialchars($employee['Fullname']) ?>"
                                                data-email="<?= htmlspecialchars($employee['Email']) ?>"
                                                data-phone="<?= htmlspecialchars($employee['Phone']) ?>"
                                                data-role-id="<?= $employee['RoleId'] ?>">
                                                Edit
                                            </button>


                                            <button class="btn btn-secondary btn-sm reset-password-btn"
                                                data-id="<?= $employee['Id'] ?>"
                                                data-bs-toggle="modal"
                                                data-bs-target="#resetPasswordModal">
                                                Reset Password
                                            </button>

                                            <?php if ($_SESSION["employeeId"] != $employee['Id']): ?>
                                                <button class="btn btn-dark btn-sm delete-employee-btn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#deleteEmployeeModal"
                                                    data-id="<?= $employee['Id'] ?>">
                                                    Delete
                                                </button>
                                            <?php endif; ?>

                                            <form method="POST" action="status/add_employeeStatus.php" style="margin: 0">
                                                <input type="hidden" name="employee_id" value="<?= $employee['Id'] ?>">
                                                <select name="status_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 150px;">
                                                    <option value="" disabled selected>Change Status</option>
                                                    <?php foreach ($statuses as $status): ?>
                                                        <option value="<?= $status['Id'] ?>">
                                                            <?= htmlspecialchars($status['StatusName']) ?>
                                                        </option>
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

                <nav>
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?><?= $successMessage ? '&success=' . urlencode($successMessage) : '' ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= $successMessage ? '&success=' . urlencode($successMessage) : '' ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?><?= $successMessage ? '&success=' . urlencode($successMessage) : '' ?>" aria-label="Next">
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



<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-labelledby="addEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEmployeeModalLabel">Add Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="employee/add_employee.php" method="POST">
                    <div class="mb-3">
                        <label for="employeeFullname" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="employeeFullname" name="employee_fullname" required autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label for="employeeEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="employeeEmail" name="employee_email" required>
                    </div>
                    <div class="mb-3">
                        <label for="employeePhone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="employeePhone" name="employee_phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="employeeRole" class="form-label">Role</label>
                        <select class="form-select" id="employeeRole" name="employee_role_id" required>
                            <option value="" disabled selected>Select Role</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= htmlspecialchars($role['Id']) ?>"><?= htmlspecialchars($role['Name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="employeePassword" class="form-label">Initial Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="employeePassword" name="employee_password" required>
                            <button class="btn btn-outline-secondary" type="button" id="generatePasswordBtn">Generate</button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-secondary w-100">Add Employee</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-labelledby="editEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editEmployeeForm" method="POST" action="employee/modify.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="editEmployeeModalLabel">Edit Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="employee_id" id="editEmployeeId">
                    <div class="mb-3">
                        <label for="editEmployeeFullname" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="editEmployeeFullname" name="employee_fullname" required>
                    </div>
                    <div class="mb-3">
                        <label for="editEmployeeEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="editEmployeeEmail" name="employee_email" required>
                    </div>
                    <div class="mb-3">
                        <label for="editEmployeePhone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="editEmployeePhone" name="employee_phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="editEmployeeRole" class="form-label">Role</label>
                        <select class="form-select" id="editEmployeeRole" name="employee_role_id" required>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= htmlspecialchars($role['Id']) ?>"><?= htmlspecialchars($role['Name']) ?></option>
                            <?php endforeach; ?>
                        </select>
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

<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="resetPasswordModalLabel">Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="employee/reset_password.php" method="POST">
                    <input type="hidden" name="employee_id" id="resetPasswordEmployeeId">
                    <div class="mb-3">
                        <label for="employeePassword" class="form-label">Initial Password</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="employeeNewPassword" name="employee_password" required>
                            <button class="btn btn-outline-secondary" type="button" id="generateNewPasswordBtn">
                                Generate Password
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-secondary">Reset Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteEmployeeModal" tabindex="-1" aria-labelledby="deleteEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteEmployeeModalLabel">Delete Employee Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this employee member?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="employee/delete_employee.php" method="POST">
                    <input type="hidden" id="employeeIdToDelete" name="employee_id">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    document.querySelectorAll('.delete-employee-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            var employeeId = this.getAttribute('data-id');
            document.getElementById('employeeIdToDelete').value = employeeId;
        });
    });

    document.querySelectorAll('.edit-employee-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const fullname = this.getAttribute('data-fullname');
            const email = this.getAttribute('data-email');
            const phone = this.getAttribute('data-phone');
            const roleId = this.getAttribute('data-role-id');

            document.getElementById('editEmployeeId').value = id;
            document.getElementById('editEmployeeFullname').value = fullname;
            document.getElementById('editEmployeeEmail').value = email;
            document.getElementById('editEmployeePhone').value = phone;
            document.getElementById('editEmployeeRole').value = roleId;

            const modal = new bootstrap.Modal(document.getElementById('editEmployeeModal'));
            modal.show();
        });
    });

    document.querySelectorAll('.reset-password-btn').forEach(button => {
        button.addEventListener('click', function() {
            const employeeId = this.getAttribute('data-id');
            document.getElementById('resetPasswordEmployeeId').value = employeeId;
        });
    });

    document.querySelector('#resetPasswordModal form').addEventListener('submit', function(e) {
        const password = document.getElementById('newPassword').value;
        const confirm = document.getElementById('confirmPassword').value;

        if (password !== confirm) {
            e.preventDefault();
            alert('Passwords do not match!');
        }
    });
</script>

<script>
    function generatePassword() {
        const uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        const lowercase = 'abcdefghijkmnpqrstuvwxyz';
        const numbers = '23456789';
        const symbols = '!@#$%^&*';
        let password = '';

        password += uppercase.charAt(Math.floor(Math.random() * uppercase.length));
        password += lowercase.charAt(Math.floor(Math.random() * lowercase.length));
        password += numbers.charAt(Math.floor(Math.random() * numbers.length));
        password += symbols.charAt(Math.floor(Math.random() * symbols.length));

        const allChars = uppercase + lowercase + numbers + symbols;
        for (let i = password.length; i < 12; i++) {
            password += allChars.charAt(Math.floor(Math.random() * allChars.length));
        }

        password = password.split('').sort(() => Math.random() - 0.5).join('');

        return password;
    }

    document.getElementById('generatePasswordBtn').addEventListener('click', function() {
        const passwordField = document.getElementById('employeePassword');
        passwordField.value = generatePassword();
        passwordField.type = 'text';

        setTimeout(() => {
            passwordField.type = 'password';
        }, 5000);
    });

    document.getElementById('generateNewPasswordBtn').addEventListener('click', function() {
        const passwordField = document.getElementById('employeeNewPassword');
        passwordField.value = generatePassword();
        passwordField.type = 'text';

        setTimeout(() => {
            passwordField.type = 'password';
        }, 5000);
    });

    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function() {
            const forms = this.getElementsByTagName('form');
            for (let form of forms) {
                form.reset();
            }

            const passwordFields = this.querySelectorAll('input[type="text"][id$="Password"]');
            passwordFields.forEach(field => {
                field.type = 'password';
            });
        });
    });
</script>