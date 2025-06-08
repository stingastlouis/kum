<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<div class="tab-pane fade" id="queries" role="tabpanel">
    <div class="card p-4">
        <h3>Queries</h3>
        <p>If you have any questions or queries, please feel free to ask below:</p>

        <form action="user-profile/addMessage.php" method="POST">
            <div class="mb-3">
                <label for="subject" class="form-label">Subject</label>
                <input type="text" class="form-control" id="subject" name="subject" placeholder="Enter the subject" required>
            </div>
            <div class="mb-3">
                <label for="query" class="form-label">Your Query</label>
                <textarea class="form-control" id="query" name="query" rows="4" placeholder="Describe your query..." required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit Query</button>
        </form>


        <h4 class="mt-4">Previous Queries</h4>
        <ul class="list-group">
            <?php
            include 'configs/db.php';
            $customerId = $_SESSION['customerId'];
            if ($customerId) {
                $stmt = $conn->prepare("SELECT `Subject`, `Content`, `DateCreated` FROM Messages WHERE SenderType = 'customer' AND SenderId = :customerId ORDER BY DateCreated DESC LIMIT 3");
                $stmt->execute([':customerId' => $customerId]);
                $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($messages) {
                    foreach ($messages as $row) {
                        echo "<li class='list-group-item'>";
                        echo "<strong>" . htmlspecialchars($row['Subject']) . "</strong><br>";
                        echo nl2br(htmlspecialchars($row['Content'])) . "<br>";
                        echo "<small class='text-muted'>" . $row['DateCreated'] . "</small>";
                        echo "</li>";
                    }
                } else {
                    echo "<li class='list-group-item text-muted'>No previous queries found.</li>";
                }
            } else {
                echo "<li class='list-group-item text-muted'>You need to be logged in to view your queries.</li>";
            }
            ?>
        </ul>
    </div>
</div>