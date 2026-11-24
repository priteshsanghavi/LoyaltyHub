
<?php
require_once "../../config/config.php";
require_once "../../config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: " . SITE_URL . "auth/login.php");
    exit;
}

$pageTitle = "Add Product Batch";
$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $batchNumber = trim($_POST['batch_number'] ?? '');
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    $manufacturedDate = $_POST['manufactured_date'] ?? '';

    if ($batchNumber === '' || $productId <= 0 || $quantity <= 0) {
        $error = "Please fill all required fields correctly.";
    } else {

        $check = $conn->prepare(
            "SELECT id FROM products WHERE id = ? AND status = 'active'"
        );
        $check->bind_param("i", $productId);
        $check->execute();
        $product = $check->get_result()->fetch_assoc();
        $check->close();

        if (!$product) {
            $error = "Please select a valid active product.";
        } else {

            $stmt = $conn->prepare("
                INSERT INTO product_batches
                (batch_number, product_id, quantity, manufactured_date)
                VALUES (?, ?, ?, ?)
            ");

            $dateValue = $manufacturedDate !== '' ? $manufacturedDate : null;

            $stmt->bind_param(
                "siis",
                $batchNumber,
                $productId,
                $quantity,
                $dateValue
            );

            if ($stmt->execute()) {
                $success = "Product batch created successfully!";
            } else {
                $error = $conn->errno == 1062
                    ? "This batch number already exists."
                    : "Unable to create batch. Please try again.";
            }

            $stmt->close();
        }
    }
}

$products = $conn->query("
    SELECT id, product_name, points_per_box
    FROM products
    WHERE status = 'active'
    ORDER BY product_name
");

require_once "../../includes/header.php";
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Add Product Batch</h2>
            <p class="text-muted mb-0">Create a new manufacturing batch.</p>
        </div>

        <a href="<?= SITE_URL ?>admin/codes/generate.php"
           class="btn btn-outline-success">
            <i class="bi bi-arrow-left me-2"></i> Generate Codes
        </a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-7">

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">

                    <h5 class="fw-bold mb-4">
                        <i class="bi bi-box-seam text-success me-2"></i>
                        Batch Information
                    </h5>

                    <form method="POST">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Batch Number
                            </label>
                            <input type="text"
                                   name="batch_number"
                                   class="form-control"
                                   placeholder="Example: BATCH-2026-001"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Select Product
                            </label>

                            <select name="product_id"
                                    class="form-select"
                                    required>
                                <option value="">Choose Product</option>

                                <?php if ($products): ?>
                                    <?php while ($p = $products->fetch_assoc()): ?>
                                        <option value="<?= (int)$p['id'] ?>">
                                            <?= htmlspecialchars($p['product_name']) ?>
                                            (<?= (int)$p['points_per_box'] ?> Points)
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Total Quantity (Boxes)
                            </label>
                            <input type="number"
                                   name="quantity"
                                   class="form-control"
                                   min="1"
                                   placeholder="Example: 100"
                                   required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">
                                Manufactured Date
                            </label>
                            <input type="date"
                                   name="manufactured_date"
                                   class="form-control">
                        </div>

                        <button type="submit" class="btn btn-success px-4">
                            <i class="bi bi-plus-circle me-2"></i>
                            Create Batch
                        </button>

                    </form>

                </div>
            </div>

        </div>
    </div>

</div>

<?php require_once "../../includes/footer.php"; ?>
