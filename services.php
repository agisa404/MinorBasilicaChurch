<?php
require_once __DIR__ . '/layout.php';
$user = current_user();
render_header('Services', 'services');

$serviceCards = [
    [
        'title' => 'Mass Intentions',
        'href' => 'form_mass_intentions.php',
        'icon' => 'bi bi-journal-check',
        'summary' => 'Request a Mass intention and choose your preferred schedule.',
        'meta' => 'Fastest form'
    ],
    [
        'title' => 'Baptism Registration',
        'href' => 'form_baptism_registration.php',
        'icon' => 'bi bi-droplet-half',
        'summary' => 'Register a baptism with child, parent, and sponsor details.',
        'meta' => 'Family details + requirements'
    ],
    [
        'title' => 'Certification Request',
        'href' => 'form_baptismal_request.php',
        'icon' => 'bi bi-file-earmark-text',
        'summary' => 'Request baptismal, confirmation, marriage, or related certificates.',
        'meta' => 'Document request'
    ],
    [
        'title' => 'Confirmation Registration',
        'href' => 'form_confirmation_registration.php',
        'icon' => 'bi bi-bookmark-check',
        'summary' => 'Submit a confirmation registration with sponsor and record details.',
        'meta' => 'Candidate information'
    ],
    [
        'title' => 'Holy Communion',
        'href' => 'form_holy_communion.php',
        'icon' => 'bi bi-stars',
        'summary' => 'Register for Holy Communion with baptism and parish information.',
        'meta' => 'Child sacrament'
    ],
    [
        'title' => 'Wedding Registration',
        'href' => 'form_wedding_registration.php',
        'icon' => 'bi bi-heart',
        'summary' => 'Book a wedding request and list the documents already prepared.',
        'meta' => 'Couple details + schedule'
    ],
    [
        'title' => 'Facilities Reservation',
        'href' => 'form_facilities_reservation.php',
        'icon' => 'bi bi-building',
        'summary' => 'Reserve parish facilities and indicate your event needs.',
        'meta' => 'Event and venue request'
    ],
    [
        'title' => 'Outside Basilica Request',
        'href' => 'form_outside_basilica_request.php',
        'icon' => 'bi bi-signpost-split',
        'summary' => 'Request documents or related permissions for services outside the basilica.',
        'meta' => 'Transfer / outside parish use'
    ],
];
?>

<section class="services-hub">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h1 class="h2 mb-1">Services</h1>
            <p class="text-secondary mb-0">Choose a form below to submit a service request.</p>
        </div>
        <div class="services-actions">
            <a class="btn btn-warning" href="filled_forms.php">View Filled Forms</a>
        </div>
    </div>

    <div class="row g-3">
        <?php foreach ($serviceCards as $card): ?>
            <div class="col-12 col-md-6 col-xl-4">
                <a class="services-card h-100" href="<?php echo e($card['href']); ?>">
                    <div class="services-card-icon" aria-hidden="true">
                        <i class="<?php echo e($card['icon']); ?>"></i>
                    </div>
                    <div class="services-card-body">
                        <p class="services-card-meta"><?php echo e($card['meta']); ?></p>
                        <h2><?php echo e($card['title']); ?></h2>
                        <p><?php echo e($card['summary']); ?></p>
                        <span class="services-card-link">Open form</span>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (!$user): ?>
        <div class="alert alert-warning mt-4">
            You can explore the services, but you must <a href="account_management.php" class="alert-link">log in</a> before submitting a request.
        </div>
    <?php endif; ?>
</section>

<?php render_footer(); ?>
