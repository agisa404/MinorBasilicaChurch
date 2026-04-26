
<?php
require_once __DIR__ . '/request_helpers.php';
require_once __DIR__ . '/layout.php';
$user = require_roles(['minister'], 'Access denied. Minister only.');
$requestKind = trim((string) ($_GET['kind'] ?? $_POST['request_kind'] ?? 'event'));
if ($requestKind !== 'mass') {
    $requestKind = 'event';
}
$isMassRequest = $requestKind === 'mass';
$requestLocation = 'minister_event_request.php?kind=' . $requestKind;
$requestHeading = $isMassRequest ? 'Mass Request' : 'Event Creation Request';
$requestDescription = $isMassRequest ? 'Submit Mass schedule details to request scheduling approval from the admin team.' : 'Submit event details to request scheduling approval from the admin team.';
$requestNotice = $isMassRequest ? 'Mass request' : 'Event creation request';
$activeNavKey = $isMassRequest ? 'event_request_mass' : 'event_request_event';

$eventOptions = [
    'Mass' => 'Mass',
    'Baptism' => 'Baptism',
    'Wedding' => 'Wedding',
    'Funeral' => 'Funeral / Burial Mass',
    'Meeting' => 'Ministry Meeting',
    'Retreat' => 'Retreat or Recollection',
    'Formation' => 'Faith Formation / Seminar',
    'Devotion' => 'Procession or Devotional',
    'Community' => 'Community / Outreach Event',
    'other' => 'Other (specify below)',
];
if (!$isMassRequest) {
    unset($eventOptions['Mass']);
}

$ministryOptions = [
    'Shrine Ministry',
    'Worship Ministry',
    'Extraordinary Ministers of Holy Communion',
    'Music Ministry',
    'Ministry of Lectors and Commentators',
    'Ministry of Altar Servers',
    'Greeters and Collectors',
    'Mother Butler Guild',
    'Bereavement Ministry',
    'Education and Formation Ministry',
    'Cathethical Ministry',
    'Vocation Ministry',
    'Synod Animator',
    'Mission Ministry',
    'Pastoral Care for LGBTQIA',
    'Social Services and Development Ministry',
    'Livelihood and Job Placement',
    'Public Affairs Ministry',
    'Elderly Ministry',
    'Ecology Ministry',
    'DRRM',
    'Health Ministry',
    'JPIC and Ubran Poor Ministry',
    'Restorative Justice Ministry',
    'Youth Ministry',
    'Social Communications Ministry',
    'Family and Life Ministry',
    'Migrants Ministry',
    'Temporalities Ministry',
    'Catholic Women League',
    'El Shaddai',
    'Holy Name Society',
    'Women for Christ',
    'Divine Mercy Apostolate',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedEventName = $isMassRequest ? 'Mass' : trim($_POST['event_title_choice'] ?? '');
    $customEventName = $isMassRequest ? '' : trim($_POST['event_title_other'] ?? '');
    if ($selectedEventName === 'other') {
        $title = $customEventName;
    } elseif ($selectedEventName !== '') {
        $title = $selectedEventName;
    } else {
        $title = '';
    }

    $description = trim($_POST['description'] ?? '');
    $eventMinistry = trim($_POST['event_ministry'] ?? '');
    $eventDate = trim($_POST['event_date'] ?? '');
    $eventTime = trim($_POST['event_time'] ?? '');
    $locationType = trim($_POST['event_location_type'] ?? 'inside');
    $insideLocation = trim($_POST['event_location_inside'] ?? '');
    $outsideLocation = trim($_POST['event_location_outside'] ?? '');
    $location = ($locationType === 'outside') ? $outsideLocation : $insideLocation;

    if ($title === '' || $eventDate === '' || $eventTime === '') {
        set_flash('danger', $isMassRequest ? 'Mass date and time are required.' : 'Event name, date, and time are required.');
        header('Location: ' . $requestLocation);
        exit();
    }

    redirect_if_invalid_future_datetime_rules([
        ['date' => $eventDate, 'time' => $eventTime, 'allow_blank' => false],
    ], $requestLocation);

    $data = [
        'request_kind' => $requestKind,
        'event_name' => $title,
        'event_name_choice' => $selectedEventName,
        'event_name_other' => $customEventName,
        'event_ministry' => $eventMinistry,
        'event_date' => $eventDate,
        'event_time' => $eventTime,
        'location_type' => $locationType,
        'location_inside' => $insideLocation,
        'location_outside' => $outsideLocation,
        'location' => $location,
        'description' => $description,
    ];

    $requestId = create_service_request(
        (int)$user['id'],
        'Event Creation Request',
        $title !== '' ? $title : ($isMassRequest ? 'Mass' : 'Event Creation Request'),
        $data,
        $eventDate,
        $eventTime
    );

    $adminStmt = $conn->prepare('SELECT id FROM users WHERE role = "admin"');
    $adminStmt->execute();
    $adminRows = $adminStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $adminStmt->close();
    foreach ($adminRows as $row) {
        notify_user((int)$row['id'], 'New ' . strtolower($requestNotice) . ' #' . $requestId . ' submitted by ' . ($user['full_name'] ?: $user['email']) . '.');
    }

    notify_user((int)$user['id'], $requestNotice . ' submitted. Reference #' . $requestId . '.');
    set_flash('success', $requestNotice . ' submitted.');
    header('Location: request_success.php?id=' . $requestId);
    exit();
}

render_header($requestHeading, $activeNavKey);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0"><?php echo e($requestHeading); ?></h2>
    <a class="btn btn-outline-light" href="services.php">Back to Services</a>
</div>
<p class="text-secondary mb-4"><?php echo e($requestDescription); ?></p>

<div class="card bg-dark border-warning-subtle">
    <div class="card-body">
        <form method="POST" class="row g-3">
            <input type="hidden" name="request_kind" value="<?php echo e($requestKind); ?>">
            <div class="col-md-6">
                <label class="form-label"><?php echo $isMassRequest ? 'Request Type' : 'Event Name'; ?></label>
                <?php if ($isMassRequest): ?>
                    <input class="form-control" type="hidden" name="event_title_choice" id="event_title_choice" value="Mass">
                    <input class="form-control" type="text" value="Mass" readonly>
                    <div class="form-text text-secondary">This request will be reviewed as a Mass schedule.</div>
                <?php else: ?>
                    <select class="form-select" name="event_title_choice" id="event_title_choice" required>
                        <option value="">Select event type</option>
                        <?php foreach ($eventOptions as $value => $label): ?>
                            <option value="<?php echo e($value); ?>"><?php echo e($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text text-secondary">Choose from the list or pick "Other" to describe a custom event.</div>
                <?php endif; ?>
            </div>
            <div class="col-md-6" id="event_title_other_group" style="display:none;">
                <label class="form-label">Other Event Name</label>
                <input class="form-control" type="text" name="event_title_other" id="event_title_other" placeholder="Describe the event">
            </div>
            <div class="col-md-6">
                <label class="form-label">Hosting Ministry</label>
                <select class="form-select" name="event_ministry">
                    <option value="">Select ministry</option>
                    <?php foreach ($ministryOptions as $ministry): ?>
                        <option value="<?php echo e($ministry); ?>"><?php echo e($ministry); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Preferred Date</label>
                <input class="form-control" type="date" name="event_date" data-datetime-future="true" data-datetime-pair="minister-event-request" data-datetime-role="date" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Preferred Time</label>
                <input class="form-control" type="time" name="event_time" data-datetime-future="true" data-datetime-pair="minister-event-request" data-datetime-role="time" required>
            </div>
            <div class="col-12">
                <label class="form-label">Location Type</label>
                <div class="d-flex flex-wrap gap-3">
                    <label class="form-check">
                        <input class="form-check-input" type="radio" name="event_location_type" value="inside" checked>
                        <span class="form-check-label">Inside church facility</span>
                    </label>
                    <label class="form-check">
                        <input class="form-check-input" type="radio" name="event_location_type" value="outside">
                        <span class="form-check-label">Outside church</span>
                    </label>
                </div>
            </div>
            <div class="col-md-6" id="location_inside_group">
                <label class="form-label">Facility</label>
                <select class="form-select" name="event_location_inside" id="event_location_inside" required>
                    <option value="">Select facility</option>
                    <option value="Minor Basilica Main Hall">Minor Basilica Main Hall</option>
                    <option value="St. Francis of Assisi Hall (2nd Floor)">St. Francis of Assisi Hall (2nd Floor)</option>
                    <option value="St. Peter of Alcantara (Peach Room)">St. Peter of Alcantara (Peach Room)</option>
                    <option value="St. Margaret of Cortona (Green Room)">St. Margaret of Cortona (Green Room)</option>
                    <option value="St. Louis IX (Blue Room)">St. Louis IX (Blue Room)</option>
                    <option value="Holy Cave">Holy Cave</option>
                    <option value="Portiuncula Formation and Renewal Hall">Portiuncula Formation and Renewal Hall</option>
                    <option value="Brother Sun Sister Moon Garden">Brother Sun Sister Moon Garden</option>
                    <option value="San Damiano Garden">San Damiano Garden</option>
                    <option value="Chamber Room">Chamber Room</option>
                </select>
            </div>
            <div class="col-md-6" id="location_outside_group" style="display:none;">
                <label class="form-label">Outside Location</label>
                <input class="form-control" type="text" name="event_location_outside" id="event_location_outside" placeholder="Enter outside location">
            </div>
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="3"></textarea>
            </div>
            <div class="col-12">
                <button class="btn btn-warning" type="submit">Submit Request</button>
            </div>
        </form>
    </div>
</div>
<script>
    (function () {
        var eventChoice = document.getElementById('event_title_choice');
        var eventOtherGroup = document.getElementById('event_title_other_group');
        var eventOther = document.getElementById('event_title_other');
        var insideGroup = document.getElementById('location_inside_group');
        var outsideGroup = document.getElementById('location_outside_group');
        var insideField = document.getElementById('event_location_inside');
        var outsideField = document.getElementById('event_location_outside');

        if (!eventChoice || !eventOtherGroup || !eventOther || !insideGroup || !outsideGroup || !insideField || !outsideField) {
            return;
        }

        function toggleOtherEventName() {
            var showCustom = eventChoice.value === 'other';
            eventOtherGroup.style.display = showCustom ? 'block' : 'none';
            eventOther.required = showCustom;
            if (!showCustom) {
                eventOther.value = '';
            }
        }

        function toggleLocationType() {
            var type = document.querySelector('input[name="event_location_type"]:checked');
            var isOutside = type && type.value === 'outside';
            insideGroup.style.display = isOutside ? 'none' : 'block';
            outsideGroup.style.display = isOutside ? 'block' : 'none';
            insideField.required = !isOutside;
            outsideField.required = isOutside;
            if (!isOutside) {
                outsideField.value = '';
            }
        }

        eventChoice.addEventListener('change', toggleOtherEventName);
        document.querySelectorAll('input[name="event_location_type"]').forEach(function (radio) {
            radio.addEventListener('change', toggleLocationType);
        });
        toggleOtherEventName();
        toggleLocationType();
    })();
</script>
<?php render_footer(); ?>


