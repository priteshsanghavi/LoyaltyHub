
<?php

require_once __DIR__ . "/../config/config.php";

$_SESSION = [];

session_destroy();

header("Location: " . SITE_URL . "auth/login.php");
exit;

?>
