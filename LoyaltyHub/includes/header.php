
<?php
require_once __DIR__ . "/../config/config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = $pageTitle ?? "Dashboard";
$isAdmin = isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($pageTitle); ?> | <?= SITE_NAME; ?>
    </title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= SITE_URL; ?>assets/css/style.css?v=101">

    <?php if ($isAdmin): ?>
    <style>
        body {
            background: #f4f7f6;
            font-family: 'DM Sans', sans-serif;
        }

        .admin-shell {
            min-height: 100vh;
        }

        .admin-sidebar {
            width: 265px;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 1050;
            overflow-y: auto;
            background: linear-gradient(180deg,#103d32,#092b24);
            color: #fff;
            padding: 24px 16px;
            transition: .3s ease;
        }

        .admin-brand {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 4px 9px 28px;
            color: white;
            text-decoration: none;
            font-family: 'Outfit',sans-serif;
            font-size: 21px;
            font-weight: 800;
            letter-spacing: .7px;
        }

        .admin-brand-icon {
            width: 43px;
            height: 43px;
            display: grid;
            place-items: center;
            border-radius: 13px;
            background: linear-gradient(135deg,#c9f3d8,#75d6a4);
            color: #075b42;
            font-size: 21px;
        }

        .admin-brand span:last-child {
            color: #8de0b1;
        }

        .sidebar-label {
            color: #80a89a;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.6px;
            padding: 15px 12px 9px;
        }

        .sidebar-menu {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 13px;
            padding: 12px 13px;
            color: #c0d4ca;
            text-decoration: none;
            border-radius: 11px;
            font-size: 13px;
            font-weight: 600;
            transition: .2s ease;
        }

        .sidebar-menu a i {
            width: 20px;
            font-size: 17px;
        }

        .sidebar-menu a:hover {
            background: rgba(255,255,255,.09);
            color: white;
            transform: translateX(3px);
        }

        .sidebar-menu a.active {
            background: linear-gradient(100deg,#159565,#08764d);
            color: white;
            box-shadow: 0 5px 15px rgba(0,0,0,.15);
        }

        .sidebar-bottom {
            border-top: 1px solid rgba(255,255,255,.12);
            margin-top: 25px;
            padding: 20px 8px 5px;
        }

        .sidebar-user {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-avatar {
            width: 39px;
            height: 39px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            border-radius: 50%;
            background: #c7f1d8;
            color: #075b42;
            font-weight: 800;
        }

        .sidebar-user-name {
            color: white;
            font-size: 12px;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .sidebar-user-role {
            color: #91b5a6;
            font-size: 10px;
            margin-top: 2px;
        }

        .admin-main {
            margin-left: 265px;
            min-height: 100vh;
            transition: .3s ease;
        }

        .admin-topbar {
            height: 72px;
            background: #fff;
            border-bottom: 1px solid #e9efeb;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            position: sticky;
            top: 0;
            z-index: 900;
        }

        .admin-topbar-title {
            color: #173e32;
            font-size: 17px;
            font-weight: 800;
        }

        .admin-topbar-subtitle {
            color: #929e97;
            font-size: 11px;
        }

        .admin-menu-toggle {
            width: 39px;
            height: 39px;
            display: none;
            place-items: center;
            border: 1px solid #e3ebe5;
            background: white;
            color: #08764d;
            border-radius: 10px;
            font-size: 19px;
        }

        .admin-topbar-right {
            display: flex;
            align-items: center;
            gap: 13px;
            color: #64776d;
            font-size: 12px;
            font-weight: 600;
        }

        .admin-status-dot {
            width: 8px;
            height: 8px;
            background: #18a66c;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }

        .admin-content {
            padding: 0;
        }

        .admin-sidebar-overlay {
            display: none;
        }

        /* Hide old top navigation on admin pages */
        .admin-main .main-navbar {
            display: none;
        }

        @media(max-width:991px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }

            .admin-sidebar.open {
                transform: translateX(0);
            }

            .admin-main {
                margin-left: 0;
            }

            .admin-menu-toggle {
                display: grid;
            }

            .admin-sidebar-overlay.show {
                display: block;
                position: fixed;
                inset: 0;
                z-index: 1040;
                background: rgba(0,0,0,.45);
            }

            .admin-topbar {
                padding: 0 17px;
            }
        }
    </style>
    <?php endif; ?>
</head>

<body>

<?php if ($isAdmin): ?>

<div class="admin-shell">

    <aside class="admin-sidebar" id="adminSidebar">

        <a href="<?= SITE_URL ?>admin/dashboard.php" class="admin-brand">
            <span class="admin-brand-icon"><i class="bi bi-gift-fill"></i></span>
            <span>LOYALTY<span>HUB</span></span>
        </a>

        <div class="sidebar-label">MAIN MENU</div>

        <nav class="sidebar-menu">
            <a href="<?= SITE_URL ?>admin/dashboard.php"
               class="<?= $currentPath = str_replace('\\','/',$_SERVER['PHP_SELF']); echo strpos($currentPath,'/admin/dashboard.php') !== false ? 'active' : ''; ?>">
                <i class="bi bi-grid-fill"></i> Dashboard
            </a>

            <a href="<?= SITE_URL ?>admin/customers/">
                <i class="bi bi-people-fill"></i> Customers
            </a>

            <a href="<?= SITE_URL ?>admin/products/">
                <i class="bi bi-box-seam-fill"></i> Products
            </a>

            <a href="<?= SITE_URL ?>admin/rewards/">
                <i class="bi bi-gift-fill"></i> Rewards
            </a>
<a href="<?= SITE_URL ?>admin/redemptions/index.php">
    <i class="bi bi-gift"></i>
    <span>Redemptions</span>
</a>
            <div class="sidebar-label">CODE MANAGEMENT</div>

            <a href="<?= SITE_URL ?>admin/batches/index.php"
               class="<?= strpos($currentPath,'/admin/batches/') !== false ? 'active' : ''; ?>">
                <i class="bi bi-collection-fill"></i> Product Batches
            </a>

            <a href="<?= SITE_URL ?>admin/codes/index.php"
               class="<?= strpos($currentPath,'/admin/codes/') !== false ? 'active' : ''; ?>">
                <i class="bi bi-qr-code-scan"></i> Product Codes
            </a>

            <a href="<?= SITE_URL ?>admin/codes/generate.php">
                <i class="bi bi-plus-square-fill"></i> Generate Codes
            </a>
        </nav>

        <div class="sidebar-bottom">
            <div class="sidebar-user">
                <div class="sidebar-avatar">
                    <?= strtoupper(substr($_SESSION['user_name'] ?? 'A',0,1)); ?>
                </div>
                <div>
                    <div class="sidebar-user-name">
                        <?= htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>
                    </div>
                    <div class="sidebar-user-role">Administrator</div>
                </div>
            </div>

            <a href="<?= SITE_URL ?>auth/logout.php"
               class="text-decoration-none d-flex align-items-center gap-2 mt-4"
               style="color:#f2b2a9;font-size:12px;font-weight:700;">
                <i class="bi bi-box-arrow-left fs-5"></i> Sign Out
            </a>
        </div>

    </aside>

    <div class="admin-sidebar-overlay" id="adminOverlay"></div>

    <main class="admin-main">

        <header class="admin-topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="admin-menu-toggle" id="adminMenuToggle" type="button">
                    <i class="bi bi-list"></i>
                </button>

                <div>
                    <div class="admin-topbar-title"><?= htmlspecialchars($pageTitle); ?></div>
                    <div class="admin-topbar-subtitle">LoyaltyHub / Administration</div>
                </div>
            </div>

            <div class="admin-topbar-right">
                <span><i class="admin-status-dot"></i> System Online</span>
                <span class="d-none d-sm-inline">
                    <i class="bi bi-shield-check me-1"></i> Admin
                </span>
            </div>
        </header>

        <div class="admin-content">

<?php else: ?>

    <?php include __DIR__ . "/navbar.php"; ?>

<?php endif; ?>
