<?php
require_once __DIR__ . '/core.php';
logout_user();
session_start();
set_flash('success', 'You have been logged out.');
header('Location: account_management.php');
exit();
?>
