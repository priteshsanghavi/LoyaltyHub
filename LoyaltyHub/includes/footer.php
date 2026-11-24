
<?php
$isAdminFooter = isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin';
?>

<?php if ($isAdminFooter): ?>

        </div><!-- admin-content -->
    </main><!-- admin-main -->

</div><!-- admin-shell -->

<?php else: ?>

<footer class="main-footer">

    <div class="container">
        <div class="row align-items-center">

            <div class="col-md-6">

                <a href="<?= SITE_URL; ?>" class="brand-logo footer-brand">
                    <span class="brand-icon">
                        <i class="bi bi-gift-fill"></i>
                    </span>
                    LOYALTY<span class="brand-highlight">HUB</span>
                </a>

                <p class="footer-description">
                    Every purchase brings you closer to something special.
                    Earn points and enjoy exclusive rewards.
                </p>

            </div>

            <div class="col-md-6 text-md-end">

                <p class="footer-copy">
                    &copy; <?= date("Y"); ?> LoyaltyHub.
                    All Rights Reserved.
                </p>

                <p class="footer-tagline">
                    Made for rewarding customer loyalty.
                </p>

            </div>

        </div>
    </div>

</footer>

<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="<?= SITE_URL; ?>assets/js/script.js"></script>

<?php if ($isAdminFooter): ?>
<script>
document.addEventListener("DOMContentLoaded", function () {

    const toggle = document.getElementById("adminMenuToggle");
    const sidebar = document.getElementById("adminSidebar");
    const overlay = document.getElementById("adminOverlay");

    if (toggle && sidebar && overlay) {

        toggle.addEventListener("click", function () {
            sidebar.classList.toggle("open");
            overlay.classList.toggle("show");
        });

        overlay.addEventListener("click", function () {
            sidebar.classList.remove("open");
            overlay.classList.remove("show");
        });

        document.querySelectorAll(".admin-sidebar a").forEach(function(link) {
            link.addEventListener("click", function() {
                sidebar.classList.remove("open");
                overlay.classList.remove("show");
            });
        });
    }

});
</script>
<?php endif; ?>

</body>
</html>
