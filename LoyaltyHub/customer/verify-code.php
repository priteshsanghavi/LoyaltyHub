
<?php

require_once "../config/config.php";

// Redirect to the actual product code redemption page
header("Location: " . SITE_URL . "customer/redeem-code.php");
exit;

?>
