<?php
$host = "sql105.infinityfree.com";
$user = "if0_40224623";
$pass = "QttsYfA2OOTcrC";
$db   = "if0_40224623_dbestok";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>
