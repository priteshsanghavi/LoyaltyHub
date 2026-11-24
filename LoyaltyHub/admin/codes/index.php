
<?php
require_once "../../config/config.php";
require_once "../../config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: " . SITE_URL . "auth/login.php");
    exit;
}

$pageTitle = "Product Codes Management";

// Search and filter
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

$allowedStatuses = ['active', 'redeemed', 'blocked'];

if (!in_array($status, $allowedStatuses, true)) {
    $status = '';
}

// Dashboard statistics
$stats = [
    'total' => 0,
    'active' => 0,
    'redeemed' => 0,
    'blocked' => 0
];

$result = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(status = 'active') AS active,
        SUM(status = 'redeemed') AS redeemed,
        SUM(status = 'blocked') AS blocked
    FROM product_codes
");

if ($result) {
    $row = $result->fetch_assoc();

    foreach ($stats as $key => $value) {
        $stats[$key] = (int)($row[$key] ?? 0);
    }
}

// Fetch codes with search and filter
$sql = "
    SELECT
        pc.id,
        pc.code_last4,
        pc.status,
        pc.created_at,
        pc.redeemed_at,
        p.product_name,
        b.batch_number
    FROM product_codes pc
    LEFT JOIN products p ON p.id = pc.product_id
    LEFT JOIN product_batches b ON b.id = pc.batch_id
    WHERE 1=1
";

$types = "";
$params = [];

if ($search !== '') {
    $sql .= "
        AND (
            pc.code_last4 LIKE ?
            OR p.product_name LIKE ?
            OR b.batch_number LIKE ?
        )
    ";

    $searchTerm = "%" . $search . "%";

    $types .= "sss";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($status !== '') {
    $sql .= " AND pc.status = ?";
    $types .= "s";
    $params[] = $status;
}

$sql .= " ORDER BY pc.id DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$codes = $stmt->get_result();

require_once "../../includes/header.php";
?>

<div class="container-fluid py-4">

    <!-- Heading -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

        <div>
            <h2 class="fw-bold mb-1">Product Codes Management</h2>
            <p class="text-muted mb-0">
                Manage generated loyalty product codes.
            </p>
        </div>

        <a href="<?= SITE_URL ?>admin/codes/generate.php"
           class="btn btn-success px-4">

            <i class="bi bi-plus-circle me-2"></i>
            Generate New Codes

        </a>

    </div>

    <!-- Statistics -->
    <div class="row g-3 mb-4">

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-2">Total Codes</p>
                            <h3 class="fw-bold mb-0">
                                <?= $stats['total'] ?>
                            </h3>
                        </div>
                        <div class="rounded-3 bg-primary bg-opacity-10
                                    text-primary p-3">
                            <i class="bi bi-qr-code fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-2">Active Codes</p>
                            <h3 class="fw-bold text-success mb-0">
                                <?= $stats['active'] ?>
                            </h3>
                        </div>
                        <div class="rounded-3 bg-success bg-opacity-10
                                    text-success p-3">
                            <i class="bi bi-check-circle fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-2">Redeemed Codes</p>
                            <h3 class="fw-bold text-primary mb-0">
                                <?= $stats['redeemed'] ?>
                            </h3>
                        </div>
                        <div class="rounded-3 bg-primary bg-opacity-10
                                    text-primary p-3">
                            <i class="bi bi-gift fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-2">Blocked Codes</p>
                            <h3 class="fw-bold text-danger mb-0">
                                <?= $stats['blocked'] ?>
                            </h3>
                        </div>
                        <div class="rounded-3 bg-danger bg-opacity-10
                                    text-danger p-3">
                            <i class="bi bi-shield-x fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Code Table -->
    <div class="card border-0 shadow-sm rounded-4">

        <div class="card-body p-4">

            <div class="d-flex flex-wrap justify-content-between
                        align-items-center gap-3 mb-4">

                <div>
                    <h5 class="fw-bold mb-1">All Product Codes</h5>
                    <p class="text-muted mb-0">
                        View and search generated codes.
                    </p>
                </div>

                <form method="GET" class="d-flex flex-wrap gap-2">

                    <input type="text"
                           name="search"
                           class="form-control"
                           placeholder="Search code, product, batch..."
                           value="<?= htmlspecialchars($search) ?>">

                    <select name="status" class="form-select">

                        <option value="">All Status</option>

                        <option value="active"
                            <?= $status === 'active' ? 'selected' : '' ?>>
                            Active
                        </option>

                        <option value="redeemed"
                            <?= $status === 'redeemed' ? 'selected' : '' ?>>
                            Redeemed
                        </option>

                        <option value="blocked"
                            <?= $status === 'blocked' ? 'selected' : '' ?>>
                            Blocked
                        </option>

                    </select>

                    <button type="submit" class="btn btn-dark">
                        <i class="bi bi-search"></i> Search
                    </button>

                    <a href="index.php" class="btn btn-outline-secondary">
                        Reset
                    </a>

                </form>

            </div>

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Code (Last 4)</th>
                            <th>Product</th>
                            <th>Batch Number</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th>Redeemed Date</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($codes->num_rows > 0): ?>

                        <?php $i = 1; ?>

                        <?php while ($code = $codes->fetch_assoc()): ?>

                            <tr>

                                <td><?= $i++ ?></td>

                                <td>
                                    <span class="badge bg-light text-dark
                                                 border font-monospace">
                                        ****<?= htmlspecialchars($code['code_last4'] ?? '----') ?>
                                    </span>
                                </td>

                                <td class="fw-semibold">
                                    <?= htmlspecialchars($code['product_name'] ?? 'N/A') ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($code['batch_number'] ?? 'N/A') ?>
                                </td>

                                <td>

                                    <?php if ($code['status'] === 'active'): ?>

                                        <span class="badge bg-success">
                                            Active
                                        </span>

                                    <?php elseif ($code['status'] === 'redeemed'): ?>

                                        <span class="badge bg-primary">
                                            Redeemed
                                        </span>

                                    <?php else: ?>

                                        <span class="badge bg-danger">
                                            Blocked
                                        </span>

                                    <?php endif; ?>

                                </td>

                                <td>
                                    <?= htmlspecialchars($code['created_at']) ?>
                                </td>

                                <td>
                                    <?= $code['redeemed_at']
                                        ? htmlspecialchars($code['redeemed_at'])
                                        : '<span class="text-muted">Not Redeemed</span>' ?>
                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="text-center py-5">

                                <i class="bi bi-inbox fs-1 text-muted"></i>

                                <h6 class="fw-bold mt-3">
                                    No Product Codes Found
                                </h6>

                                <p class="text-muted mb-3">
                                    Generate product codes to see them here.
                                </p>

                                <a href="<?= SITE_URL ?>admin/codes/generate.php"
                                   class="btn btn-success">
                                    Generate Codes
                                </a>

                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <div class="text-muted small mt-3">
                Showing <?= $codes->num_rows ?> matching code records.
            </div>

        </div>

    </div>

</div>

<?php
$stmt->close();
require_once "../../includes/footer.php";
?>
