<?php
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/request_exact_renderer.php';
$user = require_login();

$requestId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($requestId <= 0) {
    set_flash('danger', 'Invalid form reference.');
    header('Location: filled_forms.php');
    exit();
}

$stmt = $conn->prepare('SELECT r.*, u.full_name, u.email FROM service_requests r JOIN users u ON u.id = r.user_id WHERE r.id = ? LIMIT 1');
$stmt->bind_param('i', $requestId);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$request) {
    set_flash('danger', 'Form not found.');
    header('Location: filled_forms.php');
    exit();
}

if ((int)$request['user_id'] !== (int)$user['id'] && !is_admin_or_staff($user)) {
    set_flash('danger', 'You do not have permission to view that form.');
    header('Location: filled_forms.php');
    exit();
}

$details = json_decode((string)$request['details'], true);
if (!is_array($details)) {
    $details = ['details' => (string)$request['details']];
}

render_header('Filled Form View', 'services');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Filled Form Details</h2>
    <a class="btn btn-outline-light" href="filled_forms.php">Back to Filled Forms</a>
</div>

<?php render_exact_request_form($request, $details); ?>
<div class="d-flex gap-2 mt-3">
    <a class="btn btn-outline-info" href="request_pdf.php?id=<?php echo (int)$request['id']; ?>&view=1" target="_blank" rel="noopener noreferrer">View PDF</a>
    <a class="btn btn-outline-info" href="request_pdf.php?id=<?php echo (int)$request['id']; ?>">Download PDF</a>
    <a class="btn btn-outline-light" href="filled_forms.php">Close</a>
</div>
<?php render_footer(); ?>
