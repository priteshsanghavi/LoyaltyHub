
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

$pageTitle = "Add Product";

$productName = "";
$sku = "";
$description = "";
$pointsPerBox = "";
$status = "active";
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $productName = trim($_POST['product_name'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $pointsPerBox = (int)($_POST['points_per_box'] ?? 0);
    $status = $_POST['status'] ?? 'active';

    if ($productName === "" || $sku === "" || $pointsPerBox <= 0) {
        $error = "Please fill all required fields correctly.";
    } elseif (!in_array($status, ['active', 'inactive'], true)) {
        $error = "Invalid product status.";
    } else {

        $check = $conn->prepare(
            "SELECT id FROM products WHERE sku = ? LIMIT 1"
        );
        $check->bind_param("s", $sku);
        $check->execute();
        $existing = $check->get_result();

        if ($existing->num_rows > 0) {
            $error = "This SKU already exists.";
        } else {

            $stmt = $conn->prepare(
                "INSERT INTO products
                (product_name, sku, description, points_per_box, status)
                VALUES (?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "sssis",
                $productName,
                $sku,
                $description,
                $pointsPerBox,
                $status
            );

            if ($stmt->execute()) {
                header("Location: index.php?added=1");
                exit;
            } else {
                $error = "Product could not be added. " . $stmt->error;
            }

            $stmt->close();
        }

        $check->close();
    }
}

require_once "../../includes/header.php";
?>

<div class="container-fluid py-4">

    <div class="mb-4">
        <a href="index.php" class="text-decoration-none text-success">
            <i class="bi bi-arrow-left"></i> Back to Products
        </a>

        <h2 class="fw-bold mt-3">Add New Product</h2>
        <p class="text-muted">Create a product for your loyalty rewards program.</p>
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
                        Product Name *
                    </label>

                    <input type="text"
                           name="product_name"
                           class="form-control form-control-lg"
                           placeholder="Example: Premium Tea 500g"
                           value="<?= htmlspecialchars($productName) ?>"
                           required>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        Product SKU *
                    </label>

                    <input type="text"
                           name="sku"
                           class="form-control form-control-lg"
                           placeholder="Example: TEA-500"
                           value="<?= htmlspecialchars($sku) ?>"
                           required>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        Product Description
                    </label>

                    <textarea name="description"
                              class="form-control"
                              rows="4"
                              placeholder="Enter product details..."><?= htmlspecialchars($description) ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        Points Per Box *
                    </label>

                    <input type="number"
                           name="points_per_box"
                           class="form-control form-control-lg"
                           min="1"
                           placeholder="Example: 50"
                           value="<?= htmlspecialchars((string)$pointsPerBox) ?>"
                           required>

                    <small class="text-muted">
                        Customer earns these points when redeeming one valid product code.
                    </small>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        Product Status
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
                        <i class="bi bi-plus-circle"></i> Save Product
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
