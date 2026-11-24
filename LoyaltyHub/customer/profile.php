
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

// Update profile
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_profile'])) {

    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($name === "" || $phone === "") {
        $message = "Please fill all profile fields.";
        $messageType = "danger";
    } else {

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE users SET name = ?, phone = ? WHERE id = ?"
        );

        mysqli_stmt_bind_param($stmt, "ssi", $name, $phone, $userId);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['user_name'] = $name;

            $message = "Profile updated successfully!";
            $messageType = "success";
        } else {
            $message = "Unable to update profile.";
            $messageType = "danger";
        }

        mysqli_stmt_close($stmt);
    }
}

// Change password
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['change_password'])) {

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($currentPassword === "" || $newPassword === "" || $confirmPassword === "") {

        $message = "Please fill all password fields.";
        $messageType = "danger";

    } elseif (strlen($newPassword) < 6) {

        $message = "New password must contain at least 6 characters.";
        $messageType = "danger";

    } elseif ($newPassword !== $confirmPassword) {

        $message = "New password and confirm password do not match.";
        $messageType = "danger";

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "SELECT password_hash FROM users WHERE id = ?"
        );

        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $userPassword = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);

        if (!$userPassword || !password_verify($currentPassword, $userPassword['password_hash'])) {

            $message = "Current password is incorrect.";
            $messageType = "danger";

        } else {

            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare(
                $conn,
                "UPDATE users SET password_hash = ? WHERE id = ?"
            );

            mysqli_stmt_bind_param($stmt, "si", $hashedPassword, $userId);

            if (mysqli_stmt_execute($stmt)) {
                $message = "Password changed successfully!";
                $messageType = "success";
            } else {
                $message = "Unable to change password.";
                $messageType = "danger";
            }

            mysqli_stmt_close($stmt);
        }
    }
}

// Fetch latest customer details
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, name, email, phone, role, created_at
     FROM users WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $userId);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

mysqli_stmt_close($stmt);

if (!$user) {
    die("Customer account not found.");
}

require_once "../includes/header.php";
?>

<div class="container py-5">

    <div class="mb-4">
        <h2 class="fw-bold mb-1">My Profile</h2>
        <p class="text-muted">Manage your account information and password.</p>
    </div>

    <?php if ($message !== ""): ?>
        <div class="alert alert-<?php echo htmlspecialchars($messageType); ?> alert-dismissible fade show">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- Profile Information -->
        <div class="col-lg-7">

            <div class="card border-0 shadow-sm rounded-4">

                <div class="card-body p-4 p-md-5">

                    <div class="d-flex align-items-center gap-3 mb-4">

                        <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center"
                             style="width:65px;height:65px;font-size:28px;">
                            <i class="bi bi-person-fill"></i>
                        </div>

                        <div>
                            <h4 class="fw-bold mb-1">
                                <?php echo htmlspecialchars($user['name']); ?>
                            </h4>
                            <span class="badge bg-success">Customer Account</span>
                        </div>

                    </div>

                    <hr>

                    <h5 class="fw-bold mb-4">Personal Information</h5>

                    <form method="POST">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Full Name</label>

                            <input
                                type="text"
                                name="name"
                                class="form-control form-control-lg"
                                value="<?php echo htmlspecialchars($user['name']); ?>"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email Address</label>

                            <input
                                type="email"
                                class="form-control form-control-lg"
                                value="<?php echo htmlspecialchars($user['email']); ?>"
                                readonly
                            >

                            <small class="text-muted">
                                Email address cannot be changed here.
                            </small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Phone Number</label>

                            <input
                                type="text"
                                name="phone"
                                class="form-control form-control-lg"
                                value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                required
                            >
                        </div>

                        <button
                            type="submit"
                            name="update_profile"
                            class="btn btn-success btn-lg w-100"
                        >
                            <i class="bi bi-save me-2"></i>
                            Update Profile
                        </button>

                    </form>

                </div>

            </div>

        </div>

        <!-- Password Change -->
        <div class="col-lg-5">

            <div class="card border-0 shadow-sm rounded-4">

                <div class="card-body p-4">

                    <div class="text-center mb-4">

                        <div class="bg-success bg-opacity-10 text-success rounded-circle d-inline-flex align-items-center justify-content-center"
                             style="width:65px;height:65px;font-size:28px;">
                            <i class="bi bi-shield-lock-fill"></i>
                        </div>

                        <h5 class="fw-bold mt-3">Change Password</h5>

                        <p class="text-muted small">
                            Keep your account secure with a strong password.
                        </p>

                    </div>

                    <form method="POST">

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Current Password</label>

                            <input
                                type="password"
                                name="current_password"
                                class="form-control"
                                required
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">New Password</label>

                            <input
                                type="password"
                                name="new_password"
                                class="form-control"
                                minlength="6"
                                required
                            >
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Confirm New Password</label>

                            <input
                                type="password"
                                name="confirm_password"
                                class="form-control"
                                minlength="6"
                                required
                            >
                        </div>

                        <button
                            type="submit"
                            name="change_password"
                            class="btn btn-outline-success btn-lg w-100"
                        >
                            <i class="bi bi-key me-2"></i>
                            Change Password
                        </button>

                    </form>

                </div>

            </div>

            <!-- Account Details -->
            <div class="card border-0 shadow-sm rounded-4 mt-4">

                <div class="card-body p-4">

                    <h6 class="fw-bold mb-3">Account Details</h6>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Customer ID</span>
                        <strong>#<?php echo (int) $user['id']; ?></strong>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Account Type</span>
                        <strong>Customer</strong>
                    </div>

                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Joined Date</span>
                        <strong>
                            <?php echo date("d M Y", strtotime($user['created_at'])); ?>
                        </strong>
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

<?php require_once "../includes/footer.php"; ?>
