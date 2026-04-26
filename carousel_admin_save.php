<?php
require_once __DIR__ . '/carousel_helpers.php';

$currentImages = load_carousel_images($conn);
[$ok, $message] = update_carousel_images($conn, $currentImages);
set_flash($ok ? 'success' : 'danger', $message);

header('Location: admin_dashboard.php');
exit();
