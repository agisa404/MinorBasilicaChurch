<?php
require_once __DIR__ . '/layout.php';

$admin = current_admin_actor();
if (!$admin) {
    set_flash('danger', 'Access denied. Admin only.');
    header('Location: index.php');
    exit();
}

$roleOptions = [
    'parishioner' => [
        'label' => 'Parishioner',
        'description' => 'Preview the regular member experience with standard modules and service access.',
    ],
    'staff' => [
        'label' => 'Church Staff',
        'description' => 'Preview staff-facing access such as requests and operational tools.',
    ],
    'minister' => [
        'label' => 'Minister',
        'description' => 'Preview the minister workflow for event and Mass requests.',
    ],
    'priest' => [
        'label' => 'Priest',
        'description' => 'Preview the priest dashboard and priest-facing navigation.',
    ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));

    if ($action === 'stop_preview') {
        stop_view_as_role();
        set_flash('success', 'Returned to the admin view.');
        header('Location: admin_view_as.php');
        exit();
    }

    if ($action === 'start_preview') {
        $role = trim((string)($_POST['preview_role'] ?? ''));
        if (!array_key_exists($role, $roleOptions)) {
            set_flash('danger', 'Invalid preview role.');
            header('Location: admin_view_as.php');
            exit();
        }

        start_view_as_role($role);
        set_flash('success', 'Now viewing the site as ' . $roleOptions[$role]['label'] . '.');
        header('Location: index.php');
        exit();
    }
}

$activePreviewRole = current_view_as_role();

render_header('View As', 'admin_view_as');
?>
<?php require __DIR__ . '/partials/admin_tools_nav.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">View As</h2>
    <div class="text-secondary">Admin account: <?php echo e($admin['full_name'] ?: $admin['email']); ?></div>
</div>

<div class="card bg-dark border-warning-subtle mb-4">
    <div class="card-body">
        <h5 class="text-warning mb-2">Role Preview</h5>
        <p class="text-secondary mb-3">Switch the current session into another account role to preview the navigation and screens shown to that account type. This keeps your admin identity intact and can be turned off at any time.</p>
        <?php if ($activePreviewRole && isset($roleOptions[$activePreviewRole])): ?>
            <div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-2 mb-0">
                <div>
                    Currently previewing as <strong><?php echo e($roleOptions[$activePreviewRole]['label']); ?></strong>.
                </div>
                <form method="POST" class="m-0" data-suppress-alerts="true">
                    <input type="hidden" name="action" value="stop_preview">
                    <button class="btn btn-outline-light btn-sm" type="submit">Return To Admin View</button>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-secondary mb-0">Preview mode is currently off. Choose a role below to see the app from that perspective.</div>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3">
    <?php foreach ($roleOptions as $role => $meta): ?>
        <div class="col-md-6 col-xl-3">
            <div class="card bg-dark border-warning-subtle h-100">
                <div class="card-body d-flex flex-column">
                    <h5 class="text-warning"><?php echo e($meta['label']); ?></h5>
                    <p class="text-secondary flex-grow-1"><?php echo e($meta['description']); ?></p>
                    <form method="POST" class="mt-auto" data-suppress-alerts="true">
                        <input type="hidden" name="action" value="start_preview">
                        <input type="hidden" name="preview_role" value="<?php echo e($role); ?>">
                        <button class="btn btn-warning w-100" type="submit">
                            <?php echo $activePreviewRole === $role ? 'Previewing' : 'View As ' . e($meta['label']); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php render_footer(); ?>
