<?php
require_once __DIR__ . '/request_helpers.php';
require_once __DIR__ . '/layout.php';
$user = require_login();
$isAdmin = is_admin_or_staff($user);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_document_request'])) {
    $data = [
        'certificate_type' => trim($_POST['certificate_type'] ?? ''),
        'purpose' => trim($_POST['purpose'] ?? ''),
        'requested_by' => trim($_POST['requested_by'] ?? ''),
        'notes' => trim($_POST['notes'] ?? '')
    ];

    if ($data['certificate_type'] === '' || $data['purpose'] === '' || $data['requested_by'] === '') {
        set_flash('danger', 'Certificate type, requested by, and purpose are required.');
        header('Location: document_requests.php');
        exit();
    }

    $requestId = create_service_request(
        (int)$user['id'],
        'Document Request',
        'Certificate Request',
        $data
    );

    notify_user((int)$user['id'], 'Document request submitted. Reference #' . $requestId . '.');
    set_flash('success', 'Document request submitted successfully.');
    header('Location: request_success.php?id=' . $requestId);
    exit();
}

if ($isAdmin) {
    $stmt = $conn->prepare('SELECT r.*, u.full_name, u.email
        FROM service_requests r
        JOIN users u ON u.id = r.user_id
        WHERE r.form_type = "Document Request"
        ORDER BY r.created_at DESC');
} else {
    $uid = (int)$user['id'];
    $stmt = $conn->prepare('SELECT r.*, u.full_name, u.email
        FROM service_requests r
        JOIN users u ON u.id = r.user_id
        WHERE r.user_id = ? AND r.form_type = "Document Request"
        ORDER BY r.created_at DESC');
    $stmt->bind_param('i', $uid);
}
$stmt->execute();
$requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

render_header('Document Request Module', 'documents');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Document Request</h2>
    <a class="btn btn-outline-light" href="form_baptismal_request.php">Open Baptismal Request Form</a>
</div>
<p class="text-secondary mb-4">Request and monitor church certificates such as Baptism, Confirmation, and Marriage records.</p>

<div class="card bg-dark border-warning-subtle mb-4">
    <div class="card-body">
        <h5 class="text-warning">New Certificate Request</h5>
        <form method="POST" class="row g-3">
            <input type="hidden" name="submit_document_request" value="1">
            <div class="col-md-4">
                <label class="form-label">Certificate Type</label>
                <select class="form-select" name="certificate_type" required>
                    <option value="">Select type</option>
                    <option value="Baptism">Baptism</option>
                    <option value="Confirmation">Confirmation</option>
                    <option value="Marriage">Marriage</option>
                    <option value="No Record">No Record</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Requested By</label>
                <input class="form-control" type="text" name="requested_by" value="<?php echo e($user['full_name'] ?: ''); ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Purpose</label>
                <input class="form-control" type="text" name="purpose" required>
            </div>
            <div class="col-12">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes" rows="2"></textarea>
            </div>
            <div class="col-12">
                <button class="btn btn-warning" type="submit">Submit Document Request</button>
            </div>
        </form>
    </div>
</div>

<div class="card bg-dark border-warning-subtle">
    <div class="card-body">
        <h5 class="text-warning mb-3">Document Request History</h5>
        <div class="table-responsive">
            <table class="table table-dark table-striped align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <?php if ($isAdmin): ?><th>User</th><?php endif; ?>
                        <th>Type</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$requests): ?>
                        <tr><td colspan="<?php echo $isAdmin ? 6 : 5; ?>" class="text-center">No document requests found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($requests as $r): ?>
                            <tr>
                                <td>#<?php echo (int)$r['id']; ?></td>
                                <?php if ($isAdmin): ?>
                                    <td><?php echo e(($r['full_name'] ?: '-') . ' / ' . $r['email']); ?></td>
                                <?php endif; ?>
                                <td><?php echo e($r['form_type']); ?></td>
                                <td><?php echo e($r['created_at']); ?></td>
                                <td><span class="badge text-bg-secondary"><?php echo e($r['status']); ?></span></td>
                                <td><a class="btn btn-sm btn-outline-light" href="request_success.php?id=<?php echo (int)$r['id']; ?>">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php render_footer(); ?>
