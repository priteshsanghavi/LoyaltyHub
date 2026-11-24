
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

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $code = strtoupper(trim($_POST['product_code'] ?? ''));

    if ($code === "") {
        $message = "Please enter your product code.";
        $messageType = "danger";
    } else {

        // Hash the entered code before database lookup
        $codeHash = hash('sha256', $code);

        mysqli_begin_transaction($conn);

        try {

            // Lock the code row to prevent duplicate redemption
            $stmt = mysqli_prepare(
                $conn,
                "SELECT id, status FROM product_codes
                 WHERE code_hash = ? FOR UPDATE"
            );

            mysqli_stmt_bind_param($stmt, "s", $codeHash);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $codeData = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if (!$codeData) {
                throw new Exception("Invalid product code.");
            }

            if ($codeData['status'] !== 'active') {
                throw new Exception("This code has already been used or is blocked.");
            }

            $codeId = (int) $codeData['id'];

            // Get points assigned to this product code
            $stmt = mysqli_prepare(
                $conn,
                "SELECT p.points_per_box
                 FROM product_codes pc
                 JOIN product_batches pb ON pc.batch_id = pb.id
                 JOIN products p ON pb.product_id = p.id
                 WHERE pc.id = ?"
            );

            mysqli_stmt_bind_param($stmt, "i", $codeId);
            mysqli_stmt_execute($stmt);

            $productResult = mysqli_stmt_get_result($stmt);
            $product = mysqli_fetch_assoc($productResult);
            mysqli_stmt_close($stmt);

            if (!$product) {
                throw new Exception("Product information not found.");
            }

            $points = (int) $product['points_per_box'];

            if ($points <= 0) {
                throw new Exception("This product has no valid points assigned.");
            }

            // Mark code as redeemed
            $stmt = mysqli_prepare(
                $conn,
                "UPDATE product_codes
                 SET status = 'redeemed',
                     redeemed_by = ?,
                     redeemed_at = NOW()
                 WHERE id = ? AND status = 'active'"
            );

            mysqli_stmt_bind_param($stmt, "ii", $userId, $codeId);
            mysqli_stmt_execute($stmt);

            if (mysqli_stmt_affected_rows($stmt) !== 1) {
                throw new Exception("This code has already been redeemed.");
            }

            mysqli_stmt_close($stmt);

            // Add points transaction
            $description = "Points earned by redeeming product code";

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO points_transactions
                 (user_id, code_id, transaction_type, points, description)
                 VALUES (?, ?, 'earned', ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "iiis",
                $userId,
                $codeId,
                $points,
                $description
            );

            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            mysqli_commit($conn);

            $message = "Congratulations! You earned " . $points . " points.";
            $messageType = "success";

        } catch (Exception $e) {

            mysqli_rollback($conn);

            $message = $e->getMessage();
            $messageType = "danger";
        }
    }
}

require_once "../includes/header.php";
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">

            <div class="dashboard-card text-center p-4 p-md-5">

                <div class="mb-3">
                    <i class="bi bi-gift-fill text-success"
                       style="font-size:55px;"></i>
                </div>

                <h2 class="fw-bold mb-2">Redeem Your Product Code</h2>

                <p class="text-muted mb-4">
                    Enter the unique code printed inside your product box
                    and earn loyalty points.
                </p>

                <?php if ($message !== ""): ?>
                    <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <form method="POST">

                    <div class="mb-4 text-start">
                        <label class="form-label fw-semibold">
                            Product Code
                        </label>

                        <input
                            type="text"
                            name="product_code"
                            class="form-control form-control-lg text-center"
                            placeholder="ENTER YOUR PRODUCT CODE"
                            maxlength="100"
                            required
                            autocomplete="off"
                        >
                    </div>

                    <button type="submit"
                            class="btn btn-success btn-lg w-100">
                        <i class="bi bi-stars me-2"></i>
                        Redeem & Earn Points
                    </button>

                </form>

                <div class="mt-4 text-muted small">
                    <i class="bi bi-shield-check me-1"></i>
                    Every product code can be redeemed only once.
                </div>

            </div>

        </div>
    </div>
</div>

<?php require_once "../includes/footer.php"; ?>
