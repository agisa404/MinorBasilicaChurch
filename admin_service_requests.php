
<?php
require_once __DIR__ . '/layout.php';
$admin = require_admin_or_staff();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['action'])) {
    $requestId = (int)$_POST['request_id'];
    $action = trim($_POST['action']);
    $note = trim($_POST['admin_note'] ?? '');

    $stmt = $conn->prepare('SELECT id, user_id, title, form_type, details, requested_date, requested_time, status FROM service_requests WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $requestId);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($req) {
        if ($action === 'confirm_event' && (($req['form_type'] ?? '') === 'Event Creation Request')) {
            header('Location: event_schedule_admin.php?request_id=' . $requestId);
            exit();
        }
        if (in_array($req['status'], ['confirmed', 'rejected'], true)) {
            set_flash('warning', 'This request is already finalized.');
            header('Location: admin_service_requests.php');
            exit();
        }

        if ($action === 'confirm') {
            if (($req['form_type'] ?? '') === 'Event Creation Request') {
                header('Location: event_schedule_admin.php?request_id=' . $requestId);
                exit();
            }

            $hasConflict = false;

            if (!empty($req['requested_date']) && !empty($req['requested_time'])) {
                $conflictStmt = $conn->prepare('SELECT s.id FROM schedules s
                    JOIN service_requests r ON r.id = s.request_id
                    WHERE s.event_date = ? AND s.event_time = ? AND r.status = "confirmed"
                    LIMIT 1');
                $conflictStmt->bind_param('ss', $req['requested_date'], $req['requested_time']);
                $conflictStmt->execute();
                $hasConflict = $conflictStmt->get_result()->num_rows > 0;
                $conflictStmt->close();
            }

            if ($hasConflict) {
                $status = 'conflict';
                $autoNote = 'Schedule conflict: selected date/time already booked.';
                $newNote = $note !== '' ? $note : $autoNote;
                $update = $conn->prepare('UPDATE service_requests SET status = ?, admin_note = ? WHERE id = ?');
                $update->bind_param('ssi', $status, $newNote, $requestId);
                $update->execute();
                $update->close();

                notify_user((int)$req['user_id'], 'Request #' . $requestId . ' has a schedule conflict. Please choose another date/time.');
                set_flash('warning', 'Conflict detected. User notified.');
            } else {
                $status = 'confirmed';
                $update = $conn->prepare('UPDATE service_requests SET status = ?, admin_note = ? WHERE id = ?');
                $update->bind_param('ssi', $status, $note, $requestId);
                $update->execute();
                $update->close();

                if (!empty($req['requested_date']) && !empty($req['requested_time'])) {
                    $insertSchedule = $conn->prepare('INSERT INTO schedules (request_id, event_title, event_date, event_time) VALUES (?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE event_title = VALUES(event_title), event_date = VALUES(event_date), event_time = VALUES(event_time)');
                    $insertSchedule->bind_param('isss', $requestId, $req['title'], $req['requested_date'], $req['requested_time']);
                    $insertSchedule->execute();
                    $insertSchedule->close();
                }

                notify_user((int)$req['user_id'], 'Request #' . $requestId . ' has been confirmed by Admin/Staff.');
                set_flash('success', 'Request confirmed and schedule updated.');
            }
        } elseif ($action === 'reject') {
            $status = 'rejected';
            $update = $conn->prepare('UPDATE service_requests SET status = ?, admin_note = ? WHERE id = ?');
            $update->bind_param('ssi', $status, $note, $requestId);
            $update->execute();
            $update->close();

            notify_user((int)$req['user_id'], 'Request #' . $requestId . ' has been rejected. ' . ($note ?: 'Please contact parish office.'));
            set_flash('danger', 'Request rejected and user notified.');
        }
    }

    header('Location: admin_service_requests.php');
    exit();
}

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
$reqStmt = $conn->prepare('SELECT r.id, r.title, r.form_type, r.requested_date, r.requested_time, r.status, r.admin_note, r.created_at, u.full_name, u.email
    FROM service_requests r
    JOIN users u ON u.id = r.user_id
    ORDER BY FIELD(r.status, "pending","conflict","confirmed","rejected"), r.created_at DESC');
$reqStmt->execute();
$reqRes = $reqStmt->get_result();
$requests = $reqRes->fetch_all(MYSQLI_ASSOC);
$reqStmt->close();

render_header('Service Requests', 'admin_service_requests');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Admin Request</h2>
    <div class="text-secondary">Logged in as <?php echo e($admin['full_name'] ?: $admin['email']); ?></div>
</div>
<p class="text-secondary mb-3">Showing all requests (latest first).</p>

<div class="dash-welcome rounded-4 p-3 p-md-4 mb-4">
        <h2 class="h4 mb-3">Welcome</h2>
        <p class="mb-0">Review and manage service requests, confirm schedules, and track request statuses.</p>
    </div>

    <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3"><div class="card bg-dark border-warning-subtle"><div class="card-body"><h6 class="text-warning">Pending</h6><div class="display-6"><?php echo $summary['pending']; ?></div></div></div></div>
    <div class="col-sm-6 col-lg-3"><div class="card bg-dark border-warning-subtle"><div class="card-body"><h6 class="text-success">Confirmed</h6><div class="display-6"><?php echo $summary['confirmed']; ?></div></div></div></div>
    <div class="col-sm-6 col-lg-3"><div class="card bg-dark border-warning-subtle"><div class="card-body"><h6 class="text-warning">Conflict</h6><div class="display-6"><?php echo $summary['conflict']; ?></div></div></div></div>
    <div class="col-sm-6 col-lg-3"><div class="card bg-dark border-warning-subtle"><div class="card-body"><h6 class="text-danger">Rejected</h6><div class="display-6"><?php echo $summary['rejected']; ?></div></div></div></div>
</div>

<div class="card bg-dark border-warning-subtle">
    <div class="card-body">
        <h5 class="text-warning mb-3">Service Requests</h5>
        <div class="table-responsive">
            <table class="table table-dark table-striped align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Request</th>
                        <th>Schedule</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$requests): ?>
                        <tr><td colspan="6" class="text-center text-secondary">No requests found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($requests as $r): ?>
                            <tr>
                                <td>#<?php echo $r['id']; ?></td>
                                <td><?php echo e(($r['full_name'] ?: '-') . ' / ' . $r['email']); ?></td>
                                <td>
                                    <div><?php echo e($r['title']); ?></div>
                                    <small class="text-secondary"><?php echo e($r['created_at']); ?></small>
                                </td>
                                <td>
                                    <?php echo e($r['requested_date'] ?: 'N/A'); ?><br>
                                    <small class="text-secondary"><?php echo e($r['requested_time'] ? date('h:i A', strtotime($r['requested_time'])) : 'N/A'); ?></small>
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
                                    <?php if (!empty($r['admin_note'])): ?>
                                        <div><small class="text-secondary"><?php echo e($r['admin_note']); ?></small></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (in_array($r['status'], ['pending', 'conflict'], true)): ?>
                                        <form method="POST" class="d-grid gap-2">
                                            <input type="hidden" name="request_id" value="<?php echo $r['id']; ?>">
                                            <input class="form-control form-control-sm" type="text" name="admin_note" placeholder="Admin note">
                                            <?php if (($r['form_type'] ?? '') === 'Event Creation Request'): ?>
                                                <button
                                                    class="btn btn-sm btn-warning"
                                                    type="submit"
                                                    name="action"
                                                    value="confirm_event"
                                                >Review &amp; Create Event</button>
                                            <?php else: ?>
                                                <button
                                                    class="btn btn-sm btn-success"
                                                    type="submit"
                                                    name="action"
                                                    value="confirm"
                                                >Confirm</button>
                                            <?php endif; ?>
                                            <button
                                                class="btn btn-sm btn-danger"
                                                type="submit"
                                                name="action"
                                                value="reject"
                                            >Reject</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge text-bg-success">Done</span>
                                    <?php endif; ?>
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
