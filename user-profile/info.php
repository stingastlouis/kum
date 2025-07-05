<div class="tab-pane fade show active" id="user-info" role="tabpanel">
    <div class="card p-4">
        <?php
        include './configs/db.php';
        if (isset($_SESSION['customerId'])) {
            try {
                $stmt = $conn->prepare("SELECT * FROM Customer WHERE Id = :customerId");
                $stmt->bindParam(':customerId', $_SESSION['customerId'], PDO::PARAM_INT);
                $stmt->execute();
                $customer = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($customer) {
                    echo "<h3>User Information</h3>";
                    echo "<p><strong>Name:</strong> " . htmlspecialchars($customer['Fullname']) . "</p>";
                    echo "<p><strong>Email:</strong> " . htmlspecialchars($customer['Email']) . "</p>";
                    echo "<p><strong>Phone:</strong> " . htmlspecialchars($customer['Phone']) . "</p>";
                    echo "<p><strong>Address:</strong> " . nl2br(htmlspecialchars($customer['Address'])) . "</p>";
                    echo "<p><strong>Account Created On:</strong> " . date("F j, Y", strtotime($customer['DateCreated'])) . "</p>";

                    echo '<button class="btn btn-warning mt-3" data-bs-toggle="modal" data-bs-target="#resetPasswordModal">Reset Password</button>';
                } else {
                    echo "<p>Customer not found or session expired.</p>";
                }
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
        } else {
            echo "<p>Please log in to view your profile.</p>";
        }
        ?>
    </div>
</div>

<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-labelledby="resetPasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="user-profile/reset-password.php">
        <div class="modal-header">
          <h5 class="modal-title" id="resetPasswordModalLabel">Reset Password</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="customerId" value="<?php echo $_SESSION['customerId']; ?>">
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

<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> -->
