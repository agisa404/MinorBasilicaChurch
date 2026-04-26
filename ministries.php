<?php
require_once __DIR__ . '/layout.php';

$ministryItems = [
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

render_header('Ministries', 'ministries');
?>
<div class="dash-main rounded-4 p-3 p-md-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Ministries</h1>
        <a class="btn btn-outline-light btn-sm" href="services.php">Open Service Forms</a>
    </div>
    <p class="mb-3">Ministries and madated organizations.</p>
    <div class="row g-3">
        <?php foreach ($ministryItems as $item): ?>
            <div class="col-md-6 col-lg-4">
                <div class="quick-card rounded-4 p-3 h-100">
                    <h2 class="h6 mb-0"><?php echo e($item); ?></h2>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php render_footer(); ?>
