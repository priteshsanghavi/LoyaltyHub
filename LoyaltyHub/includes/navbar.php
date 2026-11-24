
<?php
$currentRole = $_SESSION['role'] ?? '';
$userName = $_SESSION['user_name'] ?? 'Guest';

$currentPage = basename($_SERVER['PHP_SELF']);
$currentPath = str_replace('\\', '/', $_SERVER['PHP_SELF']);

function navActive($path) {
    global $currentPath;
    return strpos($currentPath, $path) !== false ? 'active' : '';
}
?>

<style>
/* ================= LOYALTYHUB PREMIUM NAVBAR ================= */

.main-navbar {
    position: sticky;
    top: 0;
    z-index: 1000;
    background: linear-gradient(110deg, #103d32, #075b42, #087653);
    padding: 13px 0;
    box-shadow: 0 5px 25px rgba(8, 55, 40, .16);
    border-bottom: 1px solid rgba(255,255,255,.08);
}

.navbar-main-inner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
}

.brand-logo {
    display: flex;
    align-items: center;
    gap: 11px;
    color: white !important;
    font-size: 21px;
    font-weight: 900;
    letter-spacing: 1px;
    white-space: nowrap;
}

.brand-logo:hover {
    opacity: .9;
}

.brand-icon {
    width: 43px;
    height: 43px;
    display: grid;
    place-items: center;
    background: linear-gradient(135deg, #c9f3d8, #75d6a4);
    color: #075b42;
    border-radius: 13px;
    font-size: 21px;
    box-shadow: 0 5px 14px rgba(0,0,0,.15);
}

.brand-highlight {
    color: #8de0b1;
}

.navbar-links {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    flex-wrap: wrap;
}

.navbar-links a {
    position: relative;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: rgba(255,255,255,.76);
    text-decoration: none;
    padding: 11px 13px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 650;
    transition: .25s ease;
}

.navbar-links a i {
    font-size: 15px;
}

.navbar-links a:hover {
    background: rgba(255,255,255,.10);
    color: #fff;
}

.navbar-links a.active {
    background: rgba(255,255,255,.15);
    color: #fff;
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.08);
}

.navbar-user {
    display: flex;
    align-items: center;
    gap: 10px;
}

.user-avatar {
    width: 38px;
    height: 38px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    color: #075b42;
    background: linear-gradient(135deg, #d4f6e0, #86dbae);
    font-weight: 900;
    font-size: 15px;
    border: 2px solid rgba(255,255,255,.4);
}

.user-name {
    color: white;
    font-size: 13px;
    font-weight: 700;
    max-width: 110px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.logout-btn,
.login-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    padding: 9px 13px;
    border-radius: 10px;
    text-decoration: none;
    font-size: 12px;
    font-weight: 750;
    transition: .25s ease;
}

.logout-btn {
    color: white;
    border: 1px solid rgba(255,255,255,.20);
    background: rgba(255,255,255,.08);
}

.logout-btn:hover {
    background: rgba(255,255,255,.18);
    color: white;
}

.login-btn {
    background: #c7f1d8;
    color: #075b42;
}

.login-btn:hover {
    background: white;
    color: #075b42;
    transform: translateY(-2px);
}

.navbar-toggle {
    display: none;
    width: 42px;
    height: 42px;
    border: 1px solid rgba(255,255,255,.25);
    border-radius: 11px;
    color: white;
    background: rgba(255,255,255,.10);
    font-size: 21px;
}

/* MOBILE */

@media(max-width:1100px) {
    .navbar-main-inner {
        flex-wrap: wrap;
    }

    .navbar-toggle {
        display: grid;
        place-items: center;
        margin-left: auto;
    }

    .navbar-links {
        display: none;
        order: 3;
        width: 100%;
        flex-direction: column;
        align-items: stretch;
        gap: 4px;
        padding: 10px 0;
    }

    .navbar-links.show {
        display: flex;
    }

    .navbar-links a {
        width: 100%;
        padding: 12px 15px;
    }

    .navbar-user {
        order: 2;
        margin-left: auto;
    }
}

@media(max-width:576px) {
    .main-navbar {
        padding: 10px 0;
    }

    .brand-logo {
        font-size: 17px;
    }

    .brand-icon {
        width: 38px;
        height: 38px;
    }

    .user-name {
        display: none;
    }

    .navbar-user {
        gap: 6px;
    }

    .logout-btn {
        padding: 9px 10px;
    }
}
</style>

<nav class="main-navbar">
    <div class="container-fluid px-lg-5">
        <div class="navbar-main-inner">

            <!-- BRAND -->
            <a href="<?php echo SITE_URL; ?>"
               class="brand-logo text-decoration-none">

                <span class="brand-icon">
                    <i class="bi bi-gift-fill"></i>
                </span>

                <span>LOYALTY<span class="brand-highlight">HUB</span></span>
            </a>

            <!-- MOBILE TOGGLE -->
            <button class="navbar-toggle"
                    type="button"
                    id="navbarToggle"
                    aria-label="Toggle navigation"
                    aria-expanded="false">
                <i class="bi bi-list"></i>
            </button>

            <!-- NAVIGATION -->
            <div class="navbar-links" id="navbarLinks">

                <?php if ($currentRole === 'admin'): ?>

                    <a class="<?php echo navActive('/admin/dashboard.php'); ?>"
                       href="<?php echo SITE_URL; ?>admin/dashboard.php">
                        <i class="bi bi-grid-fill"></i> Dashboard
                    </a>

                    <a class="<?php echo navActive('/admin/customers/'); ?>"
                       href="<?php echo SITE_URL; ?>admin/customers/">
                        <i class="bi bi-people-fill"></i> Customers
                    </a>

                    <a class="<?php echo navActive('/admin/products/'); ?>"
                       href="<?php echo SITE_URL; ?>admin/products/">
                        <i class="bi bi-box-seam-fill"></i> Products
                    </a>

                    <a class="<?php echo navActive('/admin/batches/'); ?>"
                       href="<?php echo SITE_URL; ?>admin/batches/index.php">
                        <i class="bi bi-collection-fill"></i> Batches
                    </a>

                    <a class="<?php echo navActive('/admin/codes/'); ?>"
                       href="<?php echo SITE_URL; ?>admin/codes/index.php">
                        <i class="bi bi-qr-code-scan"></i> Codes
                    </a>

                    <a class="<?php echo navActive('/admin/rewards/'); ?>"
                       href="<?php echo SITE_URL; ?>admin/rewards/">
                        <i class="bi bi-gift-fill"></i> Rewards
                    </a>

                <?php elseif ($currentRole === 'customer'): ?>

                    <a class="<?php echo navActive('/customer/dashboard.php'); ?>"
                       href="<?php echo SITE_URL; ?>customer/dashboard.php">
                        <i class="bi bi-grid-fill"></i> Dashboard
                    </a>

                    <a class="<?php echo navActive('/customer/redeem-code.php'); ?>"
                       href="<?php echo SITE_URL; ?>customer/verify-code.php">
                        <i class="bi bi-qr-code-scan"></i> Earn Points
                    </a>

                    <a class="<?php echo navActive('/customer/rewards.php'); ?>"
                       href="<?php echo SITE_URL; ?>customer/rewards.php">
                        <i class="bi bi-gift-fill"></i> Rewards
                    </a>

                    <a class="<?php echo navActive('/customer/my-redemptions.php'); ?>"
                       href="<?php echo SITE_URL; ?>customer/my-redemptions.php">
                        <i class="bi bi-clock-history"></i> My Redemptions
                    </a>

                <?php endif; ?>

            </div>

            <!-- USER MENU -->
            <div class="navbar-user">

                <?php if (isset($_SESSION['user_id'])): ?>

                    <div class="user-avatar">
                        <?php echo strtoupper(substr($userName, 0, 1)); ?>
                    </div>

                    <span class="user-name">
                        <?php echo htmlspecialchars($userName); ?>
                    </span>

                    <?php if ($currentRole === 'customer'): ?>
                        <a href="<?php echo SITE_URL; ?>customer/profile.php"
                           class="logout-btn">
                            <i class="bi bi-person-circle"></i>
                            Profile
                        </a>
                    <?php endif; ?>

                    <a href="<?php echo SITE_URL; ?>auth/logout.php"
                       class="logout-btn">
                        <i class="bi bi-box-arrow-right"></i>
                        Logout
                    </a>

                <?php else: ?>

                    <a href="<?php echo SITE_URL; ?>auth/login.php"
                       class="login-btn">
                        Login <i class="bi bi-arrow-right"></i>
                    </a>

                    <a href="<?php echo SITE_URL; ?>auth/register.php"
                       class="login-btn">
                        Register
                    </a>

                <?php endif; ?>

            </div>

        </div>
    </div>
</nav>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const toggle = document.getElementById("navbarToggle");
    const links = document.getElementById("navbarLinks");

    if (toggle && links) {
        toggle.addEventListener("click", function () {
            links.classList.toggle("show");

            const expanded = links.classList.contains("show");
            toggle.setAttribute("aria-expanded", expanded ? "true" : "false");

            toggle.innerHTML = expanded
                ? '<i class="bi bi-x-lg"></i>'
                : '<i class="bi bi-list"></i>';
        });
    }
});
</script>
