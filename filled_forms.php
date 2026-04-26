<?php
require_once __DIR__ . '/layout.php';
$user = require_login();
$isAdmin = is_admin_or_staff($user);

if ($isAdmin) {
    $stmt = $conn->prepare('SELECT r.id, r.form_type, r.title, r.status, r.requested_date, r.requested_time, r.created_at, u.full_name, u.email
        FROM service_requests r
        JOIN users u ON u.id = r.user_id
        ORDER BY r.created_at DESC');
} else {
    $uid = (int)$user['id'];
    $stmt = $conn->prepare('SELECT r.id, r.form_type, r.title, r.status, r.requested_date, r.requested_time, r.created_at, u.full_name, u.email
        FROM service_requests r
        JOIN users u ON u.id = r.user_id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC');
    $stmt->bind_param('i', $uid);
}
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

render_header('Filled Forms', 'services');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Filled Forms</h2>
    <a class="btn btn-warning" href="services.php">Back to Services</a>
</div>
<p class="text-secondary mb-4">View all submitted forms and open the complete form details.</p>

<div class="card bg-dark border-warning-subtle">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-dark table-striped align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <?php if ($isAdmin): ?><th>Requester</th><?php endif; ?>
                        <th>Form</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$rows): ?>
                        <tr><td colspan="<?php echo $isAdmin ? 6 : 5; ?>" class="text-center">No submitted forms found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td>#<?php echo (int)$r['id']; ?></td>
                                <?php if ($isAdmin): ?>
                                    <td><?php echo e(($r['full_name'] ?: '-') . ' / ' . $r['email']); ?></td>
                                <?php endif; ?>
                                <td>
                                    <strong><?php echo e($r['title']); ?></strong><br>
                                    <small class="text-secondary"><?php echo e($r['form_type']); ?></small>
                                </td>
                                <td>
                                    <?php echo e($r['created_at']); ?><br>
                                    <small class="text-secondary">
                                        <?php echo e($r['requested_date'] ?: 'N/A'); ?> /
                                        <?php echo e($r['requested_time'] ? date('h:i A', strtotime($r['requested_time'])) : 'N/A'); ?>
                                    </small>
                                </td>
                                <td>
                                    <?php
                                    $badge = match ($r['status']) {
                                        'confirmed' => 'success',
                                        'rejected' => 'danger',
                                        'conflict' => 'warning',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge text-bg-<?php echo $badge; ?>"><?php echo e($r['status']); ?></span>
                                </td>
                                <td>
                                    <a class="btn btn-sm btn-outline-info" href="filled_form_view.php?id=<?php echo (int)$r['id']; ?>">Full View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php render_footer(); ?>
