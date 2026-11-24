
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

$pageTitle = "Reward Redemptions";

$error = "";
$success = "";

// Update redemption status
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $redemptionId = (int)($_POST['redemption_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    $adminNotes = trim($_POST['admin_notes'] ?? '');

    $allowedStatuses = ['pending', 'approved', 'shipped', 'rejected'];

    if ($redemptionId <= 0 || !in_array($newStatus, $allowedStatuses, true)) {
        $error = "Invalid redemption request.";
    } else {

        $stmt = $conn->prepare(
            "UPDATE reward_redemptions
             SET status = ?, admin_notes = ?
             WHERE id = ?"
        );

        $stmt->bind_param("ssi", $newStatus, $adminNotes, $redemptionId);

        if ($stmt->execute() && $stmt->affected_rows >= 0) {
            header("Location: index.php?updated=1");
            exit;
        } else {
            $error = "Unable to update redemption status.";
        }

        $stmt->close();
    }
}

if (isset($_GET['updated'])) {
    $success = "Redemption status updated successfully.";
}

// Search and filter
$search = trim($_GET['search'] ?? '');
$filterStatus = $_GET['status'] ?? '';

$sql = "SELECT
            rr.id,
            rr.user_id,
            rr.reward_id,
            rr.points_spent,
            rr.quantity,
            rr.status,
            rr.admin_notes,
            rr.created_at,
            u.name AS customer_name,
            u.email AS customer_email,
            r.reward_name
        FROM reward_redemptions rr
        INNER JOIN users u ON rr.user_id = u.id
        INNER JOIN rewards r ON rr.reward_id = r.id
        WHERE 1=1";

if ($search !== '') {
    $sql .= " AND (
        u.name LIKE ?
        OR u.email LIKE ?
        OR r.reward_name LIKE ?
    )";
}

if (in_array($filterStatus, ['pending', 'approved', 'shipped', 'rejected'], true)) {
    $sql .= " AND rr.status = ?";
}

$sql .= " ORDER BY rr.id DESC";

$stmt = $conn->prepare($sql);

if ($search !== '' && in_array($filterStatus, ['pending', 'approved', 'shipped', 'rejected'], true)) {
    $searchTerm = "%{$search}%";
    $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $filterStatus);
} elseif ($search !== '') {
    $searchTerm = "%{$search}%";
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
} elseif (in_array($filterStatus, ['pending', 'approved', 'shipped', 'rejected'], true)) {
    $stmt->bind_param("s", $filterStatus);
}

$stmt->execute();
$result = $stmt->get_result();

require_once "../../includes/header.php";
?>

<div class="container-fluid py-4">

    <div class="mb-4">
        <h2 class="fw-bold">Reward Redemptions</h2>
        <p class="text-muted">
            Manage customer reward requests and delivery status.
        </p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle me-2"></i>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">

            <form method="GET" class="row g-3 mb-4">

                <div class="col-md-6">
                    <input type="text"
                           name="search"
                           class="form-control"
                           placeholder="Search customer or reward..."
                           value="<?= htmlspecialchars($search) ?>">
                </div>

                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <?php foreach (['pending', 'approved', 'shipped', 'rejected'] as $statusOption): ?>
                            <option value="<?= $statusOption ?>"
                                <?= $filterStatus === $statusOption ? 'selected' : '' ?>>
                                <?= ucfirst($statusOption) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-search"></i> Filter
                    </button>

                    <a href="index.php" class="btn btn-outline-secondary">
                        Reset
                    </a>
                </div>

            </form>

            <div class="table-responsive">

                <table class="table table-hover align-middle">

                    <thead class="table-light">
                        <tr>
                            <th>Request ID</th>
                            <th>Customer</th>
                            <th>Reward</th>
                            <th>Points</th>
                            <th>Quantity</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($result && $result->num_rows > 0): ?>

                        <?php while ($row = $result->fetch_assoc()): ?>

                            <tr>
                                <td class="fw-semibold">
                                    #<?= $row['id'] ?>
                                </td>

                                <td>
                                    <div class="fw-semibold">
                                        <?= htmlspecialchars($row['customer_name']) ?>
                                    </div>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($row['customer_email']) ?>
                                    </small>
                                </td>

                                <td>
                                    <?= htmlspecialchars($row['reward_name']) ?>
                                </td>

                                <td>
                                    <span class="badge bg-success-subtle text-success">
                                        <?= number_format($row['points_spent']) ?>
                                    </span>
                                </td>

                                <td><?= (int)$row['quantity'] ?></td>

                                <td>
                                    <?= date('d M Y', strtotime($row['created_at'])) ?>
                                </td>

                                <td>
                                    <?php
                                    $badgeClass = [
                                        'pending' => 'warning',
                                        'approved' => 'primary',
                                        'shipped' => 'success',
                                        'rejected' => 'danger'
                                    ][$row['status']] ?? 'secondary';
                                    ?>

                                    <span class="badge bg-<?= $badgeClass ?>">
                                        <?= ucfirst($row['status']) ?>
                                    </span>
                                </td>

                                <td>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-success"
                                            data-bs-toggle="modal"
                                            data-bs-target="#request<?= $row['id'] ?>">
                                        <i class="bi bi-pencil-square"></i> Manage
                                    </button>
                                </td>
                            </tr>

                            <!-- Manage Request Modal -->
                            <div class="modal fade"
                                 id="request<?= $row['id'] ?>"
                                 tabindex="-1">

                                <div class="modal-dialog modal-dialog-centered">

                                    <div class="modal-content border-0 rounded-4">

                                        <form method="POST">

                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold">
                                                    Manage Request #<?= $row['id'] ?>
                                                </h5>

                                                <button type="button"
                                                        class="btn-close"
                                                        data-bs-dismiss="modal"></button>
                                            </div>

                                            <div class="modal-body">

                                                <p class="mb-1">
                                                    <strong>Customer:</strong>
                                                    <?= htmlspecialchars($row['customer_name']) ?>
                                                </p>

                                                <p class="mb-1">
                                                    <strong>Reward:</strong>
                                                    <?= htmlspecialchars($row['reward_name']) ?>
                                                </p>

                                                <p class="mb-3">
                                                    <strong>Points:</strong>
                                                    <?= number_format($row['points_spent']) ?>
                                                </p>

                                                <input type="hidden"
                                                       name="redemption_id"
                                                       value="<?= $row['id'] ?>">

                                                <label class="form-label fw-semibold">
                                                    Update Status
                                                </label>

                                                <select name="status" class="form-select mb-3" required>
                                                    <?php foreach (['pending', 'approved', 'shipped', 'rejected'] as $statusOption): ?>
                                                        <option value="<?= $statusOption ?>"
                                                            <?= $row['status'] === $statusOption ? 'selected' : '' ?>>
                                                            <?= ucfirst($statusOption) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>

                                                <label class="form-label fw-semibold">
                                                    Admin Notes
                                                </label>

                                                <textarea name="admin_notes"
                                                          class="form-control"
                                                          rows="3"
                                                          placeholder="Enter delivery details or notes..."><?= htmlspecialchars($row['admin_notes'] ?? '') ?></textarea>

                                            </div>

                                            <div class="modal-footer">
                                                <button type="button"
                                                        class="btn btn-light"
                                                        data-bs-dismiss="modal">
                                                    Cancel
                                                </button>

                                                <button type="submit" class="btn btn-success">
                                                    <i class="bi bi-check-lg"></i> Save Changes
                                                </button>
                                            </div>

                                        </form>

                                    </div>

                                </div>
                            </div>

                        <?php endwhile; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No redemption requests found.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>
                </table>

            </div>

        </div>
    </div>

</div>

<?php
$stmt->close();
require_once "../../includes/footer.php";
?>
