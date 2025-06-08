<?php
require_once 'auth.php';
requireEmployeeLogin([ROLE_ADMIN]);

include 'includes/header.php';
include '../configs/db.php';

$success = isset($_GET["success"]) ? $_GET["success"] : null;

$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$totalStmt = $conn->query("SELECT COUNT(*) FROM Customer");
$totalRows = $totalStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

$stmt = $conn->prepare("
    SELECT c.*, 
           s.StatusName AS LatestStatus
    FROM Customer c
    LEFT JOIN (
        SELECT cs.CustomerId, MAX(cs.Id) AS LatestStatusId
        FROM CustomerStatus cs
        GROUP BY cs.CustomerId
    ) latest_cs ON c.Id = latest_cs.CustomerId
    LEFT JOIN CustomerStatus cs ON latest_cs.LatestStatusId = cs.Id
    LEFT JOIN Status s ON cs.StatusId = s.Id
    ORDER BY c.Id DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$customerMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt3 = $conn->prepare("SELECT * FROM Status WHERE StatusName IN ('ACTIVE', 'INACTIVE')");
$stmt3->execute();
$statuses = $stmt3->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h3 class="text-dark mb-4">Customer Management</h3>
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <p class="text-secondary m-0 fw-bold">Customer List</p>
        </div>
        <div class="card-body">
            <div class="table-responsive table mt-2" id="dataTable" role="grid" aria-describedby="dataTable_info">
                <table class="table my-0" id="dataTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Latest Status</th>
                            <th>Date Created</th>
                            <?php if (isEmployeeInRole(ROLE_ADMIN)): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customerMembers as $customer): ?>
                            <tr>
                                <td><?= htmlspecialchars($customer['Id']) ?></td>
                                <td><?= htmlspecialchars($customer['Fullname']) ?></td>
                                <td><?= htmlspecialchars($customer['Email']) ?></td>
                                <td><?= htmlspecialchars($customer['Phone']) ?></td>
                                <td><?= htmlspecialchars($customer['Address']) ?></td>
                                <td><?= $customer['LatestStatus'] !== null ? htmlspecialchars($customer['LatestStatus']) : 'No Status' ?></td>
                                <td><?= htmlspecialchars($customer['DateCreated']) ?></td>
                                <?php if (isEmployeeInRole(ROLE_ADMIN)): ?>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">

                                            <button class="btn btn-secondary btn-sm edit-customer-btn"
                                                data-id="<?= $customer['Id'] ?>"
                                                data-fullname="<?= $customer['Fullname'] ?>"
                                                data-email="<?= $customer['Email'] ?>"
                                                data-phone="<?= $customer['Phone'] ?>"
                                                data-address="<?= $customer['Address'] ?>">
                                                Edit
                                            </button>

                                            <button class="btn btn-secondary btn-sm reset-password-btn"
                                                data-id="<?= $customer['Id'] ?>"
                                                data-bs-toggle="modal"
                                                data-bs-target="#resetPasswordModal">
                                                Reset Password
                                            </button>

                                            <button class="btn btn-dark btn-sm btn-del"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteCustomerModal"
                                                data-id="<?= $customer['Id'] ?>">
                                                Delete
                                            </button>

                                            <form method="POST" action="status/add_customerStatus.php" style="margin: 0;">
                                                <input type="hidden" name="customer_id" value="<?= $customer['Id'] ?>">
                                                <select name="status_id" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width: 150px;">
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

                <nav>
                    <ul class="pagination justify-content-center mt-3">
                        <?php if ($page > 1): ?>
                            <li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a></li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?>">Next</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>

            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editCustomerForm" method="POST" action="customer/modify_customer.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCustomerModalLabel">Edit Customer Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="customer_id" id="editCustomerId">

                    <div class="mb-3">
                        <label for="editCustomerFullname" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="editCustomerFullname" name="customer_fullname" required>
                    </div>
                    <div class="mb-3">
                        <label for="editCustomerEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="editCustomerEmail" name="customer_email" required>
                    </div>
                    <div class="mb-3">
                        <label for="editCustomerPhone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="editCustomerPhone" name="customer_phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="editCustomerAddress" class="form-label">Address</label>
                        <input type="address" class="form-control" id="editCustomerAddress" name="customer_address" required>
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
                <form action="customer/reset_password.php" method="POST">
                    <input type="hidden" name="customer_id" id="resetPasswordCustomerId">
                    <div class="mb-3">
                        <label for="customerPassword" class="form-label">Initial Password</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="customerNewPassword" name="customer_password" required>
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

<div class="modal fade" id="deleteCustomerModal" tabindex="-1" aria-labelledby="deleteCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCustomerModalLabel">Delete Customer Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this customer member?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="customer/delete_customer.php" method="POST">
                    <input type="hidden" id="customerIdToDelete" name="customer_id">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    // Set customer ID when clicking delete button
    document.querySelectorAll('.btn-del').forEach(function(button) {
        button.addEventListener('click', function() {
            var customerId = this.getAttribute('data-id');
            document.getElementById('customerIdToDelete').value = customerId;
        });
    });

    // Fill edit form and show modal
    document.querySelectorAll('.edit-customer-btn').forEach(button => {
        button.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const fullname = this.getAttribute('data-fullname');
            const email = this.getAttribute('data-email');
            const phone = this.getAttribute('data-phone');
            const address = this.getAttribute('data-address');

            document.getElementById('editCustomerId').value = id;
            document.getElementById('editCustomerFullname').value = fullname;
            document.getElementById('editCustomerEmail').value = email;
            document.getElementById('editCustomerPhone').value = phone;
            document.getElementById('editCustomerAddress').value = address;

            var modal = new bootstrap.Modal(document.getElementById('editCustomerModal'));
            modal.show();
        });
    });

    // Set customer ID in reset password modal
    document.querySelectorAll('.reset-password-btn').forEach(button => {
        button.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            document.getElementById('resetPasswordCustomerId').value = id;
        });
    });

    // Generate random password for reset
    document.getElementById('generateNewPasswordBtn').addEventListener('click', function () {
        const passwordField = document.getElementById('customerNewPassword');
        const newPassword = generateRandomPassword(10);
        passwordField.value = newPassword;
    });

    // Password generator function
    function generateRandomPassword(length) {
        const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+';
        let password = '';
        for (let i = 0; i < length; i++) {
            password += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return password;
    }
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
        const passwordField = document.getElementById('customerPassword');
        passwordField.value = generatePassword();
        passwordField.type = 'text';

        setTimeout(() => {
            passwordField.type = 'password';
        }, 5000);
    });

    document.getElementById('generateNewPasswordBtn').addEventListener('click', function() {
        const passwordField = document.getElementById('customerNewPassword');
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