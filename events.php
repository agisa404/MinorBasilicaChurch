<?php
require_once __DIR__ . '/layout.php';
require_login();

$now = app_now();
$monthParam = trim((string)($_GET['month'] ?? ''));
if (!preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
    $monthParam = $now->format('Y-m');
}

$calendarStart = DateTimeImmutable::createFromFormat('Y-m-d', $monthParam . '-01');
if (!$calendarStart) {
    $calendarStart = new DateTimeImmutable($now->format('Y-m-01'));
}

$calendarPrev = $calendarStart->modify('-1 month')->format('Y-m');
$calendarNext = $calendarStart->modify('+1 month')->format('Y-m');
$monthStartDate = $calendarStart->format('Y-m-01');
$monthEndDate = $calendarStart->format('Y-m-t');
$daysInMonth = (int)$calendarStart->format('t');
$startWeekday = (int)$calendarStart->format('w');

$calendarEvents = [];
$eventsByDay = [];

$eventStmt = $conn->prepare('SELECT title AS item_title, event_date AS item_date, event_time AS item_time, CASE WHEN event_kind = "mass" THEN "Mass" ELSE "Admin Event" END AS item_type, "admin" AS source
    FROM event_schedules
    WHERE event_date BETWEEN ? AND ?');
$eventStmt->bind_param('ss', $monthStartDate, $monthEndDate);
$eventStmt->execute();
$eventRows = $eventStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$eventStmt->close();

$scheduleStmt = $conn->prepare('SELECT s.event_title AS item_title, s.event_date AS item_date, s.event_time AS item_time, r.form_type AS item_type, "reservation" AS source
    FROM schedules s
    JOIN service_requests r ON r.id = s.request_id
    WHERE s.event_date BETWEEN ? AND ?');
$scheduleStmt->bind_param('ss', $monthStartDate, $monthEndDate);
$scheduleStmt->execute();
$scheduleRows = $scheduleStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$scheduleStmt->close();

$calendarEvents = array_merge($eventRows, $scheduleRows);
usort($calendarEvents, static function (array $a, array $b): int {
    return strcmp(($a['item_date'] . ' ' . $a['item_time']), ($b['item_date'] . ' ' . $b['item_time']));
});

foreach ($calendarEvents as $item) {
    $day = (int)date('j', strtotime((string)$item['item_date']));
    $eventsByDay[$day] ??= [];
    $eventsByDay[$day][] = $item;
}

$calendarCells = array_fill(0, $startWeekday, null);
for ($day = 1; $day <= $daysInMonth; $day++) {
    $calendarCells[] = $day;
}
while (count($calendarCells) % 7 !== 0) {
    $calendarCells[] = null;
}

$upcoming = [];
$todayDate = $now->format('Y-m-d');

$upEventStmt = $conn->prepare('SELECT title AS item_title, event_date AS item_date, event_time AS item_time, CASE WHEN event_kind = "mass" THEN "Mass" ELSE "Admin Event" END AS item_type, "admin" AS source
    FROM event_schedules
    WHERE event_date >= ?
    ORDER BY event_date ASC, event_time ASC
    LIMIT 20');
$upEventStmt->bind_param('s', $todayDate);
$upEventStmt->execute();
$upEventRows = $upEventStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$upEventStmt->close();

$upScheduleStmt = $conn->prepare('SELECT s.event_title AS item_title, s.event_date AS item_date, s.event_time AS item_time, r.form_type AS item_type, "reservation" AS source
    FROM schedules s
    JOIN service_requests r ON r.id = s.request_id
    WHERE s.event_date >= ?
    ORDER BY s.event_date ASC, s.event_time ASC
    LIMIT 20');
$upScheduleStmt->bind_param('s', $todayDate);
$upScheduleStmt->execute();
$upScheduleRows = $upScheduleStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$upScheduleStmt->close();

$upcoming = array_merge($upEventRows, $upScheduleRows);
usort($upcoming, static function (array $a, array $b): int {
    return strcmp(($a['item_date'] . ' ' . $a['item_time']), ($b['item_date'] . ' ' . $b['item_time']));
});
$upcoming = array_slice($upcoming, 0, 20);

render_header('Events and Schedules', 'events');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h2 class="mb-0">Schedules Calendar</h2>
    <div class="d-flex gap-2">
        <a class="btn btn-sm btn-outline-light" href="events.php?month=<?php echo e($calendarPrev); ?>">&laquo; Previous</a>
        <a class="btn btn-sm btn-outline-light" href="events.php?month=<?php echo e($now->format('Y-m')); ?>">Current Month</a>
        <a class="btn btn-sm btn-outline-light" href="events.php?month=<?php echo e($calendarNext); ?>">Next &raquo;</a>
    </div>
</div>
<p class="text-secondary mb-3"><?php echo e($calendarStart->format('F Y')); ?> - all upcoming events and approved schedules.</p>

<div class="card bg-dark border-warning-subtle mb-4">
    <div class="card-body p-2 p-md-3">
        <div class="table-responsive">
            <table class="table table-dark table-bordered align-middle mb-0">
                <thead>
                    <tr>
                        <th>Sun</th>
                        <th>Mon</th>
                        <th>Tue</th>
                        <th>Wed</th>
                        <th>Thu</th>
                        <th>Fri</th>
                        <th>Sat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_chunk($calendarCells, 7) as $week): ?>
                        <tr>
                            <?php foreach ($week as $day): ?>
                                <td style="vertical-align: top; min-width: 140px; height: 130px;">
                                    <?php if ($day === null): ?>
                                        &nbsp;
                                    <?php else: ?>
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <strong><?php echo $day; ?></strong>
                                            <?php $count = count($eventsByDay[$day] ?? []); ?>
                                            <?php if ($count > 0): ?>
                                                <span class="badge text-bg-warning"><?php echo $count; ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($eventsByDay[$day])): ?>
                                            <?php foreach (array_slice($eventsByDay[$day], 0, 2) as $entry): ?>
                                                <div class="small mb-1">
                                                    <div class="text-info"><?php echo e(date('h:i A', strtotime($entry['item_time']))); ?></div>
                                                    <div><?php echo e($entry['item_title']); ?></div>
                                                </div>
                                            <?php endforeach; ?>
                                            <?php if (count($eventsByDay[$day]) > 2): ?>
                                                <small class="text-secondary">+<?php echo count($eventsByDay[$day]) - 2; ?> more</small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card bg-dark border-warning-subtle">
    <div class="card-body">
        <h5 class="text-warning mb-3">Upcoming Events</h5>
        <?php if (!$upcoming): ?>
            <div class="alert alert-info mb-0">No upcoming events found.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-dark table-striped align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Event</th>
                            <th>Type</th>
                            <th>Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming as $item): ?>
                            <tr>
                                <td><?php echo e($item['item_date']); ?></td>
                                <td><?php echo e(date('h:i A', strtotime($item['item_time']))); ?></td>
                                <td><?php echo e($item['item_title']); ?></td>
                                <td><?php echo e($item['item_type']); ?></td>
                                <td><span class="badge text-bg-<?php echo $item['source'] === 'admin' ? 'info' : 'secondary'; ?>"><?php echo e($item['source']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php render_footer(); ?>
