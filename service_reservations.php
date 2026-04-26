<?php
require_once __DIR__ . '/core.php';
require_login();

set_flash('warning', 'Service reservation module is currently unavailable.');
header('Location: index.php');
exit();
