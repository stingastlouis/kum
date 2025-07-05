<?php
require_once 'auth.php';
requireEmployeeLogin([ROLE_ADMIN, ROLE_COOK, ROLE_DELIVERY]);

$employeeId = $_SESSION['employeeId'] ?? null;
include 'includes/header.php';
include '../configs/db.php';
?>

<div class="container my-5">
    <div class="card p-4">
        <?php
        try {
            $stmt = $conn->prepare("SELECT e.*, r.Name AS RoleName 
                                    FROM Employee e 
                                    JOIN Roles r ON e.RoleId = r.Id 
                                    WHERE e.Id = :employeeId");
            $stmt->bindParam(':employeeId', $employeeId, PDO::PARAM_INT);
            $stmt->execute();
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($employee) {
                echo "<h3>Employee Information</h3>";
                echo "<p><strong>Name:</strong> " . htmlspecialchars($employee['Fullname']) . "</p>";
                echo "<p><strong>Email:</strong> " . htmlspecialchars($employee['Email']) . "</p>";
                echo "<p><strong>Phone:</strong> " . htmlspecialchars($employee['Phone']) . "</p>";
                echo "<p><strong>Role:</strong> " . htmlspecialchars($employee['RoleName']) . "</p>";
                echo "<p><strong>Account Created On:</strong> " . date("F j, Y", strtotime($employee['DateCreated'])) . "</p>";

                echo '<button class="btn btn-warning mt-3" data-bs-toggle="modal" data-bs-target="#resetPasswordModal">Reset Password</button>';
            } else {
                echo "<p>Employee not found.</p>";
            }
        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
        ?>
    </div>
</div>

<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="employee_reset_password.php">
        <div class="modal-header">
          <h5 class="modal-title" id="resetPasswordModalLabel">Reset Password</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="employeeId" value="<?php echo $_SESSION['employeeId']; ?>">
          <div class="mb-3">
            <label for="newPassword" class="form-label">New Password</label>
            <input type="password" class="form-control" id="newPassword" name="newPassword" required>
          </div>
          <div class="mb-3">
            <label for="confirmPassword" class="form-label">Confirm New Password</label>
            <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Change Password</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>