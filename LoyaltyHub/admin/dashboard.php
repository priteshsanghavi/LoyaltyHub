
<?php
require_once "../config/config.php";
require_once "../config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . SITE_URL . "auth/login.php");
    exit;
}

function dashboardCount($conn, $sql) {
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return 0;
    }

    $row = mysqli_fetch_assoc($result);
    return (int)($row['total'] ?? 0);
}

// Main Statistics
$customers = dashboardCount($conn, "SELECT COUNT(*) AS total FROM users WHERE role='customer'");
$products = dashboardCount($conn, "SELECT COUNT(*) AS total FROM products");
$batches = dashboardCount($conn, "SELECT COUNT(*) AS total FROM product_batches");
$codes = dashboardCount($conn, "SELECT COUNT(*) AS total FROM product_codes");

// Code Statistics
$activeCodes = dashboardCount($conn, "SELECT COUNT(*) AS total FROM product_codes WHERE status='active'");
$redeemedCodes = dashboardCount($conn, "SELECT COUNT(*) AS total FROM product_codes WHERE status='redeemed'");
$blockedCodes = dashboardCount($conn, "SELECT COUNT(*) AS total FROM product_codes WHERE status='blocked'");

// Points Statistics
$earnedPoints = dashboardCount($conn, "SELECT COALESCE(SUM(points),0) AS total FROM points_transactions WHERE transaction_type='earned'");
$usedPoints = dashboardCount($conn, "SELECT COALESCE(SUM(points),0) AS total FROM points_transactions WHERE transaction_type='redeemed'");

// Reward Statistics
$rewards = dashboardCount($conn, "SELECT COUNT(*) AS total FROM rewards WHERE status='active'");

$redemptions = dashboardCount($conn, "SELECT COUNT(*) AS total FROM reward_redemptions");
$pendingRedemptions = dashboardCount($conn, "SELECT COUNT(*) AS total FROM reward_redemptions WHERE status='pending'");
$approvedRedemptions = dashboardCount($conn, "SELECT COUNT(*) AS total FROM reward_redemptions WHERE status='approved'");
$shippedRedemptions = dashboardCount($conn, "SELECT COUNT(*) AS total FROM reward_redemptions WHERE status='shipped'");
$rejectedRedemptions = dashboardCount($conn, "SELECT COUNT(*) AS total FROM reward_redemptions WHERE status='rejected'");

// Recent Redemptions
$recentRedemptions = mysqli_query($conn, "
    SELECT
        rr.id,
        rr.points_spent,
        rr.quantity,
        rr.status,
        rr.created_at,
        u.name AS customer_name,
        r.reward_name
    FROM reward_redemptions rr
    INNER JOIN users u ON rr.user_id = u.id
    INNER JOIN rewards r ON rr.reward_id = r.id
    ORDER BY rr.created_at DESC
    LIMIT 5
");

// Analytics
$activePercent = $codes > 0 ? round(($activeCodes / $codes) * 100) : 0;
$redeemedPercent = $codes > 0 ? round(($redeemedCodes / $codes) * 100) : 0;
$blockedPercent = $codes > 0 ? round(($blockedCodes / $codes) * 100) : 0;

require_once "../includes/header.php";
?>

<style>
body {
    background: #f4f7f6;
}

.lh-dashboard {
    padding: 32px 0 60px;
    color: #193c32;
}

/* HERO */
.lh-welcome {
    position: relative;
    overflow: hidden;
    background: linear-gradient(120deg,#103d32,#087653,#13a477);
    border-radius: 25px;
    padding: 35px 38px;
    color: white;
    margin-bottom: 30px;
    box-shadow: 0 18px 40px rgba(8,91,64,.20);
}

.lh-welcome:before,
.lh-welcome:after {
    content: "";
    position: absolute;
    border-radius: 50%;
    background: rgba(255,255,255,.07);
}

.lh-welcome:before {
    width: 300px;
    height: 300px;
    right: 5%;
    top: -180px;
}

.lh-welcome:after {
    width: 230px;
    height: 230px;
    right: 20%;
    bottom: -180px;
}

.welcome-content,
.welcome-side {
    position: relative;
    z-index: 2;
}

.welcome-label {
    display: inline-flex;
    gap: 7px;
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.18);
    padding: 7px 13px;
    border-radius: 30px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 1.5px;
    margin-bottom: 16px;
}

.lh-welcome h2 {
    color: white;
    font-size: clamp(25px,4vw,36px);
    font-weight: 850;
    margin-bottom: 9px;
}

.lh-welcome p {
    color: rgba(255,255,255,.75);
    margin: 0;
    font-size: 14px;
}

.welcome-side {
    text-align: right;
}

.welcome-icon {
    width: 100px;
    height: 100px;
    display: inline-grid;
    place-items: center;
    border-radius: 28px;
    background: rgba(255,255,255,.13);
    border: 1px solid rgba(255,255,255,.2);
    font-size: 45px;
    transform: rotate(-8deg);
}

/* STAT CARDS */
.lh-stat {
    background: #fff;
    border: 1px solid #e9efeb;
    border-radius: 19px;
    padding: 22px;
    height: 100%;
    transition: .3s ease;
    box-shadow: 0 5px 20px rgba(20,60,45,.035);
}

.lh-stat:hover {
    transform: translateY(-5px);
    box-shadow: 0 17px 35px rgba(20,60,45,.10);
    border-color: #c9e5d6;
}

.lh-stat-top {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 22px;
}

.lh-stat-icon {
    width: 49px;
    height: 49px;
    display: grid;
    place-items: center;
    border-radius: 15px;
    font-size: 21px;
}

.lh-stat small {
    color: #89968f;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .5px;
    text-transform: uppercase;
}

.lh-stat h3 {
    color: #173e32;
    font-size: 30px;
    font-weight: 850;
    margin: 5px 0 0;
}

.lh-stat-footer {
    color: #9aa59f;
    font-size: 12px;
    margin-top: 7px;
}

.stat-green .lh-stat-icon {background:#e3f5eb;color:#098353;}
.stat-blue .lh-stat-icon {background:#eaf1ff;color:#4775db;}
.stat-orange .lh-stat-icon {background:#fff1df;color:#e7952c;}
.stat-purple .lh-stat-icon {background:#f2eaff;color:#8855cf;}
.stat-red .lh-stat-icon {background:#ffebeb;color:#dc5656;}
.stat-teal .lh-stat-icon {background:#e1f6f4;color:#139b91;}

/* PANELS */
.lh-panel {
    background: #fff;
    border: 1px solid #e9efeb;
    border-radius: 21px;
    padding: 25px;
    height: 100%;
    box-shadow: 0 5px 20px rgba(20,60,45,.035);
}

.lh-panel-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 24px;
}

.lh-panel-heading h5 {
    color: #173e32;
    font-size: 17px;
    font-weight: 850;
    margin: 0;
}

.lh-panel-heading p {
    color: #9aa59f;
    font-size: 12px;
    margin: 5px 0 0;
}

.lh-panel-icon {
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    background: #e9f7ef;
    color: #078253;
    border-radius: 13px;
    font-size: 19px;
}

/* ANALYTICS */
.analytics-item {
    margin-bottom: 22px;
}

.analytics-info {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 9px;
    font-size: 13px;
}

.analytics-info span {
    color: #65766d;
    font-weight: 600;
}

.analytics-info strong {
    color: #193c32;
}

.lh-progress {
    height: 10px;
    background: #edf2ee;
    border-radius: 20px;
    overflow: hidden;
}

.lh-progress-bar {
    height: 100%;
    border-radius: 20px;
}

.bar-active {background:linear-gradient(90deg,#08764d,#37c18c);}
.bar-redeemed {background:linear-gradient(90deg,#4775db,#82a8ff);}
.bar-blocked {background:linear-gradient(90deg,#db5555,#ff9999);}

/* POINTS */
.points-box {
    background: linear-gradient(120deg,#142f29,#205747);
    color: #fff;
    border-radius: 19px;
    padding: 24px;
    margin-top: 24px;
}

.points-box small,
.points-sub {
    color: rgba(255,255,255,.65);
}

.points-box h3 {
    font-size: 29px;
    font-weight: 850;
    margin: 8px 0;
}

/* QUICK ACTIONS */
.lh-action {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 15px;
    border: 1px solid #edf1ed;
    border-radius: 15px;
    margin-bottom: 12px;
    text-decoration: none;
    transition: .25s ease;
    background: #fff;
}

.lh-action:hover {
    background: #f2faf5;
    border-color: #bde2cb;
    transform: translateX(5px);
}

.lh-action-icon {
    width: 46px;
    height: 46px;
    flex-shrink: 0;
    display: grid;
    place-items: center;
    background: #e6f5ec;
    color: #08764d;
    border-radius: 13px;
    font-size: 20px;
}

.lh-action-text strong {
    display: block;
    color: #24483b;
    font-size: 13px;
    font-weight: 800;
}

.lh-action-text small {
    display: block;
    color: #929d96;
    font-size: 11px;
    margin-top: 3px;
}

.lh-action-arrow {
    margin-left: auto;
    color: #9ca9a1;
}

/* RECENT TABLE */
.lh-table {
    min-width: 700px;
}

.lh-table thead th {
    background: #f5faf6;
    color: #718078;
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .5px;
    padding: 15px 12px;
    border-bottom: 1px solid #e8f0ea;
    white-space: nowrap;
}

.lh-table tbody td {
    padding: 14px 12px;
    border-bottom: 1px solid #f0f3f0;
    vertical-align: middle;
    font-size: 13px;
}

.lh-table tbody tr:last-child td {
    border-bottom: 0;
}

.customer-avatar {
    width: 38px;
    height: 38px;
    flex-shrink: 0;
    border-radius: 12px;
    background: #e5f5eb;
    color: #08764d;
    display: grid;
    place-items: center;
    font-weight: 800;
}

.lh-bottom-note {
    color: #99a69f;
    text-align: center;
    font-size: 12px;
    padding-top: 28px;
}

@media(max-width:767px) {
    .lh-welcome {padding:27px 22px;}
    .welcome-side {display:none;}
    .lh-panel {padding:19px;}
    .lh-stat h3 {font-size:26px;}
}
</style>

<div class="container-fluid px-3 px-lg-4 lh-dashboard">

    <!-- WELCOME -->
    <section class="lh-welcome">
        <div class="row align-items-center">

            <div class="col-md-9 welcome-content">
                <div class="welcome-label">
                    <i class="bi bi-stars"></i>
                    LOYALTYHUB ADMIN PANEL
                </div>

                <h2>
                    Welcome back,
                    <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin') ?>
                    👋
                </h2>

                <p>
                    Manage your loyalty program, monitor customer activity
                    and grow your rewards community.
                </p>
            </div>

            <div class="col-md-3 welcome-side">
                <div class="welcome-icon">
                    <i class="bi bi-gem"></i>
                </div>
            </div>

        </div>
    </section>

    <!-- BUSINESS OVERVIEW -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h5 class="fw-bold mb-1">Business Overview</h5>
            <small class="text-muted">Your platform at a glance</small>
        </div>

        <span class="badge rounded-pill px-3 py-2"
              style="background:#e3f5eb;color:#08764d;">
            <i class="bi bi-circle-fill me-1" style="font-size:7px;"></i>
            Live Overview
        </span>
    </div>

    <div class="row g-3 mb-4">

        <div class="col-6 col-xl-3">
            <div class="lh-stat stat-green">
                <div class="lh-stat-top">
                    <div class="lh-stat-icon"><i class="bi bi-people-fill"></i></div>
                </div>
                <small>Total Customers</small>
                <h3><?= number_format($customers) ?></h3>
                <div class="lh-stat-footer">Registered members</div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="lh-stat stat-blue">
                <div class="lh-stat-top">
                    <div class="lh-stat-icon"><i class="bi bi-box-seam"></i></div>
                </div>
                <small>Total Products</small>
                <h3><?= number_format($products) ?></h3>
                <div class="lh-stat-footer">Products in system</div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="lh-stat stat-orange">
                <div class="lh-stat-top">
                    <div class="lh-stat-icon"><i class="bi bi-collection"></i></div>
                </div>
                <small>Total Batches</small>
                <h3><?= number_format($batches) ?></h3>
                <div class="lh-stat-footer">Created product batches</div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="lh-stat stat-purple">
                <div class="lh-stat-top">
                    <div class="lh-stat-icon"><i class="bi bi-qr-code"></i></div>
                </div>
                <small>Generated Codes</small>
                <h3><?= number_format($codes) ?></h3>
                <div class="lh-stat-footer">Total product codes</div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="lh-stat stat-teal">
                <div class="lh-stat-top">
                    <div class="lh-stat-icon"><i class="bi bi-check-circle-fill"></i></div>
                </div>
                <small>Active Codes</small>
                <h3><?= number_format($activeCodes) ?></h3>
                <div class="lh-stat-footer">Available to redeem</div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="lh-stat stat-green">
                <div class="lh-stat-top">
                    <div class="lh-stat-icon"><i class="bi bi-patch-check-fill"></i></div>
                </div>
                <small>Redeemed Codes</small>
                <h3><?= number_format($redeemedCodes) ?></h3>
                <div class="lh-stat-footer">Successfully redeemed</div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="lh-stat stat-red">
                <div class="lh-stat-top">
                    <div class="lh-stat-icon"><i class="bi bi-shield-lock-fill"></i></div>
                </div>
                <small>Blocked Codes</small>
                <h3><?= number_format($blockedCodes) ?></h3>
                <div class="lh-stat-footer">Blocked product codes</div>
            </div>
        </div>

        <div class="col-6 col-xl-3">
            <div class="lh-stat stat-orange">
                <div class="lh-stat-top">
                    <div class="lh-stat-icon"><i class="bi bi-gift-fill"></i></div>
                </div>
                <small>Active Rewards</small>
                <h3><?= number_format($rewards) ?></h3>
                <div class="lh-stat-footer">Available reward options</div>
            </div>
        </div>

    </div>

    <!-- REDEMPTION OVERVIEW -->
    <div class="d-flex align-items-center justify-content-between mb-3 mt-4">
        <div>
            <h5 class="fw-bold mb-1">Reward Redemption Overview</h5>
            <small class="text-muted">Track customer reward requests</small>
        </div>

        <a href="<?= SITE_URL ?>admin/redemptions/index.php"
           class="btn btn-sm btn-outline-success rounded-3">
            View All <i class="bi bi-arrow-right"></i>
        </a>
    </div>

    <div class="row g-3 mb-4">

        <div class="col-6 col-xl">
            <div class="lh-stat stat-purple">
                <div class="lh-stat-top">
                    <div class="lh-stat-icon"><i class="bi bi-gift-fill"></i></div>
                </div>
                <small>Total Requests</small>
                <h3><?= number_format($redemptions) ?></h3>
                <div class="lh-stat-footer">All requests</div>
            </div>
        </div>

        <div class="col-6 col-xl">
            <div class="lh-stat stat-orange">
                <div class="lh-stat-top">
                    <div class="lh-stat-icon"><i class="bi bi-hourglass-split"></i></div>
                </div>
                <small>Pending</small>
                <h3><?= number_format($pendingRedemptions) ?></h3>
                <div class="lh-stat-footer">Waiting for approval</div>
            </div>
        </div>

        <div class="col-6 col-xl">
            <div class="lh-stat stat-blue">
                <div class="lh-stat-top">
                    <div class="lh-stat-icon"><i class="bi bi-check-circle"></i></div>
                </div>
                <small>Approved</small>
                <h3><?= number_format($approvedRedemptions) ?></h3>
                <div class="lh-stat-footer">Approved requests</div>
            </div>
        </div>

        <div class="col-6 col-xl">
            <div class="lh-stat stat-green">
                <div class="lh-stat-top">
                    <div class="lh-stat-icon"><i class="bi bi-truck"></i></div>
                </div>
                <small>Shipped</small>
                <h3><?= number_format($shippedRedemptions) ?></h3>
                <div class="lh-stat-footer">Rewards dispatched</div>
            </div>
        </div>

        <div class="col-6 col-xl">
            <div class="lh-stat stat-red">
                <div class="lh-stat-top">
                    <div class="lh-stat-icon"><i class="bi bi-x-circle"></i></div>
                </div>
                <small>Rejected</small>
                <h3><?= number_format($rejectedRedemptions) ?></h3>
                <div class="lh-stat-footer">Rejected requests</div>
            </div>
        </div>

    </div>

    <!-- RECENT REDEMPTIONS -->
    <div class="lh-panel mb-4">

        <div class="lh-panel-heading">
            <div>
                <h5>Recent Redemptions</h5>
                <p>Latest reward requests from customers</p>
            </div>

            <a href="<?= SITE_URL ?>admin/redemptions/index.php"
               class="btn btn-sm btn-outline-success rounded-3">
                View All <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <div class="table-responsive">
            <table class="table lh-table align-middle mb-0">

                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Reward</th>
                        <th>Points</th>
                        <th>Quantity</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($recentRedemptions && mysqli_num_rows($recentRedemptions) > 0): ?>

                    <?php while ($row = mysqli_fetch_assoc($recentRedemptions)): ?>

                        <?php
                        $status = strtolower($row['status']);

                        $badgeClass = [
                            'pending' => 'warning',
                            'approved' => 'primary',
                            'shipped' => 'success',
                            'rejected' => 'danger'
                        ][$status] ?? 'secondary';
                        ?>

                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="customer-avatar">
                                        <?= htmlspecialchars(strtoupper(substr($row['customer_name'], 0, 1))) ?>
                                    </div>

                                    <strong style="color:#24483b;">
                                        <?= htmlspecialchars($row['customer_name']) ?>
                                    </strong>
                                </div>
                            </td>

                            <td>
                                <strong>
                                    <?= htmlspecialchars($row['reward_name']) ?>
                                </strong>
                            </td>

                            <td>
                                <strong style="color:#08764d;">
                                    <?= number_format($row['points_spent']) ?> pts
                                </strong>
                            </td>

                            <td><?= (int)$row['quantity'] ?></td>

                            <td>
                                <small class="text-muted">
                                    <?= date('d M Y', strtotime($row['created_at'])) ?>
                                </small>
                            </td>

                            <td>
                                <span class="badge bg-<?= $badgeClass ?>-subtle text-<?= $badgeClass ?> rounded-pill px-3 py-2">
                                    <?= ucfirst($status) ?>
                                </span>
                            </td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <i class="bi bi-gift" style="font-size:35px;color:#aab8af;"></i>
                            <p class="text-muted mt-2 mb-0">
                                No redemption requests found.
                            </p>
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>
            </table>
        </div>
    </div>

    <!-- ANALYTICS + QUICK ACTIONS -->
    <div class="row g-4">

        <!-- ANALYTICS -->
        <div class="col-lg-7">
            <div class="lh-panel">

                <div class="lh-panel-heading">
                    <div>
                        <h5>Product Code Analytics</h5>
                        <p>Current status of all generated codes</p>
                    </div>

                    <div class="lh-panel-icon">
                        <i class="bi bi-bar-chart-line-fill"></i>
                    </div>
                </div>

                <div class="analytics-item">
                    <div class="analytics-info">
                        <span>
                            <i class="bi bi-circle-fill text-success me-2" style="font-size:8px;"></i>
                            Active Codes
                        </span>
                        <strong><?= number_format($activeCodes) ?> · <?= $activePercent ?>%</strong>
                    </div>

                    <div class="lh-progress">
                        <div class="lh-progress-bar bar-active"
                             style="width:<?= $activePercent ?>%"></div>
                    </div>
                </div>

                <div class="analytics-item">
                    <div class="analytics-info">
                        <span>
                            <i class="bi bi-circle-fill text-primary me-2" style="font-size:8px;"></i>
                            Redeemed Codes
                        </span>
                        <strong><?= number_format($redeemedCodes) ?> · <?= $redeemedPercent ?>%</strong>
                    </div>

                    <div class="lh-progress">
                        <div class="lh-progress-bar bar-redeemed"
                             style="width:<?= $redeemedPercent ?>%"></div>
                    </div>
                </div>

                <div class="analytics-item">
                    <div class="analytics-info">
                        <span>
                            <i class="bi bi-circle-fill text-danger me-2" style="font-size:8px;"></i>
                            Blocked Codes
                        </span>
                        <strong><?= number_format($blockedCodes) ?> · <?= $blockedPercent ?>%</strong>
                    </div>

                    <div class="lh-progress">
                        <div class="lh-progress-bar bar-blocked"
                             style="width:<?= $blockedPercent ?>%"></div>
                    </div>
                </div>

                <div class="points-box">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small>
                                <i class="bi bi-stars me-1"></i>
                                Total Points Earned
                            </small>

                            <h3><?= number_format($earnedPoints) ?></h3>

                            <div class="points-sub">
                                Points distributed to customers
                            </div>
                        </div>

                        <i class="bi bi-trophy"
                           style="font-size:48px;color:#b5e8c9;"></i>
                    </div>

                    <hr style="border-color:rgba(255,255,255,.2);">

                    <div class="d-flex justify-content-between">
                        <span class="points-sub">Points Redeemed</span>
                        <strong><?= number_format($usedPoints) ?></strong>
                    </div>
                </div>

            </div>
        </div>

        <!-- QUICK ACTIONS -->
        <div class="col-lg-5">
            <div class="lh-panel">

                <div class="lh-panel-heading">
                    <div>
                        <h5>Quick Actions</h5>
                        <p>Manage your loyalty platform</p>
                    </div>

                    <div class="lh-panel-icon">
                        <i class="bi bi-lightning-charge-fill"></i>
                    </div>
                </div>

                <a href="<?= SITE_URL ?>admin/batches/index.php" class="lh-action">
                    <div class="lh-action-icon">
                        <i class="bi bi-collection"></i>
                    </div>
                    <div class="lh-action-text">
                        <strong>Manage Batches</strong>
                        <small>View all product batches</small>
                    </div>
                    <i class="bi bi-chevron-right lh-action-arrow"></i>
                </a>

                <a href="<?= SITE_URL ?>admin/batches/add.php" class="lh-action">
                    <div class="lh-action-icon">
                        <i class="bi bi-plus-circle"></i>
                    </div>
                    <div class="lh-action-text">
                        <strong>Create New Batch</strong>
                        <small>Add a new product batch</small>
                    </div>
                    <i class="bi bi-chevron-right lh-action-arrow"></i>
                </a>

                <a href="<?= SITE_URL ?>admin/codes/generate.php" class="lh-action">
                    <div class="lh-action-icon">
                        <i class="bi bi-qr-code-scan"></i>
                    </div>
                    <div class="lh-action-text">
                        <strong>Generate Product Codes</strong>
                        <small>Create secure unique codes</small>
                    </div>
                    <i class="bi bi-chevron-right lh-action-arrow"></i>
                </a>

                <a href="<?= SITE_URL ?>admin/codes/index.php" class="lh-action">
                    <div class="lh-action-icon">
                        <i class="bi bi-upc"></i>
                    </div>
                    <div class="lh-action-text">
                        <strong>View All Codes</strong>
                        <small>Monitor code status</small>
                    </div>
                    <i class="bi bi-chevron-right lh-action-arrow"></i>
                </a>

                <a href="<?= SITE_URL ?>admin/redemptions/index.php" class="lh-action">
                    <div class="lh-action-icon">
                        <i class="bi bi-gift-fill"></i>
                    </div>
                    <div class="lh-action-text">
                        <strong>Manage Redemptions</strong>
                        <small>Approve and manage customer requests</small>
                    </div>
                    <i class="bi bi-chevron-right lh-action-arrow"></i>
                </a>

                <div class="mt-4 p-3 rounded-4"
                     style="background:#f3faf5;border:1px solid #e5f1e8;">

                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-gift-fill text-success fs-4"></i>

                        <div>
                            <strong style="font-size:13px;color:#24483b;">
                                Reward Redemptions
                            </strong>

                            <div class="text-muted" style="font-size:12px;">
                                <?= number_format($redemptions) ?> total redemption requests
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <div class="lh-bottom-note">
        <i class="bi bi-shield-check me-1"></i>
        LoyaltyHub Admin Panel · Secure Loyalty Management System
    </div>

</div>

<?php require_once "../includes/footer.php"; ?>
