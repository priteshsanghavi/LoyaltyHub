
<?php

// Start Session

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Project Configuration

define("SITE_NAME", "Loyalty Rewards System");

define("SITE_URL", "http://localhost/loyalty-rewards/");

// Timezone

date_default_timezone_set("Asia/Kolkata");

// Error Reporting (Development)

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
