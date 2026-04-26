<?php
require_once __DIR__ . '/layout.php';
render_header('Help', 'help');
?>
<div class="dash-main rounded-4 p-3 p-md-4">
    <h1 class="h3 mb-3">Help</h1>
    <div class="quick-card rounded-4 p-3 mb-3">
        <h2 class="h5 mb-2">Common Actions</h2>
        <ul class="mb-0">
            <li>Create service requests in Service Forms.</li>
            <li>Track statuses in Services and Documents.</li>
            <li>Check schedules and attendance in their modules.</li>
            <li>Manage your profile in Account.</li>
        </ul>
    </div>
    <div class="quick-card rounded-4 p-3">
        <h2 class="h5 mb-2">Support Contact</h2>
        <p class="mb-1"><strong>Office:</strong> Parish Operations Desk</p>
        <p class="mb-0"><strong>Phone:</strong> 271482136</p>
    </div>
</div>
<?php render_footer(); ?>
