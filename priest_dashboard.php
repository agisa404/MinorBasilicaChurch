<?php
require_once __DIR__ . '/layout.php';
$priest = require_priest_only();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim($_POST['action'] ?? '');
    $pid = (int)$priest['id'];
    if ($action === 'confirm_mass') {
        $massId = (int)($_POST['mass_id'] ?? 0);
        if ($massId > 0) {
            $stmt = $conn->prepare('UPDATE event_schedules SET status = "confirmed", priest_confirmed_at = NOW() WHERE id = ? AND priest_id = ? AND event_kind = "mass" AND status = "pending_priest"');
            $stmt->bind_param('ii', $massId, $pid);
            $stmt->execute();
            $updated = $stmt->affected_rows > 0;
            $stmt->close();
            set_flash($updated ? 'success' : 'warning', $updated ? 'Mass schedule confirmed.' : 'Mass schedule not found or already confirmed.');
            // TODO: Send email to admin
        }
    } elseif ($action === 'cancel_mass') {
        $massId = (int)($_POST['mass_id'] ?? 0);
        if ($massId > 0) {
            $stmt = $conn->prepare('UPDATE event_schedules SET status = "cancelled" WHERE id = ? AND priest_id = ? AND event_kind = "mass" AND status = "pending_priest"');
            $stmt->bind_param('ii', $massId, $pid);
            $stmt->execute();
            $cancelled = $stmt->affected_rows > 0;
            $stmt->close();
            set_flash($cancelled ? 'info' : 'warning', $cancelled ? 'Mass schedule cancelled.' : 'Mass schedule not found.');
        }
    }
    header('Location: priest_dashboard.php');
    exit();
}

$announcementCount = 0;
$aRes = $conn->query('SELECT COUNT(*) AS total FROM announcements WHERE is_published = 1');
if ($aRes) {
    $row = $aRes->fetch_assoc();
    $announcementCount = (int)($row['total'] ?? 0);
}

$upcomingEvents = [];
$pid = (int)$priest['id'];
$evStmt = $conn->prepare('SELECT CONCAT(CASE WHEN event_kind = "mass" THEN "[Mass] " ELSE "[Event] " END, title) AS title, event_date, event_time, location FROM event_schedules WHERE status = "confirmed" AND event_date >= CURDATE() AND (event_kind <> "mass" OR priest_id = ?) ORDER BY event_date ASC, event_time ASC LIMIT 8');
$evStmt->bind_param('i', $pid);
$evStmt->execute();
$upcomingEvents = $evStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$evStmt->close();

$pendingMasses = [];
$pendingStmt = $conn->prepare('SELECT id, title, event_date, event_time, location FROM event_schedules WHERE event_kind = "mass" AND priest_id = ? AND status = "pending_priest" ORDER BY event_date ASC, event_time ASC');
$pendingStmt->bind_param('i', $pid);
$pendingStmt->execute();
$pendingMasses = $pendingStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$pendingStmt->close();

render_header('Priest Dashboard', 'priest');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Priest Dashboard</h2>
    <div class="text-secondary">Logged in as <?php echo e($priest['full_name'] ?: $priest['email']); ?></div>
</div>
<div class="dash-welcome rounded-4 p-3 p-md-4 mb-4">
    <h2 class="h4 mb-3">Welcome</h2>
    <p class="mb-0">Review pending Mass confirmations, confirm or reject schedules, and monitor upcoming events.</p>
</div>


<?php if ($pendingMasses): ?>
<div class="card bg-dark border-warning-subtle mb-4">
    <div class="card-body">
        <h5 class="text-warning mb-3">Pending Mass Confirmations</h5>
        <div class="table-responsive">
            <table class="table table-dark table-striped align-middle mb-0">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Location</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingMasses as $mass): ?>
                        <tr>
                            <td><?php echo e($mass['title']); ?></td>
                            <td><?php echo e($mass['event_date']); ?></td>
                            <td><?php echo e(date('h:i A', strtotime($mass['event_time']))); ?></td>
                            <td><?php echo e($mass['location'] ?: '-'); ?></td>
                            <td>
                                <form method="POST" class="d-flex gap-2">
                                    <input type="hidden" name="mass_id" value="<?php echo (int)$mass['id']; ?>">
                                    <button class="btn btn-sm btn-success" type="submit" name="action" value="confirm_mass">Confirm</button>
                                    <button class="btn btn-sm btn-outline-danger" type="submit" name="action" value="cancel_mass">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card bg-dark border-warning-subtle h-100">
            <div class="card-body">
                <h6 class="text-warning mb-2">Published Announcements</h6>
                <div class="display-6"><?php echo $announcementCount; ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card bg-dark border-warning-subtle h-100">
            <div class="card-body">
                <h6 class="text-warning mb-3">Upcoming Event Schedules</h6>
                <?php if (!$upcomingEvents): ?>
                    <p class="text-secondary mb-0">No upcoming schedules yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Location</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcomingEvents as $event): ?>
                                    <tr>
                                        <td><?php echo e($event['title']); ?></td>
                                        <td><?php echo e($event['event_date']); ?></td>
                                        <td><?php echo e(date('h:i A', strtotime($event['event_time']))); ?></td>
                                        <td><?php echo e($event['location'] ?: '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php render_footer(); ?>
