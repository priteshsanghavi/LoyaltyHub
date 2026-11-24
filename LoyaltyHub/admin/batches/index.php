
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

$pageTitle = "Batch Management";

// Search
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT
        b.id,
        b.batch_number,
        b.quantity,
        b.manufactured_date,
        b.created_at,
        p.product_name,
        COUNT(pc.id) AS generated_codes
    FROM product_batches b
    LEFT JOIN products p ON p.id = b.product_id
    LEFT JOIN product_codes pc ON pc.batch_id = b.id
    WHERE (
        b.batch_number LIKE ?
        OR p.product_name LIKE ?
    )
    GROUP BY b.id, b.batch_number, b.quantity,
             b.manufactured_date, b.created_at, p.product_name
    ORDER BY b.id DESC
";

$stmt = $conn->prepare($sql);
$searchTerm = "%" . $search . "%";
$stmt->bind_param("ss", $searchTerm, $searchTerm);
$stmt->execute();
$batches = $stmt->get_result();

// Statistics
$statsResult = $conn->query("
    SELECT
        COUNT(*) AS total_batches,
        COALESCE(SUM(quantity), 0) AS total_quantity
    FROM product_batches
");

$stats = $statsResult->fetch_assoc();

$codeStatsResult = $conn->query("
    SELECT COUNT(*) AS total_codes
    FROM product_codes
");

$codeStats = $codeStatsResult->fetch_assoc();

$totalGenerated = (int)$codeStats['total_codes'];

require_once "../../includes/header.php";
?>

<div class="container-fluid py-4">

    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

        <div>
            <h2 class="fw-bold mb-1">Batch Management</h2>
            <p class="text-muted mb-0">
                Manage product manufacturing batches and generated codes.
            </p>
        </div>

        <a href="<?= SITE_URL ?>admin/batches/add.php"
           class="btn btn-success px-4">

            <i class="bi bi-plus-circle me-2"></i>
            Add New Batch

        </a>

    </div>

    <!-- Statistics -->
    <div class="row g-3 mb-4">

        <div class="col-xl-4 col-md-6">

            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>
                            <p class="text-muted mb-2">Total Batches</p>
                            <h3 class="fw-bold mb-0">
                                <?= (int)$stats['total_batches'] ?>
                            </h3>
                        </div>

                        <div class="bg-primary bg-opacity-10 text-primary
                                    rounded-3 p-3">
                            <i class="bi bi-box-seam fs-3"></i>
                        </div>

                    </div>

                </div>
            </div>

        </div>

        <div class="col-xl-4 col-md-6">

            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>
                            <p class="text-muted mb-2">Total Box Quantity</p>
                            <h3 class="fw-bold text-success mb-0">
                                <?= number_format((int)$stats['total_quantity']) ?>
                            </h3>
                        </div>

                        <div class="bg-success bg-opacity-10 text-success
                                    rounded-3 p-3">
                            <i class="bi bi-boxes fs-3"></i>
                        </div>

                    </div>

                </div>
            </div>

        </div>

        <div class="col-xl-4 col-md-6">

            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>
                            <p class="text-muted mb-2">Generated Codes</p>
                            <h3 class="fw-bold text-primary mb-0">
                                <?= number_format($totalGenerated) ?>
                            </h3>
                        </div>

                        <div class="bg-info bg-opacity-10 text-info
                                    rounded-3 p-3">
                            <i class="bi bi-qr-code fs-3"></i>
                        </div>

                    </div>

                </div>
            </div>

        </div>

    </div>

    <!-- Batch Table -->
    <div class="card border-0 shadow-sm rounded-4">

        <div class="card-body p-4">

            <div class="d-flex flex-wrap justify-content-between
                        align-items-center gap-3 mb-4">

                <div>
                    <h5 class="fw-bold mb-1">All Product Batches</h5>
                    <p class="text-muted mb-0">
                        View and track your product batches.
                    </p>
                </div>

                <form method="GET" class="d-flex gap-2">

                    <input type="text"
                           name="search"
                           class="form-control"
                           placeholder="Search batch or product..."
                           value="<?= htmlspecialchars($search) ?>">

                    <button type="submit" class="btn btn-dark">
                        <i class="bi bi-search"></i>
                    </button>

                    <a href="index.php"
                       class="btn btn-outline-secondary">
                        Reset
                    </a>

                </form>

            </div>

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-light">

                        <tr>
                            <th>#</th>
                            <th>Batch Number</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Generated Codes</th>
                            <th>Remaining</th>
                            <th>Manufactured Date</th>
                            <th>Created Date</th>
                        </tr>

                    </thead>

                    <tbody>

                    <?php if ($batches->num_rows > 0): ?>

                        <?php $i = 1; ?>

                        <?php while ($batch = $batches->fetch_assoc()): ?>

                            <?php
                            $quantity = (int)$batch['quantity'];
                            $generated = (int)$batch['generated_codes'];
                            $remaining = max(0, $quantity - $generated);
                            ?>

                            <tr>

                                <td><?= $i++ ?></td>

                                <td>
                                    <span class="fw-semibold text-success">
                                        <?= htmlspecialchars($batch['batch_number']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= htmlspecialchars($batch['product_name'] ?? 'N/A') ?>
                                </td>

                                <td>
                                    <?= number_format($quantity) ?> Boxes
                                </td>

                                <td>
                                    <span class="badge bg-primary">
                                        <?= number_format($generated) ?>
                                    </span>
                                </td>

                                <td>
                                    <?php if ($remaining > 0): ?>
                                        <span class="badge bg-success">
                                            <?= number_format($remaining) ?> Left
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">
                                            Completed
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= $batch['manufactured_date']
                                        ? htmlspecialchars($batch['manufactured_date'])
                                        : '<span class="text-muted">Not Set</span>' ?>
                                </td>

                                <td>
                                    <?= date(
                                        'd M Y',
                                        strtotime($batch['created_at'])
                                    ) ?>
                                </td>

                            </tr>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" class="text-center py-5">

                                <i class="bi bi-inbox fs-1 text-muted"></i>

                                <h6 class="fw-bold mt-3">
                                    No Batches Found
                                </h6>

                                <p class="text-muted">
                                    Create your first product batch.
                                </p>

                                <a href="<?= SITE_URL ?>admin/batches/add.php"
                                   class="btn btn-success">
                                    Add New Batch
                                </a>

                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <div class="text-muted small mt-3">
                Showing <?= $batches->num_rows ?> batches.
            </div>

        </div>

    </div>

</div>

<?php
$stmt->close();
require_once "../../includes/footer.php";
?>
