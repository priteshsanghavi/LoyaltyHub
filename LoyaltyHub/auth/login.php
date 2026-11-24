
<?php
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../config/database.php";

if (isset($_SESSION['user_id'])) {
    $destination = $_SESSION['role'] === 'admin'
        ? 'admin/dashboard.php'
        : 'customer/dashboard.php';

    header("Location: " . SITE_URL . $destination);
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === "" || $password === "") {
        $error = "Please enter email and password.";
    } else {

        $stmt = $conn->prepare(
            "SELECT id, name, email, password_hash, role, status
             FROM users
             WHERE email = ?
             LIMIT 1"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            if (
                password_verify($password, $user['password_hash'])
                && $user['status'] === 'active'
            ) {

                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    header("Location: " . SITE_URL . "admin/dashboard.php");
                } else {
                    header("Location: " . SITE_URL . "customer/dashboard.php");
                }

                exit;

            } else {
                $error = "Invalid credentials or account is blocked.";
            }

        } else {
            $error = "Invalid email or password.";
        }

        $stmt->close();
    }
}

$pageTitle = "Login";
require_once __DIR__ . "/../includes/header.php";
?>

<div class="auth-wrapper">

    <div class="auth-box">

        <div class="text-center mb-4">

            <div class="stat-icon mx-auto">
                <i class="bi bi-shield-lock-fill"></i>
            </div>

            <h2>Welcome Back!</h2>

            <p class="auth-subtitle">
                Login to your LoyaltyHub account.
            </p>

        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="mb-3">

                <label class="form-label">Email Address</label>

                <input type="email"
                       name="email"
                       class="form-control"
                       placeholder="Enter your email"
                       required>

            </div>

            <div class="mb-4">

                <label class="form-label">Password</label>

                <input type="password"
                       name="password"
                       class="form-control"
                       placeholder="Enter your password"
                       required>

            </div>

            <button type="submit"
                    class="btn-primary-custom w-100">

                Login Now
                <i class="bi bi-arrow-right ms-2"></i>

            </button>

        </form>

        <div class="text-center mt-4">

            <p>
                Don't have an account?

                <a href="register.php"
                   class="fw-bold"
                   style="color:#087f5b;">
                    Register Now
                </a>
            </p>

        </div>

    </div>

</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
