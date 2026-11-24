
<?php
require_once "../../config/config.php";
require_once "../../config/database.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Admin Authentication
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: " . SITE_URL . "auth/login.php");
    exit;
}

$pageTitle = "Generate Product Codes";

$success = "";
$error = "";
$generatedCodes = [];

// Fetch Active Products
$products = $conn->query("
    SELECT id, product_name, points_per_box
    FROM products
    WHERE status = 'active'
    ORDER BY product_name ASC
");

// Fetch Batches
$batches = $conn->query("
    SELECT b.id, b.batch_number, b.product_id,
           b.quantity, p.product_name
    FROM product_batches b
    INNER JOIN products p ON p.id = b.product_id
    ORDER BY b.id DESC
");

// Generate Codes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $productId = (int)($_POST['product_id'] ?? 0);
    $batchId = (int)($_POST['batch_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);

    if ($productId <= 0 || $batchId <= 0) {
        $error = "Please select a product and batch.";
    } elseif ($quantity < 1 || $quantity > 500) {
        $error = "Please generate between 1 and 500 codes.";
    } else {

        try {
            $conn->begin_transaction();

            // Lock selected batch
            $check = $conn->prepare("
                SELECT id, quantity
                FROM product_batches
                WHERE id = ? AND product_id = ?
                FOR UPDATE
            ");

            $check->bind_param("ii", $batchId, $productId);
            $check->execute();

            $batch = $check->get_result()->fetch_assoc();
            $check->close();

            if (!$batch) {
                throw new Exception("Invalid product batch selected.");
            }

            // Count already generated codes
            $countStmt = $conn->prepare("
                SELECT COUNT(*) AS total
                FROM product_codes
                WHERE batch_id = ?
            ");

            $countStmt->bind_param("i", $batchId);
            $countStmt->execute();

            $countResult = $countStmt->get_result()->fetch_assoc();
            $countStmt->close();

            $alreadyGenerated = (int)$countResult['total'];
            $remaining = (int)$batch['quantity'] - $alreadyGenerated;

            if ($remaining <= 0) {
                throw new Exception("All codes for this batch have already been generated.");
            }

            if ($quantity > $remaining) {
                throw new Exception(
                    "Only " . $remaining . " codes can be generated for this batch."
                );
            }

            // Insert generated codes
            $insert = $conn->prepare("
                INSERT INTO product_codes
                (product_id, batch_id, code_hash, code_last4, status)
                VALUES (?, ?, ?, ?, 'active')
            ");

            for ($i = 0; $i < $quantity; $i++) {

                // Generate secure random 16-character code
                $plainCode = strtoupper(bin2hex(random_bytes(8)));

                // Store only SHA-256 hash
                $codeHash = hash('sha256', $plainCode);
                $last4 = substr($plainCode, -4);

                $insert->bind_param(
                    "iiss",
                    $productId,
                    $batchId,
                    $codeHash,
                    $last4
                );

                $insert->execute();

                // Keep plain code only for one-time display
                $generatedCodes[] = $plainCode;
            }

            $insert->close();

            $conn->commit();

            $success = count($generatedCodes) .
                " product codes generated successfully!";

        } catch (Throwable $e) {

            $conn->rollback();

            $generatedCodes = [];
            $error = $e->getMessage();
        }
    }
}

require_once "../../includes/header.php";
?>

<div class="container-fluid py-4">

    <!-- Page Heading -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

        <div>
            <h2 class="fw-bold mb-1">Generate Product Codes</h2>
            <p class="text-muted mb-0">
                Create unique loyalty redemption codes for your products.
            </p>
        </div>

        <a href="<?= SITE_URL ?>admin/codes/index.php"
           class="btn btn-outline-success">
            <i class="bi bi-arrow-left me-2"></i>
            All Product Codes
        </a>

    </div>

    <!-- Alerts -->
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close"
                    data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-circle-fill me-2"></i>
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close"
                    data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- Generation Form -->
        <div class="col-lg-7">

            <div class="card border-0 shadow-sm rounded-4">

                <div class="card-body p-4">

                    <h5 class="fw-bold mb-4">
                        <i class="bi bi-qr-code text-success me-2"></i>
                        Generation Details
                    </h5>

                    <form method="POST">

                        <div class="mb-3">

                            <label class="form-label fw-semibold">
                                Select Product
                            </label>

                            <select name="product_id"
                                    id="product_id"
                                    class="form-select form-select-lg"
                                    required>

                                <option value="">Choose Product</option>

                                <?php if ($products): ?>
                                    <?php while ($product = $products->fetch_assoc()): ?>

                                        <option value="<?= (int)$product['id'] ?>">
                                            <?= htmlspecialchars($product['product_name']) ?>
                                            - <?= (int)$product['points_per_box'] ?> Points
                                        </option>

                                    <?php endwhile; ?>
                                <?php endif; ?>

                            </select>

                        </div>

                        <div class="mb-3">

                            <label class="form-label fw-semibold">
                                Select Product Batch
                            </label>

                            <select name="batch_id"
                                    id="batch_id"
                                    class="form-select form-select-lg"
                                    required>

                                <option value="">Choose Batch</option>

                                <?php if ($batches): ?>
                                    <?php while ($batch = $batches->fetch_assoc()): ?>

                                        <option
                                            value="<?= (int)$batch['id'] ?>"
                                            data-product="<?= (int)$batch['product_id'] ?>"
                                        >
                                            <?= htmlspecialchars($batch['batch_number']) ?>
                                            - <?= htmlspecialchars($batch['product_name']) ?>
                                            (<?= (int)$batch['quantity'] ?> Boxes)
                                        </option>

                                    <?php endwhile; ?>
                                <?php endif; ?>

                            </select>

                        </div>

                        <div class="mb-4">

                            <label class="form-label fw-semibold">
                                Number of Codes
                            </label>

                            <input type="number"
                                   name="quantity"
                                   class="form-control form-control-lg"
                                   min="1"
                                   max="500"
                                   value="10"
                                   required>

                            <small class="text-muted">
                                Maximum 500 codes per request. Total codes cannot
                                exceed the batch quantity.
                            </small>

                        </div>

                        <button type="submit"
                                class="btn btn-success btn-lg px-4">

                            <i class="bi bi-magic me-2"></i>
                            Generate Codes

                        </button>

                    </form>

                </div>

            </div>

        </div>

        <!-- Information Card -->
        <div class="col-lg-5">

            <div class="card border-0 shadow-sm rounded-4 h-100">

                <div class="card-body p-4">

                    <div class="rounded-4 bg-success bg-opacity-10
                                d-flex align-items-center justify-content-center mb-3"
                         style="width:65px;height:65px;">

                        <i class="bi bi-shield-lock-fill text-success fs-2"></i>

                    </div>

                    <h5 class="fw-bold">Secure Code Generation</h5>

                    <p class="text-muted">
                        Each code is randomly generated and securely stored
                        as a SHA-256 hash in the database.
                    </p>

                    <hr>

                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Code Length</span>
                        <strong>16 Characters</strong>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Storage Method</span>
                        <strong>SHA-256 Hash</strong>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Initial Status</span>
                        <span class="badge bg-success">Active</span>
                    </div>

                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Redemption</span>
                        <strong>One Time Only</strong>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- Generated Codes -->
    <?php if (!empty($generatedCodes)): ?>

        <div class="card border-0 shadow-sm rounded-4 mt-4">

            <div class="card-body p-4">

                <div class="d-flex flex-wrap justify-content-between
                            align-items-center gap-3 mb-3">

                    <div>
                        <h5 class="fw-bold mb-1">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            Generated Product Codes
                        </h5>

                        <p class="text-muted mb-0">
                            Save these codes securely. Plain codes are shown only once.
                        </p>
                    </div>

                    <button type="button"
                            class="btn btn-dark"
                            onclick="copyAllCodes()">

                        <i class="bi bi-clipboard me-2"></i>
                        Copy All Codes

                    </button>

                </div>

                <textarea id="generatedCodes"
                          class="form-control font-monospace"
                          rows="10"
                          readonly><?=
                    htmlspecialchars(implode("\n", $generatedCodes))
                ?></textarea>

                <div class="mt-3">
                    <span class="badge bg-success">
                        <?= count($generatedCodes) ?> Codes Generated
                    </span>
                </div>

            </div>

        </div>

    <?php endif; ?>

</div>

<script>

// Filter batches according to selected product
const productSelect = document.getElementById("product_id");
const batchSelect = document.getElementById("batch_id");

function filterBatches() {

    const selectedProduct = productSelect.value;

    Array.from(batchSelect.options).forEach(option => {

        if (option.value === "") {
            option.hidden = false;
            return;
        }

        option.hidden = option.dataset.product !== selectedProduct;

    });

    const selectedOption = batchSelect.selectedOptions[0];

    if (selectedOption && selectedOption.hidden) {
        batchSelect.value = "";
    }
}

productSelect.addEventListener("change", filterBatches);

filterBatches();


// Copy all generated codes
function copyAllCodes() {

    const textarea = document.getElementById("generatedCodes");

    navigator.clipboard.writeText(textarea.value)
        .then(() => {
            alert("All product codes copied successfully!");
        })
        .catch(() => {

            textarea.select();
            document.execCommand("copy");

            alert("Codes copied. Save them securely!");
        });

}

</script>

<?php require_once "../../includes/footer.php"; ?>
