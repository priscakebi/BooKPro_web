<?php
session_start();
session_destroy();

// Redirection vers login
header("Location: login.php");
exit();
?>