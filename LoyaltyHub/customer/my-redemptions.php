
<?php
require_once "../config/database.php";
require_once "../config/config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: " . SITE_URL . "auth/login.php");
    exit;
}

$userId = (int) $_SESSION['user_id'];

// Fetch redemption history using actual database columns
$stmt = mysqli_prepare(
    $conn,
    "SELECT
        rr.id,
        rr.points_spent,
        rr.quantity,
        rr.status,
        rr.created_at,
        r.reward_name,
        r.description
     FROM reward_redemptions rr
     LEFT JOIN rewards r ON rr.reward_id = r.id
     WHERE rr.user_id = ?
     ORDER BY rr.created_at DESC"
);

mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$totalRequests = mysqli_num_rows($result);

require_once "../includes/header.php";
?>

<div class="container py-5">

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

        <div>
            <h2 class="fw-bold mb-1">My Redemption History</h2>
            <p class="text-muted mb-0">
                Track all your reward redemption requests.
            </p>
        </div>

        <div class="bg-success text-white rounded-4 p-3 text-center">
            <div class="small">Total Requests</div>
            <h3 class="fw-bold mb-0">
                <?php echo $totalRequests; ?>
            </h3>
        </div>

    </div>

    <div class="card border-0 shadow-sm rounded-4">

        <div class="card-body p-0">

            <?php if ($totalRequests > 0): ?>

                <div class="table-responsive">

                    <table class="table table-hover align-middle mb-0">

                        <thead class="table-light">
                            <tr>
                                <th class="p-3">#</th>
                                <th>Reward</th>
                                <th>Quantity</th>
                                <th>Points Used</th>
                                <th>Request Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php $count = 1; ?>

                            <?php while ($row = mysqli_fetch_assoc($result)): ?>

                                <?php
                                $status = strtolower($row['status'] ?? 'pending');

                                $statusClass = 'bg-warning text-dark';

                                if ($status === 'approved' || $status === 'shipped') {
                                    $statusClass = 'bg-success';
                                } elseif ($status === 'rejected') {
                                    $statusClass = 'bg-danger';
                                }
                                ?>

                                <tr>

                                    <td class="p-3">
                                        <?php echo $count++; ?>
                                    </td>

                                    <td>
                                        <div class="fw-semibold">
                                            <?php echo htmlspecialchars($row['reward_name'] ?? 'Reward unavailable'); ?>
                                        </div>

                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($row['description'] ?? ''); ?>
                                        </small>
                                    </td>

                                    <td>
                                        <?php echo (int) $row['quantity']; ?>
                                    </td>

                                    <td>
                                        <span class="fw-bold text-success">
                                            <?php echo number_format((int) $row['points_spent']); ?> PTS
                                        </span>
                                    </td>

                                    <td>
                                        <?php
                                        echo date(
                                            "d M Y, h:i A",
                                            strtotime($row['created_at'])
                                        );
                                        ?>
                                    </td>

                                    <td>
                                        <span class="badge <?php echo $statusClass; ?> px-3 py-2">
                                            <?php echo htmlspecialchars(ucfirst($status)); ?>
                                        </span>
                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>

            <?php else: ?>

                <div class="text-center py-5">

                    <i class="bi bi-gift text-success"
                       style="font-size:65px;"></i>

                    <h5 class="fw-bold mt-3">
                        No Redemption History Yet
                    </h5>

                    <p class="text-muted">
                        You haven't redeemed any rewards yet.
                    </p>

                    <a href="<?php echo SITE_URL; ?>customer/rewards.php"
                       class="btn btn-success rounded-3">
                        Explore Rewards
                    </a>

                </div>

            <?php endif; ?>

        </div>

    </div>

</div>

<?php
mysqli_stmt_close($stmt);
require_once "../includes/footer.php";
?>
