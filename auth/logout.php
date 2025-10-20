<?php
session_start();

// hapus semua data session
$_SESSION = array();

// hapus cookie remember me 
if (isset($_COOKIE['user_remember'])) {
    setcookie('user_remember', '', time() - 3600, '/');
}

// hancurin session
session_destroy();

// arahin ke halaman login
header("Location: login.php");
exit;
?>