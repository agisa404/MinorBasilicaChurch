<?php
require_once __DIR__ . '/layout.php';
render_header('Mission and Vision', 'mission');
?>
<div class="dash-main rounded-4 p-3 p-md-4">
    <h1 class="h3 mb-3">Mission and Vision</h1>
    <div class="row g-3">
        <div class="col-md-6">
            <div class="quick-card rounded-4 p-3 h-100">
                <h2 class="h5 mb-2">Mission</h2>
                <p class="mb-0">
                    To support parish ministry through efficient digital tools that improve service delivery,
                    communication, and community participation.
                </p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="quick-card rounded-4 p-3 h-100">
                <h2 class="h5 mb-2">Vision</h2>
                <p class="mb-0">
                    A connected and responsive parish where clergy, staff, ministries, and faithful can collaborate
                    through a reliable and user-friendly information system.
                </p>
            </div>
        </div>
    </div>
</div>
<?php render_footer(); ?>
