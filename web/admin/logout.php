<?php

declare(strict_types=1);

session_start();

// Destroy session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;

?>
