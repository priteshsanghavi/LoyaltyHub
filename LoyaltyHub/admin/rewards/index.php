<?php
require_once "../../config/config.php";
require_once "../../config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . SITE_URL . "auth/login.php");
    exit;
}

$pageTitle = "Manage Rewards";

$search = trim($_GET['search'] ?? '');

$sql = "SELECT * FROM rewards";

if ($search !== '') {
    $searchSafe = mysqli_real_escape_string($conn, $search);
    $sql .= " WHERE reward_name LIKE '%$searchSafe%'";
}

$sql .= " ORDER BY id DESC";

$result = mysqli_query($conn, $sql);

require_once "../../includes/header.php";
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold">Rewards</h2>
            <p class="text-muted">Manage customer loyalty rewards</p>
        </div>

        <a href="add.php" class="btn btn-success rounded-3">
            <i class="bi bi-plus-lg"></i> Add Reward
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">

            <form method="GET" class="mb-4">
                <div class="input-group">
                    <input type="text"
                           name="search"
                           class="form-control"
                           placeholder="Search reward..."
                           value="<?= htmlspecialchars($search) ?>">

                    <button class="btn btn-success">Search</button>
                    <a href="index.php" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Reward</th>
                            <th>Description</th>
                            <th>Required Points</th>
                            <th>Stock</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>#<?= $row['id'] ?></td>

                                <td class="fw-semibold">
                                    <?= htmlspecialchars($row['reward_name']) ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['description'] ?? '-') ?>
                                </td>

                                <td>
                                    <span class="badge bg-success-subtle text-success">
                                        <?= number_format($row['points_required']) ?> Points
                                    </span>
                                </td>

                                <td><?= number_format($row['stock']) ?></td>

                                <td>
                                    <?php if ($row['status'] === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                No rewards found.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>

                </table>
            </div>

        </div>
    </div>
</div>

<?php require_once "../../includes/footer.php"; ?>