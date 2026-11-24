
<?php
$pageTitle = "Welcome";
require_once "includes/header.php";
?>

<div class="container page-container">

    <div class="page-heading">
        <h1>Welcome to LoyaltyHub 🎁</h1>
        <p>Your purchases deserve exciting rewards.</p>
    </div>

    <div class="row g-4">

        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-gift"></i>
                </div>
                <h3>Earn Points</h3>
                <p>Enter your product code and collect points.</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-stars"></i>
                </div>
                <h3>Exclusive Rewards</h3>
                <p>Discover exciting gifts and rewards.</p>
            </div>
        </div>

        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-wallet2"></i>
                </div>
                <h3>Redeem Points</h3>
                <p>Use your collected points to claim rewards.</p>
            </div>
        </div>

    </div>

</div>

<?php require_once "includes/footer.php"; ?>
