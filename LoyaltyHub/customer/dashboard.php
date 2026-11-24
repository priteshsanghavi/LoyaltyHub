
<?php

require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../config/database.php";

// Customer Login Protection
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header("Location: " . SITE_URL . "auth/login.php");
    exit;
}

$userId = (int) $_SESSION['user_id'];
$userName = $_SESSION['user_name'];

// Total Reward Points
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(
        CASE
            WHEN transaction_type = 'earned' THEN points
            WHEN transaction_type = 'redeemed' THEN -points
            WHEN transaction_type = 'adjustment' THEN points
            ELSE 0
        END
    ), 0) AS balance
    FROM points_transactions
    WHERE user_id = ?
");

$stmt->bind_param("i", $userId);
$stmt->execute();
$pointsBalance = $stmt->get_result()->fetch_assoc()['balance'];
$stmt->close();

// Total Codes Redeemed
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM product_codes
    WHERE redeemed_by = ? AND status = 'redeemed'
");

$stmt->bind_param("i", $userId);
$stmt->execute();
$totalCodes = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Total Points Earned
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(points), 0) AS total
    FROM points_transactions
    WHERE user_id = ? AND transaction_type = 'earned'
");

$stmt->bind_param("i", $userId);
$stmt->execute();
$totalEarned = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Available Rewards
$result = $conn->query("
    SELECT COUNT(*) AS total
    FROM rewards
    WHERE status = 'active' AND stock > 0
");

$availableRewards = $result->fetch_assoc()['total'];

// Recent Transactions
$stmt = $conn->prepare("
    SELECT transaction_type, points, description, created_at
    FROM points_transactions
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 5
");

$stmt->bind_param("i", $userId);
$stmt->execute();
$transactions = $stmt->get_result();

$pageTitle = "Customer Dashboard";

require_once __DIR__ . "/../includes/header.php";

?>

<div class="container page-container">

    <!-- Welcome Section -->

    <div class="dashboard-welcome">

        <div>
            <span class="welcome-label">
                CUSTOMER DASHBOARD
            </span>

            <h1>
                Welcome back,
                <?php echo htmlspecialchars($userName); ?>!
                👋
            </h1>

            <p>
                Track your rewards, collect points and enjoy exclusive gifts.
            </p>
        </div>

        <a href="<?php echo SITE_URL; ?>customer/verify-code.php"
   class="btn btn-success">
    <i class="bi bi-qr-code-scan me-2"></i>
    Enter Product Code
</a>

    </div>


    <!-- Statistics -->

    <div class="row g-4 mb-4">

        <div class="col-xl-3 col-md-6">

            <div class="stat-card">

                <div class="stat-icon">
                    <i class="bi bi-stars"></i>
                </div>

                <h3>
                    <?php echo number_format($pointsBalance); ?>
                </h3>

                <p>Available Reward Points</p>

                <span class="stat-caption">
                    Your current points balance
                </span>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="stat-card">

                <div class="stat-icon">
                    <i class="bi bi-box-seam"></i>
                </div>

                <h3>
                    <?php echo number_format($totalCodes); ?>
                </h3>

                <p>Codes Redeemed</p>

                <span class="stat-caption">
                    Successfully verified boxes
                </span>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="stat-card">

                <div class="stat-icon">
                    <i class="bi bi-wallet2"></i>
                </div>

                <h3>
                    <?php echo number_format($totalEarned); ?>
                </h3>

                <p>Total Points Earned</p>

                <span class="stat-caption">
                    Lifetime earned points
                </span>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="stat-card">

                <div class="stat-icon">
                    <i class="bi bi-gift"></i>
                </div>

                <h3>
                    <?php echo number_format($availableRewards); ?>
                </h3>

                <p>Available Rewards</p>

                <span class="stat-caption">
                    Gifts waiting for you
                </span>

            </div>

        </div>

    </div>


    <!-- Points Banner -->

    <div class="points-banner mb-4">

        <div class="points-banner-content">

            <span>YOUR REWARD BALANCE</span>

            <h2>
                <?php echo number_format($pointsBalance); ?>
                <small>POINTS</small>
            </h2>

            <p>
                Keep collecting points and unlock exciting rewards!
            </p>

            <a href="<?php echo SITE_URL; ?>customer/rewards.php"
               class="banner-btn">

                Explore Rewards
                <i class="bi bi-arrow-right ms-2"></i>

            </a>

        </div>

        <div class="banner-decoration">
            <i class="bi bi-gift-fill"></i>
        </div>

    </div>


    <!-- Quick Actions -->

    <div class="section-heading mb-3">

        <div>
            <h3>Quick Actions</h3>
            <p>What would you like to do today?</p>
        </div>

    </div>


    <div class="row g-4 mb-5">

        <div class="col-md-4">

            <a href="<?php echo SITE_URL; ?>customer/verify-code.php"
               class="action-card">

                <div class="action-icon">
                    <i class="bi bi-qr-code-scan"></i>
                </div>

                <h5>Verify Product Code</h5>

                <p>Enter your box code and earn reward points.</p>

                <span>Earn Points <i class="bi bi-arrow-right"></i></span>

            </a>

        </div>


        <div class="col-md-4">

            <a href="<?php echo SITE_URL; ?>customer/rewards.php"
               class="action-card">

                <div class="action-icon">
                    <i class="bi bi-gift"></i>
                </div>

                <h5>Explore Rewards</h5>

                <p>Discover exclusive gifts and exciting rewards.</p>

                <span>View Rewards <i class="bi bi-arrow-right"></i></span>

            </a>

        </div>


        <div class="col-md-4">

            <a href="<?php echo SITE_URL; ?>customer/points-history.php"
               class="action-card">

                <div class="action-icon">
                    <i class="bi bi-clock-history"></i>
                </div>

                <h5>Points History</h5>

                <p>Check your previous points transactions.</p>

                <span>View History <i class="bi bi-arrow-right"></i></span>

            </a>

        </div>

    </div>


    <!-- Recent Transactions -->

    <div class="table-card">

        <div class="section-heading mb-4">

            <div>
                <h3>Recent Transactions</h3>
                <p>Your latest points activity</p>
            </div>

            <a href="<?php echo SITE_URL; ?>customer/points-history.php"
               class="view-all-btn">

                View All
                <i class="bi bi-arrow-right"></i>

            </a>

        </div>


        <div class="table-responsive">

            <table class="table">

                <thead>
                    <tr>
                        <th>TRANSACTION</th>
                        <th>DESCRIPTION</th>
                        <th>DATE</th>
                        <th>POINTS</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($transactions->num_rows > 0): ?>

                    <?php while ($row = $transactions->fetch_assoc()): ?>

                        <tr>

                            <td>
                                <?php if ($row['transaction_type'] === 'earned'): ?>

                                    <span class="transaction-type earned">
                                        <i class="bi bi-plus-circle"></i>
                                        Earned
                                    </span>

                                <?php elseif ($row['transaction_type'] === 'redeemed'): ?>

                                    <span class="transaction-type redeemed">
                                        <i class="bi bi-dash-circle"></i>
                                        Redeemed
                                    </span>

                                <?php else: ?>

                                    <span class="transaction-type adjustment">
                                        <i class="bi bi-arrow-left-right"></i>
                                        Adjustment
                                    </span>

                                <?php endif; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars(
                                    $row['description'] ?? ''
                                ); ?>
                            </td>

                            <td>
                                <?php echo date(
                                    "d M Y",
                                    strtotime($row['created_at'])
                                ); ?>
                            </td>

                            <td>

                                <strong class="<?php
                                    echo $row['transaction_type'] === 'redeemed'
                                        ? 'text-danger'
                                        : 'text-success';
                                ?>">

                                    <?php
                                    echo $row['transaction_type'] === 'redeemed'
                                        ? '-'
                                        : '+';

                                    echo number_format($row['points']);
                                    ?>

                                </strong>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="4">

                            <div class="empty-state">

                                <i class="bi bi-receipt"></i>

                                <h4>No Transactions Yet</h4>

                                <p>
                                    Verify your first product code
                                    to start earning points.
                                </p>

                            </div>

                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<?php

$stmt->close();

require_once __DIR__ . "/../includes/footer.php";

?>
