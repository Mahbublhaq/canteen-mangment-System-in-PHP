<?php
// Start the session if it hasn't been started yet
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Destroy all session data
session_unset();
session_destroy();

// Redirect to welcome.php
header("Location: welcome.php");
exit();
?>
