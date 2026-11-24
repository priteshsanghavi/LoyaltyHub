
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

$pageTitle = "Add Reward";

$rewardName = "";
$description = "";
$pointsRequired = "";
$stock = "";
$status = "active";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $rewardName = trim($_POST['reward_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $pointsRequired = (int)($_POST['points_required'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $status = $_POST['status'] ?? 'active';

    if ($rewardName === "" || $pointsRequired <= 0 || $stock < 0) {
        $error = "Please enter valid reward details.";
    } elseif (!in_array($status, ['active', 'inactive'], true)) {
        $error = "Invalid reward status.";
    } else {

        $stmt = $conn->prepare(
            "INSERT INTO rewards
            (reward_name, description, points_required, stock, status)
            VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "ssiis",
            $rewardName,
            $description,
            $pointsRequired,
            $stock,
            $status
        );

        if ($stmt->execute()) {
            header("Location: index.php?added=1");
            exit;
        } else {
            $error = "Reward could not be added: " . $stmt->error;
        }

        $stmt->close();
    }
}

require_once "../../includes/header.php";
?>

<div class="container-fluid py-4">

    <div class="mb-4">
        <a href="index.php" class="text-decoration-none text-success">
            <i class="bi bi-arrow-left"></i> Back to Rewards
        </a>

        <h2 class="fw-bold mt-3">Add New Reward</h2>
        <p class="text-muted">Create a new reward for your customers.</p>
    </div>

    <div class="card border-0 shadow-sm rounded-4" style="max-width:850px;">
        <div class="card-body p-4 p-md-5">

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        Reward Name *
                    </label>

                    <input type="text"
                           name="reward_name"
                           class="form-control form-control-lg"
                           placeholder="Example: Amazon Gift Voucher"
                           value="<?= htmlspecialchars($rewardName) ?>"
                           required>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        Description
                    </label>

                    <textarea name="description"
                              class="form-control"
                              rows="4"
                              placeholder="Enter reward details..."><?= htmlspecialchars($description) ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        Required Points *
                    </label>

                    <input type="number"
                           name="points_required"
                           class="form-control form-control-lg"
                           min="1"
                           placeholder="Example: 500"
                           value="<?= htmlspecialchars((string)$pointsRequired) ?>"
                           required>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        Available Stock *
                    </label>

                    <input type="number"
                           name="stock"
                           class="form-control form-control-lg"
                           min="0"
                           placeholder="Example: 20"
                           value="<?= htmlspecialchars((string)$stock) ?>"
                           required>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        Reward Status
                    </label>

                    <select name="status" class="form-select form-select-lg">
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>
                            Active
                        </option>

                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>
                            Inactive
                        </option>
                    </select>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-success btn-lg px-4">
                        <i class="bi bi-plus-circle"></i> Save Reward
                    </button>

                    <a href="index.php" class="btn btn-light btn-lg px-4">
                        Cancel
                    </a>
                </div>

            </form>

        </div>
    </div>
</div>

<?php require_once "../../includes/footer.php"; ?>
