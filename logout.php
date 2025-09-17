<?php
session_start();
session_destroy();

// Clear browser cache
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Back to login
header("Location: login.php");
exit;
?>
