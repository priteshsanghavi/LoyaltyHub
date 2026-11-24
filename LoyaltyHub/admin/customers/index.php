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

$pageTitle = "Manage Customers";

$search = trim($_GET['search'] ?? '');

$sql = "SELECT id, name, email, phone, status, created_at
        FROM users
        WHERE role = 'customer'";

if ($search !== '') {
    $searchSafe = mysqli_real_escape_string($conn, $search);
    $sql .= " AND (name LIKE '%$searchSafe%'
              OR email LIKE '%$searchSafe%'
              OR phone LIKE '%$searchSafe%')";
}

$sql .= " ORDER BY id DESC";

$result = mysqli_query($conn, $sql);

require_once "../../includes/header.php";
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold">Customers</h2>
            <p class="text-muted">Manage your LoyaltyHub customers</p>
        </div>
        <span class="badge bg-success p-2">Customer Management</span>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">

            <form method="GET" class="mb-4">
                <div class="input-group">
                    <input type="text"
                           name="search"
                           class="form-control"
                           placeholder="Search name, email or phone..."
                           value="<?= htmlspecialchars($search) ?>">

                    <button class="btn btn-success">
                        <i class="bi bi-search"></i> Search
                    </button>

                    <a href="index.php" class="btn btn-outline-secondary">
                        Reset
                    </a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Joined</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>#<?= $row['id'] ?></td>

                                <td class="fw-semibold">
                                    <?= htmlspecialchars($row['name']) ?>
                                </td>

                                <td><?= htmlspecialchars($row['email']) ?></td>

                                <td><?= htmlspecialchars($row['phone'] ?? '-') ?></td>

                                <td>
                                    <?php if ($row['status'] === 'active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Blocked</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= date('d M Y', strtotime($row['created_at'])) ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                No customers found.
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