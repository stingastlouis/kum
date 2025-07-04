<?php
require_once 'auth.php';
requireEmployeeLogin([ROLE_DELIVERY]);
include 'includes/header.php';
include '../configs/db.php';
require_once './utils/orderUtils.php';

$employeeId = $_SESSION['employeeId'];

$sql = "
    SELECT 
        d.Id AS DeliveryId,
        o.ScheduleDate,
        o.Id AS OrderId,
        d.Location,
        s.StatusName AS Status
    FROM Delivery d
    INNER JOIN Orders o ON d.OrderId = o.Id
    LEFT JOIN (
        SELECT ds1.DeliveryId, s1.StatusName
        FROM DeliveryStatus ds1
        LEFT JOIN Status s1 ON ds1.StatusId = s1.Id
        WHERE ds1.Id IN (
            SELECT MAX(ds2.Id)
            FROM DeliveryStatus ds2
            GROUP BY ds2.DeliveryId
        )
    ) s ON s.DeliveryId = d.Id
    WHERE d.EmployeeId = :employeeId
      AND (s.StatusName IS NULL OR s.StatusName != 'DELIVERED')
    ORDER BY o.Id DESC
";


$stmt = $conn->prepare($sql);
$stmt->bindParam(':employeeId', $employeeId, PDO::PARAM_INT);
$stmt->execute();
$deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);



$statusStmt = $conn->prepare("SELECT * FROM Status WHERE StatusName IN ('DELIVERED','OUT FOR DELIVERY');");
$statusStmt->execute();
$Deliverystatuses = $statusStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid" style="height: 90vh;">
    <h3 class="text-dark mb-4">Assigned Deliveries</h3>
    <div class="card shadow">
        <div class="card-header py-3">
            <p class="text-secondary m-0 fw-bold">Your Deliveries</p>
        </div>
        <div class="card-body">
            <?php if (count($deliveries) > 0): ?>
                <div class="table-responsive table mt-2">
                    <table class="table my-0" id="deliveryTable">
                        <thead>
                            <tr>
                                <th>Delivery ID</th>
                                <th>Scheduled Date</th>
                                <th>Location (lat,lng)</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deliveries as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['DeliveryId']) ?></td>
                                    <td><?= htmlspecialchars($row['ScheduleDate']) ?></td>
                                    <td> <button class="btn btn-primary btn-sm btn-map"
                                            data-location="<?= htmlspecialchars($row['Location']) ?>"
                                            data-bs-toggle="modal"
                                            data-bs-target="#mapModal">
                                            View Map
                                        </button>
                                    </td>
                                    <td><?= htmlspecialchars($row['Status'] ?? 'Pending') ?></td>
                                    <td>
                                        <?php if ($row['Location'] && $row['Status'] != 'DELIVERED'): ?>
                                            <form method="POST" action="status/add_deliveryStatus.php" style="margin: 0;">
                                                <input type="hidden" name="order_id" value="<?= $row['OrderId'] ?>">
                                                <input type="hidden" name="employee_id" value="<?= $employeeId ?>">
                                                <select name="status_id" class="form-select form-select-sm"
                                                    style="width: 140px; background-color: #f8f9fa; color: #333; border: 1px solid #ccc;"
                                                    onchange="this.form.submit()">
                                                    <option value="" disabled selected>Change Delivery Status</option>
                                                    <?php foreach (getFilteredStatuses($Deliverystatuses, $row['Status']) as $deliveryStatus): ?>
                                                        <option value="<?= $deliveryStatus['Id'] ?>"><?= htmlspecialchars($deliveryStatus['StatusName']) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </form>
                                        <?php endif ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No deliveries assigned yet.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="mapModal" tabindex="-1" aria-labelledby="mapModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delivery Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="height: 500px;">
                <div id="leafletMap" style="height: 100%; width: 100%;"></div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
    let map;
    let marker;

    document.querySelectorAll('.btn-map').forEach(btn => {
        btn.addEventListener('click', function() {
            const location = this.dataset.location;
            const [lat, lng] = location.split(',').map(Number);

            setTimeout(() => {
                if (!map) {
                    map = L.map('leafletMap').setView([lat, lng], 13);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(map);
                    marker = L.marker([lat, lng]).addTo(map);
                } else {
                    map.setView([lat, lng], 13);
                    marker.setLatLng([lat, lng]);
                }
            }, 300);
        });
    });

    const mapModal = document.getElementById('mapModal');
    mapModal.addEventListener('shown.bs.modal', function() {
        setTimeout(() => {
            if (map) {
                map.invalidateSize();
            }
        }, 100);
    });
</script>