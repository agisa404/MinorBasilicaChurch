<?php
require_once __DIR__ . '/layout.php';
$admin = require_admin_only();

$summary = [
    'pending' => 0,
    'confirmed' => 0,
    'conflict' => 0,
    'rejected' => 0
];
$monthStart = app_now()->format('Y-m-01 00:00:00');
$nextMonthStart = app_now()->modify('first day of next month')->format('Y-m-01 00:00:00');

$sumStmt = $conn->prepare('SELECT status, COUNT(*) AS total
    FROM service_requests
    WHERE created_at >= ? AND created_at < ?
    GROUP BY status');
$sumStmt->bind_param('ss', $monthStart, $nextMonthStart);
$sumStmt->execute();
$sumRes = $sumStmt->get_result();
while ($row = $sumRes->fetch_assoc()) {
    if (array_key_exists($row['status'], $summary)) {
        $summary[$row['status']] = (int)$row['total'];
    }
}
$sumStmt->close();

$requests = [];
$reqStmt = $conn->prepare('SELECT r.id, r.title, r.form_type, r.requested_date, r.requested_time, r.status, r.created_at, u.full_name, u.email
    FROM service_requests r
    JOIN users u ON u.id = r.user_id
    WHERE r.created_at >= ? AND r.created_at < ?
    ORDER BY r.created_at DESC');
$reqStmt->bind_param('ss', $monthStart, $nextMonthStart);
$reqStmt->execute();
$reqRes = $reqStmt->get_result();
$requests = $reqRes->fetch_all(MYSQLI_ASSOC);
$reqStmt->close();

render_header('Admin Dashboard', 'admin_dashboard');
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Admin Dashboard - Current Month</h2>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card bg-dark border-warning-subtle">
            <div class="card-body text-center">
                <div class="display-6 text-warning"><?php echo $summary['pending']; ?></div>
                <h6>Pending</h6>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-warning-subtle">
            <div class="card-body text-center">
                <div class="display-6 text-success"><?php echo $summary['confirmed']; ?></div>
                <h6>Confirmed</h6>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-warning-subtle">
            <div class="card-body text-center">
                <div class="display-6 text-warning"><?php echo $summary['conflict']; ?></div>
                <h6>Conflict</h6>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-dark border-warning-subtle">
            <div class="card-body text-center">
                <div class="display-6 text-danger"><?php echo $summary['rejected']; ?></div>
                <h6>Rejected</h6>
            </div>
        </div>
    </div>
</div>

<div class="card bg-dark border-warning-subtle">
    <div class="card-body">
        <h5 class="text-warning mb-3">Recent Service Requests (View Only)</h5>
        <div class="table-responsive">
            <table class="table table-dark table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Title</th>
                        <th>Date/Time</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($requests, 0, 20) as $r): ?>
                        <tr>
                            <td>#<?php echo $r['id']; ?></td>
                            <td><?php echo htmlspecialchars($r['full_name'] ?: $r['email']); ?></td>
                            <td><?php echo htmlspecialchars($r['title']); ?></td>
                            <td><?php echo htmlspecialchars($r['requested_date'] ?: 'N/A'); ?> <small><?php echo htmlspecialchars($r['requested_time'] ?: ''); ?></small></td>
                            <td>
                                <?php $badge = match ($r['status'] ?? '') {
                                    'confirmed' => 'success',
                                    'rejected' => 'danger',
                                    'conflict' => 'warning',
                                    default => 'secondary'
                                }; ?>
                                <span class="badge bg-<?php echo $badge; ?> text-white"><?php echo htmlspecialchars($r['status'] ?? ''); ?></span>
                            </td>
                            <td><small><?php echo date('M j', strtotime($r['created_at'])); ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($requests)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No recent requests this month.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if (!empty($requests)): ?>
            <div class="mt-3 text-center">
                <a href="admin_service_requests.php" class="btn btn-outline-warning">View All Requests →</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php render_footer(); ?>
