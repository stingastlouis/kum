<?php
include "includes/header.php";
include './configs/db.php';

$search = $_GET['search'] ?? '';
$categoryId = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 8;
$offset = ($page - 1) * $perPage;

try {
    $stmtCats = $conn->query("SELECT Id, Name FROM Category ORDER BY Name ASC");
    $categories = $stmtCats->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}

$where = [];
$params = [];

$where[] = "cs.DateCreated = (SELECT MAX(cs_inner.DateCreated) FROM CakeStatus cs_inner WHERE cs_inner.CakeId = c.Id)";
$where[] = "s.StatusName != 'INACTIVE'";

if ($search) {
    $where[] = "c.Name LIKE :search";
    $params[':search'] = '%' . $search . '%';
}
if ($categoryId) {
    $where[] = "c.CategoryId = :categoryId";
    $params[':categoryId'] = $categoryId;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sortSql = '';
if ($sort === 'price_asc') {
    $sortSql = 'ORDER BY c.Price ASC';
} elseif ($sort === 'price_desc') {
    $sortSql = 'ORDER BY c.Price DESC';
} else {
    $sortSql = 'ORDER BY c.DateCreated DESC';
}


try {
    $stmtCount = $conn->prepare("
        SELECT COUNT(*) 
        FROM Cakes c
        LEFT JOIN CakeStatus cs ON c.Id = cs.CakeId
        LEFT JOIN Status s ON cs.StatusId = s.Id
        $whereSql
    ");
    $stmtCount->execute($params);
    $total = $stmtCount->fetchColumn();
    $totalPages = ceil($total / $perPage);
} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT c.*, cs.StatusId, s.StatusName
        FROM Cakes c
        LEFT JOIN CakeStatus cs ON c.Id = cs.CakeId
        LEFT JOIN Status s ON cs.StatusId = s.Id
        $whereSql
        $sortSql
        LIMIT :limit OFFSET :offset
    ");

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $cakes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo 'Error: ' . $e->getMessage();
    exit;
}
?>

<link rel="stylesheet" href="./assets/css/cakes.css">

<div class="container py-4">
    <h1 class="text-center mb-5 cake-title"> Our Yummiest Cakes </h1>

    <form method="GET" class="row mb-4 g-2 align-items-center">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="Search cakes..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-3">
            <select name="category" class="form-select">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['Id'] ?>" <?= ($categoryId == $cat['Id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['Name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select name="sort" class="form-select">
                <option value="">Sort by</option>
                <option value="price_asc" <?= ($sort === 'price_asc') ? 'selected' : '' ?>>Price: Low to High</option>
                <option value="price_desc" <?= ($sort === 'price_desc') ? 'selected' : '' ?>>Price: High to Low</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </form>

    <div class="row">
        <?php if (!empty($cakes)) : ?>
            <?php foreach ($cakes as $cake) :
                $hasDiscount = !empty($cake['DiscountPrice']) && $cake['DiscountPrice'] > 0;
                $isOutOfStock = empty($cake['StockCount']) || $cake['StockCount'] <= 0;
            ?>
                <div class="col-md-3 mb-4">
                    <div class="cake-card shadow-sm d-flex flex-column">
                        <img src="./assets/uploads/cakes/<?= htmlspecialchars($cake['ImagePath']) ?>" class="card-img-top mb-2" alt="<?= htmlspecialchars($cake['Name']) ?>">
                        <div class="card-body d-flex flex-column p-2">
                            <h5 class="card-title"><?= htmlspecialchars($cake['Name']) ?></h5>
                            <p><?= htmlspecialchars($cake['Description']) ?></p>
                            <p>
                                <?php if ($hasDiscount): ?>
                                    <strong class="text-danger">$<?= number_format($cake['DiscountPrice'], 2) ?></strong>
                                    <span style="text-decoration: line-through; color: grey;">$<?= number_format($cake['Price'], 2) ?></span>
                                <?php else: ?>
                                    <strong>$<?= number_format($cake['Price'], 2) ?></strong>
                                <?php endif; ?>
                            </p>
                            <p style="font-size: 0.85rem;">
                                <strong>Stock:</strong> <?= intval($cake['StockCount']) ?>
                            </p>
                            <div class="d-flex align-items-center mt-auto">
                                <input type="number" class="form-control me-2 quantity-input" min="1" max="<?= intval($cake['StockCount']) ?>" value="1" style="width: 60px;" <?= $isOutOfStock ? 'disabled' : '' ?>>
                                <button class="btn btn-secondary add-to-cart"
                                    data-id="<?= htmlspecialchars($cake['Id']) ?>"
                                    data-name="<?= htmlspecialchars($cake['Name']) ?>"
                                    data-type="cake"
                                    data-price="<?= number_format($hasDiscount ? $cake['DiscountPrice'] : $cake['Price'], 2) ?>"
                                    data-stock="<?= intval($cake['StockCount']) ?>"
                                    <?= $isOutOfStock ? 'disabled' : '' ?>>
                                    Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No cakes available.</p>
        <?php endif; ?>
    </div>

    <?php if ($totalPages > 1) : ?>
        <nav>
            <ul class="pagination justify-content-center">
                <?php for ($p = 1; $p <= $totalPages; $p++) : ?>
                    <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>">
                            <?= $p ?>
                        </a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<?php include "includes/footer.php" ?>