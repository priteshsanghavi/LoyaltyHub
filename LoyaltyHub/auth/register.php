
<?php
require_once __DIR__ . "/../config/config.php";
require_once __DIR__ . "/../config/database.php";

if (isset($_SESSION['user_id'])) {
    header("Location: " . SITE_URL . "customer/dashboard.php");
    exit;
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (
        $name === "" || $email === "" ||
        $phone === "" || $password === "" ||
        $confirm_password === ""
    ) {
        $error = "All fields are required.";
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    }
    elseif (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        $error = "Please enter a valid mobile number.";
    }
    elseif (strlen($password) < 8) {
        $error = "Password must contain at least 8 characters.";
    }
    elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    }
    else {

        // Check duplicate email
        $stmt = $conn->prepare(
            "SELECT id FROM users WHERE email = ?"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error = "Email address already registered.";
        } else {

            // Secure password hashing
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Register customer
            $stmt = $conn->prepare(
                "INSERT INTO users
                (name, email, phone, password_hash, role)
                VALUES (?, ?, ?, ?, 'customer')"
            );

            $stmt->bind_param(
                "ssss",
                $name,
                $email,
                $phone,
                $hashedPassword
            );

            if ($stmt->execute()) {
                $success = "Registration successful! Please login.";
            } else {
                $error = "Registration failed. Please try again.";
            }
        }

        $stmt->close();
    }
}

$pageTitle = "Customer Registration";
require_once __DIR__ . "/../includes/header.php";
?>

<div class="auth-wrapper">

    <div class="auth-box">

        <div class="text-center mb-4">

            <div class="stat-icon mx-auto">
                <i class="bi bi-person-plus-fill"></i>
            </div>

            <h2>Create Account</h2>

            <p class="auth-subtitle">
                Join LoyaltyHub and start earning rewards.
            </p>

        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
                <a href="login.php" class="fw-bold">Login Now</a>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="mb-3">
                <label class="form-label">Full Name</label>

                <input type="text"
                       name="name"
                       class="form-control"
                       placeholder="Enter your full name"
                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                       required>
            </div>

            <div class="mb-3">
                <label class="form-label">Email Address</label>

                <input type="email"
                       name="email"
                       class="form-control"
                       placeholder="example@gmail.com"
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                       required>
            </div>

            <div class="mb-3">
                <label class="form-label">Mobile Number</label>

                <input type="tel"
                       name="phone"
                       class="form-control"
                       placeholder="Enter mobile number"
                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                       required>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>

                <input type="password"
                       name="password"
                       class="form-control"
                       placeholder="Minimum 8 characters"
                       required>
            </div>

            <div class="mb-4">
                <label class="form-label">Confirm Password</label>

                <input type="password"
                       name="confirm_password"
                       class="form-control"
                       placeholder="Confirm your password"
                       required>
            </div>

            <button type="submit"
                    class="btn-primary-custom w-100">
                Create Account
                <i class="bi bi-arrow-right ms-2"></i>
            </button>

        </form>

        <div class="text-center mt-4">

            <p class="mb-0">
                Already have an account?

                <a href="login.php"
                   class="fw-bold"
                   style="color:#087f5b;">
                    Login
                </a>
            </p>

        </div>

    </div>

</div>

<?php require_once __DIR__ . "/../includes/footer.php"; ?>
