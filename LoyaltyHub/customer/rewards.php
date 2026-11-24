
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
$message = "";
$messageType = "";

// Calculate current points balance
function getCustomerBalance($conn, $userId) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT COALESCE(SUM(
            CASE
                WHEN transaction_type = 'earned' THEN points
                WHEN transaction_type = 'redeemed' THEN -points
                ELSE points
            END
        ), 0) AS balance
        FROM points_transactions
        WHERE user_id = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    mysqli_stmt_close($stmt);

    return (int) $row['balance'];
}

$balance = getCustomerBalance($conn, $userId);

// Handle reward redemption
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $rewardId = (int) ($_POST['reward_id'] ?? 0);

    if ($rewardId <= 0) {
        $message = "Invalid reward selected.";
        $messageType = "danger";
    } else {

        mysqli_begin_transaction($conn);

        try {

            // Lock reward row
            $stmt = mysqli_prepare(
                $conn,
                "SELECT id, reward_name, points_required, stock
                 FROM rewards
                 WHERE id = ? AND status = 'active'
                 FOR UPDATE"
            );

            mysqli_stmt_bind_param($stmt, "i", $rewardId);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $reward = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if (!$reward) {
                throw new Exception("This reward is not available.");
            }

            $requiredPoints = (int) $reward['points_required'];
            $stock = (int) $reward['stock'];

            if ($stock <= 0) {
                throw new Exception("Sorry, this reward is out of stock.");
            }

            // Lock customer's points transactions
            $stmt = mysqli_prepare(
                $conn,
                "SELECT id FROM points_transactions
                 WHERE user_id = ?
                 FOR UPDATE"
            );

            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_get_result($stmt);
            mysqli_stmt_close($stmt);

            $balance = getCustomerBalance($conn, $userId);

            if ($balance < $requiredPoints) {
                throw new Exception("You do not have enough points.");
            }

            // Reduce reward stock
            $stmt = mysqli_prepare(
                $conn,
                "UPDATE rewards
                 SET stock = stock - 1
                 WHERE id = ? AND stock > 0"
            );

            mysqli_stmt_bind_param($stmt, "i", $rewardId);
            mysqli_stmt_execute($stmt);

            if (mysqli_stmt_affected_rows($stmt) !== 1) {
                throw new Exception("Reward stock is unavailable.");
            }

            mysqli_stmt_close($stmt);

            // Create redemption request
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO reward_redemptions
                 (user_id, reward_id, points_used, status, requested_at)
                 VALUES (?, ?, ?, 'pending', NOW())"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "iii",
                $userId,
                $rewardId,
                $requiredPoints
            );

            mysqli_stmt_execute($stmt);
            $redemptionId = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            // Deduct points immediately
            $description = "Points used for reward redemption #" . $redemptionId;

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO points_transactions
                 (user_id, code_id, transaction_type, points, description)
                 VALUES (?, NULL, 'redeemed', ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "iis",
                $userId,
                $requiredPoints,
                $description
            );

            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            mysqli_commit($conn);

            $balance = getCustomerBalance($conn, $userId);

            $message = "Reward redemption request submitted successfully!";
            $messageType = "success";

        } catch (Exception $e) {

            mysqli_rollback($conn);

            $message = $e->getMessage();
            $messageType = "danger";

            $balance = getCustomerBalance($conn, $userId);
        }
    }
}

// Fetch available rewards
$rewardsQuery = mysqli_query(
    $conn,
    "SELECT * FROM rewards
     WHERE status = 'active'
     ORDER BY points_required ASC"
);

require_once "../includes/header.php";
?>

<div class="container py-5">

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">

        <div>
            <h2 class="fw-bold mb-1">Rewards Collection</h2>
            <p class="text-muted mb-0">
                Choose your favorite rewards and redeem your points.
            </p>
        </div>

        <div class="bg-success text-white rounded-4 p-3 text-center">
            <div class="small">Your Available Points</div>
            <h3 class="fw-bold mb-0">
                <?php echo number_format($balance); ?>
                <small class="fs-6">PTS</small>
            </h3>
        </div>

    </div>

    <?php if ($message !== ""): ?>
        <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible fade show">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <?php if ($rewardsQuery && mysqli_num_rows($rewardsQuery) > 0): ?>

            <?php while ($reward = mysqli_fetch_assoc($rewardsQuery)): ?>

                <?php
                $required = (int) $reward['points_required'];
                $stock = (int) $reward['stock'];
                $canRedeem = ($balance >= $required && $stock > 0);
                ?>

                <div class="col-md-6 col-lg-4">

                    <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">

                        <div class="bg-light text-center py-5">
                            <i class="bi bi-gift-fill text-success"
                               style="font-size:65px;"></i>
                        </div>

                        <div class="card-body p-4">

                            <h5 class="fw-bold">
                                <?php echo htmlspecialchars($reward['reward_name']); ?>
                            </h5>

                            <p class="text-muted">
                                <?php echo htmlspecialchars($reward['description'] ?? 'Exclusive LoyaltyHub reward'); ?>
                            </p>

                            <div class="d-flex justify-content-between align-items-center mb-3">

                                <span class="text-success fw-bold fs-5">
                                    <?php echo number_format($required); ?> PTS
                                </span>

                                <span class="badge bg-light text-dark">
                                    Stock: <?php echo $stock; ?>
                                </span>

                            </div>

                            <?php if ($canRedeem): ?>

                                <form method="POST"
                                      onsubmit="return confirm('Are you sure you want to redeem this reward?');">

                                    <input type="hidden"
                                           name="reward_id"
                                           value="<?php echo (int) $reward['id']; ?>">

                                    <button type="submit"
                                            class="btn btn-success w-100 rounded-3">

                                        <i class="bi bi-stars me-2"></i>
                                        Redeem Reward

                                    </button>

                                </form>

                            <?php elseif ($stock <= 0): ?>

                                <button class="btn btn-secondary w-100" disabled>
                                    Out of Stock
                                </button>

                            <?php else: ?>

                                <button class="btn btn-outline-secondary w-100" disabled>
                                    Need <?php echo number_format($required - $balance); ?> More Points
                                </button>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="col-12">
                <div class="alert alert-info">
                    No rewards are available right now.
                </div>
            </div>

        <?php endif; ?>

    </div>

</div>

<?php require_once "../includes/footer.php"; ?>
