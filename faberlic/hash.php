<?php
$password = 'Admin12345!'; // Пароль, который мы хотим установить
echo password_hash($password, PASSWORD_DEFAULT);
?>