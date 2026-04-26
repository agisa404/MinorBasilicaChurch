<?php
require_once __DIR__ . '/layout.php';
require_admin_only();

$priests = [];
$priestStmt = $conn->prepare('SELECT id, full_name, email FROM users WHERE role = "priest" ORDER BY full_name');
$priestStmt->execute();
$priests = $priestStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$priestStmt->close();

$selectedPriestId = (int)($_GET['priest'] ?? 0);
$selectedPriest = null;
if ($selectedPriestId) {
    $stmt = $conn->prepare('SELECT id, full_name, email FROM users WHERE id = ? AND role = "priest"');
    $stmt->bind_param('i', $selectedPriestId);
    $stmt->execute();
    $selectedPriest = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$schedules = [];
if ($selectedPriest) {
    $stmt = $conn->prepare('SELECT title, event_date, event_time, status FROM event_schedules WHERE priest_id = ? AND event_kind = "mass" ORDER BY event_date, event_time');
    $stmt->bind_param('i', $selectedPriestId);
    $stmt->execute();
    $schedules = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

render_header('Priest Availability', 'priest_availability');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Priest Availability</h2>
    <a class="btn btn-outline-light" href="event_schedule_admin.php">← Back to Schedules</a>
</div>

<div class="card bg-dark border-warning-subtle mb-4">
    <div class="card-body">
        <h5 class="text-warning mb-3">Select Priest</h5>
        <form method="GET" class="row g-3">
            <div class="col-md-6">
                <select class="form-select" name="priest" onchange="this.form.submit()">
                    <option value="">Choose priest...</option>
                    <?php foreach ($priests as $p): ?>
                        <option value="<?php echo (int)$p['id']; ?>" <?php echo $selectedPriestId === (int)$p['id'] ? 'selected' : ''; ?>><?php echo e($p['full_name'] ?: $p['email']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<?php if ($selectedPriest): ?>
<div class="card bg-dark border-warning-subtle">
    <div class="card-body">
        <h5 class="text-warning mb-3"><?php echo e($selectedPriest['full_name'] ?: $selectedPriest['email']); ?> - Mass Schedule</h5>
        <?php if (!$schedules): ?>
            <div class="alert alert-info">No masses scheduled for this priest.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-dark table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Title</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $s): ?>
                            <tr>
                                <td><?php echo e($s['event_date']); ?></td>
                                <td><?php echo date('h:i A', strtotime($s['event_time'])); ?></td>
                                <td><?php echo e($s['title']); ?></td>
                                <td>
                                    <span class="badge text-bg-<?php echo $s['status'] === 'confirmed' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($s['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
<?php render_footer(); ?>
