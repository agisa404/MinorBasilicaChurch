<?php
require_once __DIR__ . '/layout.php';
$user = require_login();
$roleLabel = (($user['role'] ?? '') === 'user') ? 'parishioner' : ($user['role'] ?? 'parishioner');

if (isset($_POST['update_app_datetime'])) {
    if (($user['role'] ?? '') !== 'admin') {
        set_flash('danger', 'Only admin can update system date/time.');
        header('Location: settings.php');
        exit();
    }

    $input = trim($_POST['app_datetime'] ?? '');
    if ($input === '') {
        clear_app_setting('app_datetime_override');
        set_flash('success', 'System date/time reset to server current time.');
        header('Location: settings.php');
        exit();
    }

    redirect_if_invalid_future_datetime_rules([
        ['datetime_local' => $input, 'allow_blank' => false],
    ], 'settings.php');

    $parsed = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $input);
    if (!$parsed) {
        set_flash('danger', 'Invalid date/time value.');
        header('Location: settings.php');
        exit();
    }

    set_app_setting('app_datetime_override', $parsed->format('Y-m-d H:i:s'));
    set_flash('success', 'System date/time updated.');
    header('Location: settings.php');
    exit();
}

$appDateTimeInput = app_now()->format('Y-m-d\TH:i');
$appOverrideRaw = get_app_setting('app_datetime_override', null);

render_header('Settings', 'settings');
?>
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card bg-dark border-warning-subtle">
            <div class="card-body">
                <h4 class="mb-3">Session</h4>
                <p class="mb-2"><?php echo e($user['full_name'] ?: $user['email']); ?></p>
                <small class="text-uppercase d-block mb-3"><?php echo e($roleLabel); ?></small>
                <a class="btn btn-outline-light" href="logout.php">Logout</a>
            </div>
        </div>
    </div>

    <?php if (($user['role'] ?? '') === 'admin'): ?>
        <div class="col-12">
            <div class="card bg-dark border-warning-subtle">
                <div class="card-body">
                    <h4 class="mb-3">System Date and Time</h4>
                    <form method="POST" class="row g-3 align-items-end">
                        <input type="hidden" name="update_app_datetime" value="1">
                        <div class="col-md-5">
                            <label class="form-label">Date/Time</label>
                            <input class="form-control" type="datetime-local" name="app_datetime" data-datetime-future="true" data-datetime-role="datetime-local" value="<?php echo e($appDateTimeInput); ?>">
                        </div>
                        <div class="col-md-7">
                            <div class="d-flex gap-2">
                                <button class="btn btn-warning" type="submit">Save Date/Time</button>
                                <button class="btn btn-outline-light" type="submit" name="app_datetime" value="" data-datetime-submit-ignore>Reset to Server Time</button>
                            </div>
                            <small class="text-secondary d-block mt-2">
                                <?php echo $appOverrideRaw ? 'Custom app time is active.' : 'Using current server time.'; ?>
                            </small>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php render_footer(); ?>
