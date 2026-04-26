<?php
require_once __DIR__ . '/layout.php';

$user = require_roles(AttendanceService::moduleRoles(), 'Access denied. Attendance is available to Admin, Church Staff, and Ministers only.');
$roleKey = strtolower(trim((string)($user['role'] ?? '')));
$isAdmin = AttendanceService::canScan($user);
$canGenerateQr = AttendanceService::canGenerateQr($user);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isAdmin) {
        set_flash('danger', 'Only admins can manage attendance sessions.');
        header('Location: attendance.php');
        exit();
    }

    $action = trim((string)($_POST['attendance_action'] ?? ''));
    if ($action === 'start_session') {
        $sessionName = trim((string)($_POST['session_name'] ?? ''));
        $result = AttendanceService::createSession($conn, $sessionName, (int)$user['id']);
        set_flash($result['ok'] ? 'success' : 'danger', $result['ok'] ? 'Attendance session started successfully.' : ($result['message'] ?? 'Unable to start attendance session.'));
    } elseif ($action === 'close_session') {
        $sessionId = (int)($_POST['session_id'] ?? 0);
        $closed = $sessionId > 0 ? AttendanceService::closeSession($conn, $sessionId) : false;
        set_flash($closed ? 'success' : 'warning', $closed ? 'Attendance session closed.' : 'Attendance session could not be closed.');
    }

    header('Location: attendance.php');
    exit();
}

$activeSession = $isAdmin ? AttendanceService::activeSession($conn) : null;
$recentSessions = $isAdmin ? AttendanceService::recentSessions($conn, 6) : [];
$entries = ($isAdmin && $activeSession) ? AttendanceService::recentEntries($conn, (int)$activeSession['id']) : [];
$qrToken = $canGenerateQr ? AttendanceService::buildQrToken($conn, $user) : '';
$displayName = AttendanceService::displayName($user);
$roleLabel = AttendanceService::roleLabel($roleKey);
$isPreviewOnly = !$isAdmin && !$canGenerateQr;

render_header('Attendance Module', 'attendance');
?>
<div class="attendance-module">
    <div class="attendance-hero rounded-4 mb-4">
        <div>
            <span class="attendance-kicker">Attendance Module</span>
            <h2 class="attendance-title mb-2"><?php echo $isAdmin ? 'Admin Attendance Scanner' : 'Your Attendance QR'; ?></h2>
            <p class="attendance-copy mb-0">
                <?php if ($isAdmin): ?>
                    Start an attendance session, scan staff and minister QR codes, and watch the live attendance list update instantly.
                <?php elseif ($isPreviewOnly): ?>
                    Attendance preview is active, but QR generation is disabled while using the admin view-as mode.
                <?php else: ?>
                    Present this QR code to the admin scanner during church events so your time-in is recorded securely.
                <?php endif; ?>
            </p>
        </div>
        <div class="attendance-hero-badge">
            <span class="badge text-bg-warning-subtle"><?php echo e($roleLabel); ?></span>
            <strong><?php echo e($displayName); ?></strong>
        </div>
    </div>

    <?php if ($isAdmin): ?>
        <div class="row g-4">
            <div class="col-xl-4">
                <div class="attendance-panel rounded-4 p-4 h-100">
                    <?php if ($activeSession): ?>
                        <div class="attendance-session-state mb-4">
                            <span class="attendance-pill attendance-pill-live">Live Session</span>
                            <h3 class="h4 mt-3 mb-2"><?php echo e($activeSession['session_name']); ?></h3>
                            <div class="attendance-session-meta">
                                <div><strong>Date:</strong> <?php echo e(date('F d, Y', strtotime((string)$activeSession['session_date']))); ?></div>
                                <div><strong>Started:</strong> <?php echo e(date('M d, Y h:i A', strtotime((string)$activeSession['started_at']))); ?></div>
                                <div><strong>Created By:</strong> <?php echo e(trim((string)($activeSession['created_by_name'] ?? '')) !== '' ? (string)$activeSession['created_by_name'] : 'Admin'); ?></div>
                            </div>
                        </div>
                        <form method="POST" class="attendance-form-stack">
                            <input type="hidden" name="attendance_action" value="close_session">
                            <input type="hidden" name="session_id" value="<?php echo (int)$activeSession['id']; ?>">
                            <button class="btn btn-outline-light w-100" type="submit">Close Current Session</button>
                        </form>
                    <?php else: ?>
                        <div class="attendance-session-state mb-4">
                            <span class="attendance-pill">No Active Session</span>
                            <h3 class="h4 mt-3 mb-2">Start a New Attendance Session</h3>
                            <p class="mb-0 text-light">Create one active session for the current event so duplicate time-ins are blocked automatically.</p>
                        </div>
                        <form method="POST" class="attendance-form-stack">
                            <input type="hidden" name="attendance_action" value="start_session">
                            <div>
                                <label class="form-label" for="session_name">Session Name</label>
                                <input
                                    class="form-control"
                                    id="session_name"
                                    name="session_name"
                                    type="text"
                                    maxlength="180"
                                    placeholder="Example: Sunday Worship Service"
                                    required
                                >
                            </div>
                            <button class="btn btn-warning w-100" type="submit">Start Attendance Session</button>
                        </form>
                    <?php endif; ?>

                    <hr class="attendance-divider my-4">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h5 mb-0">Recent Sessions</h3>
                        <span class="text-light-subtle small">Latest 6</span>
                    </div>
                    <div class="attendance-session-list">
                        <?php if (!$recentSessions): ?>
                            <div class="attendance-empty-state">No sessions recorded yet.</div>
                        <?php else: ?>
                            <?php foreach ($recentSessions as $session): ?>
                                <div class="attendance-session-item">
                                    <div>
                                        <strong><?php echo e($session['session_name']); ?></strong>
                                        <div class="attendance-session-sub">
                                            <?php echo e(date('M d, Y h:i A', strtotime((string)$session['started_at']))); ?>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <span class="attendance-pill <?php echo ($session['status'] ?? '') === 'open' ? 'attendance-pill-live' : ''; ?>">
                                            <?php echo e(ucfirst((string)$session['status'])); ?>
                                        </span>
                                        <div class="attendance-session-sub"><?php echo (int)$session['attendee_count']; ?> attendance</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="attendance-panel rounded-4 p-4 mb-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h3 class="h4 mb-1">Scanner</h3>
                            <p class="mb-0 text-light-subtle">Use the device camera to continuously read secure minister and staff QR codes.</p>
                        </div>
                        <?php if ($activeSession): ?>
                            <span class="attendance-pill attendance-pill-live">Session #<?php echo (int)$activeSession['id']; ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($activeSession): ?>
                        <div id="attendance-reader" class="attendance-reader"></div>
                        <div id="attendance-scan-status" class="alert alert-secondary attendance-status mb-0">
                            Preparing camera access for live scanning.
                        </div>
                    <?php else: ?>
                        <div class="attendance-empty-state attendance-empty-state-large">
                            Start an attendance session first to activate the QR scanner.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="attendance-panel rounded-4 p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h3 class="h4 mb-1">Live Attendance List</h3>
                            <p class="mb-0 text-light-subtle">Every successful scan appears here immediately with duplicate time-ins blocked for this session.</p>
                        </div>
                        <span class="attendance-count-pill">
                            <span id="attendance-count"><?php echo count($entries); ?></span> checked in
                        </span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-dark table-borderless align-middle attendance-table mb-0">
                            <thead>
                                <tr>
                                    <th>Full Name</th>
                                    <th>Role</th>
                                    <th>Time In</th>
                                </tr>
                            </thead>
                            <tbody id="attendance-live-body">
                                <?php if (!$entries): ?>
                                    <tr class="attendance-empty-row">
                                        <td colspan="3" class="text-center text-light-subtle">No attendance records for the active session yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($entries as $entry): ?>
                                        <tr>
                                            <td><?php echo e($entry['full_name']); ?></td>
                                            <td><span class="attendance-role-chip"><?php echo e($entry['role_label']); ?></span></td>
                                            <td><?php echo e($entry['time_in_display']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($canGenerateQr): ?>
        <div class="row g-4 align-items-stretch">
            <div class="col-lg-5">
                <div class="attendance-panel rounded-4 p-4 attendance-qr-panel h-100">
                    <div class="attendance-qr-card">
                        <canvas id="attendance-personal-qr" width="280" height="280" aria-label="Attendance QR code"></canvas>
                    </div>
                    <div class="attendance-qr-actions mt-4">
                        <button class="btn btn-warning" id="attendance-download-qr" type="button">Download QR</button>
                        <button class="btn btn-outline-light" id="attendance-print-qr" type="button">Print QR</button>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="attendance-panel rounded-4 p-4 h-100">
                    <h3 class="h4 mb-3">QR Identity Card</h3>
                    <div class="attendance-identity-grid mb-4">
                        <div class="attendance-identity-item">
                            <span class="attendance-identity-label">Full Name</span>
                            <strong><?php echo e($displayName); ?></strong>
                        </div>
                        <div class="attendance-identity-item">
                            <span class="attendance-identity-label">Role</span>
                            <strong><?php echo e($roleLabel); ?></strong>
                        </div>
                        <div class="attendance-identity-item">
                            <span class="attendance-identity-label">User ID</span>
                            <strong>#<?php echo (int)$user['id']; ?></strong>
                        </div>
                    </div>

                    <div class="attendance-note-list">
                        <div class="attendance-note">
                            <strong>Secure verification</strong>
                            <p class="mb-0">Your QR contains a signed token that the admin scanner validates on the server before recording attendance.</p>
                        </div>
                        <div class="attendance-note">
                            <strong>One QR per account</strong>
                            <p class="mb-0">The code is uniquely tied to your account details and becomes invalid if your role or identity data changes.</p>
                        </div>
                        <div class="attendance-note">
                            <strong>Fast event check-in</strong>
                            <p class="mb-0">Open this page on your phone, save the QR, or print it ahead of time for quicker entry during events.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="attendance-panel rounded-4 p-4">
            <h3 class="h4 mb-3">QR Preview Unavailable</h3>
            <p class="mb-0 text-light">
                This account is currently using admin preview mode. For security, attendance QR codes are only generated for real Church Staff and Minister accounts, not for role previews.
            </p>
        </div>
    <?php endif; ?>
</div>

<script>
    window.ATTENDANCE_MODULE_CONFIG = <?php echo json_encode([
        'isAdmin' => $isAdmin,
        'activeSessionId' => $activeSession ? (int)$activeSession['id'] : 0,
        'apiUrl' => 'attendance_api.php',
        'initialEntries' => $entries,
        'qrToken' => $qrToken,
        'displayName' => $displayName,
        'roleLabel' => $roleLabel,
        'userId' => (int)$user['id'],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
</script>
<?php if ($isAdmin && $activeSession): ?>
    <script src="https://unpkg.com/html5-qrcode" defer></script>
<?php endif; ?>
<?php if ($canGenerateQr): ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js" defer></script>
<?php endif; ?>
<script>
    (function () {
        var config = window.ATTENDANCE_MODULE_CONFIG || {};

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function renderRows(entries) {
            var tbody = document.getElementById('attendance-live-body');
            var countEl = document.getElementById('attendance-count');
            if (!tbody || !countEl) {
                return;
            }

            if (!Array.isArray(entries) || !entries.length) {
                tbody.innerHTML = '<tr class="attendance-empty-row"><td colspan="3" class="text-center text-light-subtle">No attendance records for the active session yet.</td></tr>';
                countEl.textContent = '0';
                return;
            }

            tbody.innerHTML = entries.map(function (entry) {
                return '<tr>' +
                    '<td>' + escapeHtml(entry.full_name) + '</td>' +
                    '<td><span class="attendance-role-chip">' + escapeHtml(entry.role_label || entry.role) + '</span></td>' +
                    '<td>' + escapeHtml(entry.time_in_display || entry.time_in) + '</td>' +
                '</tr>';
            }).join('');
            countEl.textContent = String(entries.length);
        }

        function setStatus(message, level) {
            var statusEl = document.getElementById('attendance-scan-status');
            if (!statusEl) {
                return;
            }

            statusEl.className = 'alert attendance-status mb-0';
            statusEl.classList.add(level === 'danger' ? 'alert-danger' : level === 'warning' ? 'alert-warning' : level === 'success' ? 'alert-success' : 'alert-secondary');
            statusEl.textContent = message;
        }

        function initPersonalQr() {
            if (!config.qrToken) {
                return;
            }

            var canvas = document.getElementById('attendance-personal-qr');
            if (!canvas) {
                return;
            }

            var initialize = function () {
                if (!window.QRious) {
                    return;
                }

                var qr = new window.QRious({
                    element: canvas,
                    value: config.qrToken,
                    size: 280,
                    level: 'H',
                    foreground: '#5d2f17',
                    background: '#fffdf8'
                });

                var downloadButton = document.getElementById('attendance-download-qr');
                if (downloadButton) {
                    downloadButton.addEventListener('click', function () {
                        var link = document.createElement('a');
                        link.href = qr.toDataURL('image/png');
                        link.download = 'attendance-qr-user-' + config.userId + '.png';
                        link.click();
                    });
                }

                var printButton = document.getElementById('attendance-print-qr');
                if (printButton) {
                    printButton.addEventListener('click', function () {
                        var printWindow = window.open('', '_blank', 'width=420,height=640');
                        if (!printWindow) {
                            return;
                        }

                        var imageUrl = qr.toDataURL('image/png');
                        printWindow.document.write(
                            '<!doctype html><html><head><title>Attendance QR</title>' +
                            '<style>body{font-family:Arial,sans-serif;padding:32px;text-align:center;color:#2f1a0f}img{width:280px;height:280px}h1{margin-bottom:8px}p{margin:6px 0}small{display:block;margin-top:16px;color:#6b4a2f}</style>' +
                            '</head><body>' +
                            '<h1>' + escapeHtml(config.displayName) + '</h1>' +
                            '<p>' + escapeHtml(config.roleLabel) + '</p>' +
                            '<p>User ID #' + escapeHtml(config.userId) + '</p>' +
                            '<img src="' + imageUrl + '" alt="Attendance QR">' +
                            '<small>Present this QR code to the admin attendance scanner.</small>' +
                            '</body></html>'
                        );
                        printWindow.document.close();
                        printWindow.focus();
                        printWindow.print();
                    });
                }
            };

            if (window.QRious) {
                initialize();
                return;
            }

            window.addEventListener('load', initialize, { once: true });
        }

        function initScanner() {
            if (!config.isAdmin || !config.activeSessionId) {
                return;
            }

            renderRows(config.initialEntries || []);

            var scannerReady = function () {
                if (!window.Html5Qrcode) {
                    setStatus('Scanner library could not load. Please refresh the page.', 'danger');
                    return;
                }

                var scanner = new window.Html5Qrcode('attendance-reader');
                var locked = false;
                var lastText = '';
                var lastSeenAt = 0;

                function handleScan(decodedText) {
                    var now = Date.now();
                    if (locked) {
                        return;
                    }
                    if (decodedText === lastText && (now - lastSeenAt) < 1800) {
                        return;
                    }

                    locked = true;
                    lastText = decodedText;
                    lastSeenAt = now;
                    setStatus('Validating scanned QR code...', 'secondary');

                    fetch(config.apiUrl + '?action=scan', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            session_id: config.activeSessionId,
                            qr_text: decodedText
                        })
                    })
                        .then(function (response) {
                            return response.json().then(function (payload) {
                                return { ok: response.ok, payload: payload };
                            });
                        })
                        .then(function (result) {
                            var payload = result.payload || {};
                            renderRows(payload.entries || []);
                            if (!result.ok || payload.ok === false) {
                                setStatus(payload.message || 'Scan failed.', 'danger');
                                return;
                            }
                            setStatus(payload.message || 'Scan complete.', payload.duplicate ? 'warning' : 'success');
                        })
                        .catch(function () {
                            setStatus('Unable to reach the attendance scanner endpoint.', 'danger');
                        })
                        .finally(function () {
                            window.setTimeout(function () {
                                locked = false;
                            }, 900);
                        });
                }

                function refreshEntries() {
                    fetch(config.apiUrl + '?action=list&session_id=' + encodeURIComponent(config.activeSessionId), {
                        headers: { 'Accept': 'application/json' }
                    })
                        .then(function (response) {
                            return response.ok ? response.json() : null;
                        })
                        .then(function (payload) {
                            if (payload && payload.ok) {
                                renderRows(payload.entries || []);
                            }
                        })
                        .catch(function () {
                            return null;
                        });
                }

                var scanConfig = {
                    fps: 10,
                    qrbox: { width: 260, height: 260 },
                    aspectRatio: 1
                };

                scanner.start(
                    { facingMode: 'environment' },
                    scanConfig,
                    handleScan,
                    function () {
                        return null;
                    }
                ).then(function () {
                    setStatus('Scanner is live. Present a staff or minister QR code to the camera.', 'success');
                }).catch(function () {
                    window.Html5Qrcode.getCameras().then(function (devices) {
                        if (!devices || !devices.length) {
                            setStatus('No camera was detected for attendance scanning.', 'danger');
                            return;
                        }
                        return scanner.start(devices[0].id, scanConfig, handleScan, function () {
                            return null;
                        }).then(function () {
                            setStatus('Scanner is live. Present a staff or minister QR code to the camera.', 'success');
                        }).catch(function () {
                            setStatus('Camera access was blocked. Please allow camera access and refresh.', 'danger');
                        });
                    }).catch(function () {
                        setStatus('Unable to initialize the attendance scanner.', 'danger');
                    });
                });

                window.setInterval(refreshEntries, 8000);
            };

            if (window.Html5Qrcode) {
                scannerReady();
                return;
            }

            window.addEventListener('load', scannerReady, { once: true });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function () {
                initPersonalQr();
                initScanner();
            }, { once: true });
        } else {
            initPersonalQr();
            initScanner();
        }
    })();
</script>
<?php render_footer(); ?>
