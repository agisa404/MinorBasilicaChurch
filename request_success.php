<?php
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/request_exact_renderer.php';
$user = require_login();

$requestId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($requestId <= 0) {
    set_flash('danger', 'Invalid request reference.');
    header('Location: services.php');
    exit();
}

$stmt = $conn->prepare('SELECT r.*, u.full_name, u.email FROM service_requests r JOIN users u ON u.id = r.user_id WHERE r.id = ? LIMIT 1');
$stmt->bind_param('i', $requestId);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$request) {
    set_flash('danger', 'Request not found.');
    header('Location: services.php');
    exit();
}

if ((int)$request['user_id'] !== (int)$user['id'] && !is_admin_or_staff($user)) {
    set_flash('danger', 'You do not have permission to view that request.');
    header('Location: services.php');
    exit();
}

$details = json_decode((string)$request['details'], true);
if (!is_array($details)) {
    $details = ['details' => (string)$request['details']];
}

render_header('Request Submitted', 'services');
?>
<?php render_exact_request_form($request, $details); ?>
<div class="d-flex flex-wrap gap-2 mt-3">
    <a class="btn btn-outline-info" href="request_pdf.php?id=<?php echo (int)$request['id']; ?>&view=1" target="_blank" rel="noopener noreferrer">View PDF</a>
    <a class="btn btn-outline-info" href="request_pdf.php?id=<?php echo (int)$request['id']; ?>">Download PDF</a>
    <a class="btn btn-outline-light" href="filled_form_view.php?id=<?php echo (int)$request['id']; ?>">Full View</a>
    <a class="btn btn-outline-light" href="services.php">Back to Services</a>
    <?php if (is_admin_or_staff($user)): ?>
        <a class="btn btn-outline-light" href="admin_dashboard.php">Open Admin Dashboard</a>
    <?php endif; ?>
</div>
<?php render_footer(); ?>
