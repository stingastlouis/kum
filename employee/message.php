<?php
require_once 'auth.php';
requireEmployeeLogin([ROLE_ADMIN]);
include 'includes/header.php';
include '../configs/db.php';

$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$totalStmt = $conn->query("SELECT COUNT(*) FROM Messages");
$totalRows = $totalStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

$stmt = $conn->prepare("SELECT * FROM Messages ORDER BY Id DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid" >
    <h3 class="text-dark mb-4">Messages</h3>
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <p class="text-secondary m-0 fw-bold">Message List</p>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead class="table-secondary">
                        <tr>
                            <th>ID</th>
                            <th>Sender</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Read</th>
                            <th>Date</th>
                            <?php if (isEmployeeInRole(ROLE_ADMIN)): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $msg): ?>
                            <tr>
                                <td><?= htmlspecialchars($msg['Id']) ?></td>
                                <td><?= htmlspecialchars($msg['SenderType']) ?></td>
                                <td><?= htmlspecialchars($msg['GuestName'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($msg['GuestEmail'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($msg['Subject']) ?></td>
                                <td>
                                    <?= $msg['Read'] ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-warning text-dark">No</span>' ?>
                                </td>
                                <td><?= htmlspecialchars(date('d M Y, H:i', strtotime($msg['DateCreated'] ?? $msg['Date'] ?? 'now'))) ?></td>
                                <?php if (isEmployeeInRole(ROLE_ADMIN)): ?>
                                    <td>
                                        <?php if (!$msg['Read']): ?>
                                            <form action="message/index.php" method="POST" style="display:inline;">
                                                <input type="hidden" name="id" value="<?= $msg['Id'] ?>">
                                                <input type="hidden" name="read" value="<?= $msg['Read'] ? 0 : 1 ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-primary">
                                                    Mark as <?= $msg['Read'] ? 'Unread' : 'Read' ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <nav>
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>