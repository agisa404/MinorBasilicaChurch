<?php
require_once __DIR__ . '/core.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: account_management.php?auth=login');
    exit();
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    set_flash('danger', 'Email and password are required.');
    header('Location: account_management.php?auth=login');
    exit();
}

$stmt = $conn->prepare('SELECT id, full_name, email, password, role FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($password, $user['password'])) {
    set_flash('danger', 'Invalid login credentials.');
    header('Location: account_management.php?auth=login');
    exit();
}

login_user($user);
set_flash('success', 'Welcome back, ' . ($user['full_name'] ?: $user['email']) . '.');
header('Location: index.php');
exit();
?>
