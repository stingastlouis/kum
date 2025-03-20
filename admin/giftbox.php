<?php include 'includes/header.php'; ?>

<?php
// Fetch cake gift boxes from the database
include '../configs/db.php';

$success = isset($_GET["success"]) ? $_GET["success"] : null;
$stmt = $conn->prepare("
   SELECT gb.*, 
       s.StatusName AS LatestStatus 
FROM GiftBox gb
LEFT JOIN (
    SELECT gbs.GiftBoxId, 
           MAX(gbs.Id) AS LatestStatusId
    FROM GiftBoxStatus gbs
    GROUP BY gbs.GiftBoxId
) latest_gbs ON gb.Id = latest_gbs.GiftBoxId
LEFT JOIN GiftBoxStatus gbs ON latest_gbs.LatestStatusId = gbs.Id
LEFT JOIN Status s ON gbs.StatusId = s.Id;
");
$stmt->execute();
$giftBoxes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt2 = $conn->prepare("SELECT * FROM Status");
$stmt2->execute();
$statuses = $stmt2->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h3 class="text-dark mb-4">Cake Gift Boxes</h3>
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <p class="text-primary m-0 fw-bold">Cake Gift Box List</p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGiftBoxModal">
                Add Cake Gift Box
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive table mt-2" id="dataTable" role="grid" aria-describedby="dataTable_info">
                <table class="table my-0" id="dataTable">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Discount Price</th>
                            <th>Image</th>
                            <th>Latest Status</th>
                            <th>Date Created</th>                            
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($giftBoxes as $giftBox): ?>
                            <tr>
                                <td><?= htmlspecialchars($giftBox['Id']) ?></td>
                                <td><?= htmlspecialchars($giftBox['Name']) ?></td>
                                <td><?= htmlspecialchars($giftBox['Description']) ?></td>
                                <td><?= htmlspecialchars($giftBox['Price']) ?></td>
                                <td><?= htmlspecialchars($giftBox['DiscountPrice']) ?></td>
                                <td>
                                    <img src="../assets/uploads/<?= htmlspecialchars($giftBox['ImagePath']) ?>" alt="<?= htmlspecialchars($giftBox['Name']) ?>" style="width: 100px; height: auto;">
                                </td>
                                <td><?= htmlspecialchars($giftBox['LatestStatus']) ?: 'No Status' ?></td>
                                <td><?= htmlspecialchars($giftBox['DateCreated']) ?></td>                              
                                <td style="padding:10px">

                                <?php
                                echo "
                                    <button class='btn btn-warning btn-sm edit-giftBox-btn' 
                                        data-id='{$giftBox['Id']}' 
                                        data-name='{$giftBox['Name']}' 
                                        data-description='{$giftBox['Description']}' 
                                        data-price='{$giftBox['Price']}' 
                                        data-discount='{$giftBox['DiscountPrice']}'>Edit</button>";
                                ?>
                                    <button style="font-size: 12px;" class="btn btn-danger btn-del" data-bs-toggle="modal" data-bs-target="#deleteGiftBoxModal" data-id="<?= $giftBox['Id'] ?>">Delete</button>
                                    <form method="POST" action="status/add_giftBoxStatus.php" style="display: inline;">
                                        <input type="hidden" name="giftBox_id" value="<?= $giftBox['Id'] ?>">
                                        <select name="status_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <option value="" disabled selected>Change Status</option>
                                            <?php foreach ($statuses as $status): ?>
                                                <option value="<?= $status['Id'] ?>"><?= htmlspecialchars($status['Name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
