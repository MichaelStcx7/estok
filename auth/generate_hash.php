<?php
$password_input = '12345';
$hashed_password = password_hash($password_input, PASSWORD_DEFAULT);
echo $hashed_password; 
?>