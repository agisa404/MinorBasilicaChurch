<?php
require_once __DIR__ . '/request_helpers.php';
require_once __DIR__ . '/layout.php';
$user = require_login();
$logoPath = '612401184_4348220988792023_5812589285034246497_n.jpg';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    redirect_if_invalid_future_datetime_rules([
        ['date' => trim($_POST['date_of_baptism'] ?? ''), 'time' => trim($_POST['time_of_baptism'] ?? ''), 'allow_blank' => false],
    ], 'form_baptism_registration.php');

    $data = [
        'name_of_child' => trim($_POST['name_of_child'] ?? ''),
        'date_of_birth' => trim($_POST['date_of_birth'] ?? ''),
        'place_of_birth' => trim($_POST['place_of_birth'] ?? ''),
        'gender' => trim($_POST['gender'] ?? ''),
        'current_age' => trim($_POST['current_age'] ?? ''),
        'father_name' => trim($_POST['father_name'] ?? ''),
        'mother_maiden_name' => trim($_POST['mother_maiden_name'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'contact_nos' => trim($_POST['contact_nos'] ?? ''),
        'full_name_of_sponsors' => trim($_POST['full_name_of_sponsors'] ?? ''),
        'sponsors_address' => trim($_POST['sponsors_address'] ?? ''),
        'additional_sponsors' => trim($_POST['additional_sponsors'] ?? ''),
        'additional_sponsors_address' => trim($_POST['additional_sponsors_address'] ?? ''),
        'requirements' => $_POST['requirements'] ?? [],
        'notes' => trim($_POST['notes'] ?? ''),
        'date_registered' => trim($_POST['date_registered'] ?? ''),
        'book_no' => trim($_POST['book_no'] ?? ''),
        'page_no' => trim($_POST['page_no'] ?? ''),
        'line_no' => trim($_POST['line_no'] ?? ''),
        'amount' => trim($_POST['amount'] ?? ''),
        'received_by' => trim($_POST['received_by'] ?? ''),
        'minister_of_baptism' => trim($_POST['minister_of_baptism'] ?? ''),
    ];

    $requestId = create_service_request(
        (int)$user['id'],
        'Baptism Registration',
        'Registration Form for Baptism',
        $data,
        trim($_POST['date_of_baptism'] ?? ''),
        trim($_POST['time_of_baptism'] ?? '')
    );

    notify_user((int)$user['id'], 'Baptism registration submitted. Reference #' . $requestId . '.');
    set_flash('success', 'Baptism registration submitted.');
    header('Location: request_success.php?id=' . $requestId);
    exit();
}

render_header('Baptism Registration Form', 'services_baptism_registration');
?>
<div class="request-paper-wrap">
    <form method="POST" class="request-paper service-form" data-service-form>
        <div class="paper-header service-form-header">
            <div class="paper-brand">
                <img class="paper-brand-logo" src="<?php echo e($logoPath); ?>" alt="Basilica Logo">
                <div>Basilica Menor de<br>San Pedro Bautista</div>
            </div>
            <div class="service-form-intro">
                <p class="service-eyebrow">Service Request</p>
                <h1 class="paper-title">Baptism Registration</h1>
                <p class="paper-subhead">Share the child’s basic details first. Optional parish-office fields are tucked below so the form stays quick and easy to finish.</p>
            </div>
        </div>

        <div class="service-form-banner">
            <strong>What you need:</strong> preferred baptism schedule, child details, parent details, and any available requirements.
        </div>

        <section class="service-section">
            <div class="service-section-heading">
                <p class="service-step">Step 1</p>
                <h2>Preferred Schedule</h2>
                <p>Pick your preferred date and time. The parish can still confirm final availability after review.</p>
            </div>
            <div class="service-grid service-grid-2">
                <div class="service-field" data-field>
                    <label class="service-label" for="date_of_baptism">Date of baptism <span class="service-required">Required</span></label>
                    <input
                        id="date_of_baptism"
                        class="paper-line-input paper-date"
                        type="date"
                        name="date_of_baptism"
                        data-datetime-future="true"
                        data-datetime-pair="baptism-request-schedule"
                        data-datetime-role="date"
                        value="<?php echo e(service_form_value('date_of_baptism')); ?>"
                        required
                        data-required-message="Please choose your preferred baptism date.">
                    <p class="form-helper">Choose the closest available date that works for your family.</p>
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="time_of_baptism">Preferred time <span class="service-required">Required</span></label>
                    <select
                        id="time_of_baptism"
                        class="paper-line-input"
                        name="time_of_baptism"
                        data-datetime-future="true"
                        data-datetime-pair="baptism-request-schedule"
                        data-datetime-role="time"
                        required
                        data-required-message="Please choose a preferred time for the baptism.">
                        <?php render_service_time_select('time_of_baptism', 'Choose a time'); ?>
                    </select>
                    <p class="form-helper">Available times are shown here to avoid typing errors.</p>
                    <p class="form-error" aria-live="polite"></p>
                </div>
            </div>
        </section>

        <section class="service-section">
            <div class="service-section-heading">
                <p class="service-step">Step 2</p>
                <h2>Child Information</h2>
                <p>Enter the child’s information as it appears on the birth certificate when possible.</p>
            </div>
            <div class="service-grid">
                <div class="service-field service-field-full" data-field>
                    <label class="service-label" for="name_of_child">Child's full name <span class="service-required">Required</span></label>
                    <input
                        id="name_of_child"
                        class="paper-line-input"
                        type="text"
                        name="name_of_child"
                        autocomplete="name"
                        placeholder="e.g. Juan Dela Cruz"
                        value="<?php echo e(service_form_value('name_of_child')); ?>"
                        required
                        minlength="3"
                        data-required-message="Please enter the child's full name."
                        data-length-message="Please enter the child's complete name.">
                    <p class="form-helper">Use the exact name to help the parish verify records faster.</p>
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="date_of_birth">Date of birth</label>
                    <input
                        id="date_of_birth"
                        class="paper-line-input paper-date"
                        type="date"
                        name="date_of_birth"
                        value="<?php echo e(service_form_value('date_of_birth')); ?>"
                        data-age-target="current_age">
                    <p class="form-helper">We can estimate the age automatically when this is filled in.</p>
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="place_of_birth">Place of birth</label>
                    <input
                        id="place_of_birth"
                        class="paper-line-input"
                        type="text"
                        name="place_of_birth"
                        autocomplete="off"
                        placeholder="City / Municipality"
                        value="<?php echo e(service_form_value('place_of_birth')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="gender">Gender</label>
                    <select id="gender" class="paper-line-input" name="gender">
                        <option value="">Select gender</option>
                        <option value="Male" <?php echo service_form_selected('gender', 'Male'); ?>>Male</option>
                        <option value="Female" <?php echo service_form_selected('gender', 'Female'); ?>>Female</option>
                    </select>
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="current_age">Current age</label>
                    <input
                        id="current_age"
                        class="paper-line-input"
                        type="number"
                        min="0"
                        max="120"
                        name="current_age"
                        inputmode="numeric"
                        placeholder="Auto-filled if date of birth is set"
                        value="<?php echo e(service_form_value('current_age')); ?>"
                        data-range-message="Please enter a valid age.">
                    <p class="form-error" aria-live="polite"></p>
                </div>
            </div>
        </section>

        <section class="service-section">
            <div class="service-section-heading">
                <p class="service-step">Step 3</p>
                <h2>Parent And Contact Details</h2>
                <p>Only the core details are shown here. Add as much as you already have and leave the rest blank if needed.</p>
            </div>
            <div class="service-grid service-grid-2">
                <div class="service-field" data-field>
                    <label class="service-label" for="father_name">Father's name</label>
                    <input id="father_name" class="paper-line-input" type="text" name="father_name" autocomplete="name" placeholder="Full name" value="<?php echo e(service_form_value('father_name')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="mother_maiden_name">Mother's maiden name</label>
                    <input id="mother_maiden_name" class="paper-line-input" type="text" name="mother_maiden_name" autocomplete="name" placeholder="Full maiden name" value="<?php echo e(service_form_value('mother_maiden_name')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field service-field-full" data-field>
                    <label class="service-label" for="address">Home address</label>
                    <textarea id="address" class="paper-line-input" name="address" rows="3" autocomplete="street-address" placeholder="Street, barangay, city"><?php echo e(service_form_value('address')); ?></textarea>
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="contact_nos">Contact number</label>
                    <input
                        id="contact_nos"
                        class="paper-line-input"
                        type="tel"
                        name="contact_nos"
                        autocomplete="tel"
                        inputmode="tel"
                        placeholder="e.g. 0917 123 4567"
                        value="<?php echo e(service_form_value('contact_nos')); ?>"
                        pattern="^[0-9+()\\-\\s]{7,20}$"
                        data-pattern-message="Use digits and common phone symbols only.">
                    <p class="form-helper">This helps the parish reach you quickly for schedule confirmation.</p>
                    <p class="form-error" aria-live="polite"></p>
                </div>
            </div>
        </section>

        <section class="service-section">
            <div class="service-section-heading">
                <p class="service-step">Step 4</p>
                <h2>Sponsors And Requirements</h2>
                <p>Add the sponsor details you already know. You can update missing items with the parish office later.</p>
            </div>
            <div class="service-grid service-grid-2">
                <div class="service-field" data-field>
                    <label class="service-label" for="full_name_of_sponsors">Primary sponsor(s)</label>
                    <input id="full_name_of_sponsors" class="paper-line-input" type="text" name="full_name_of_sponsors" autocomplete="name" placeholder="Full name of ninong / ninang" value="<?php echo e(service_form_value('full_name_of_sponsors')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="sponsors_address">Sponsor address</label>
                    <input id="sponsors_address" class="paper-line-input" type="text" name="sponsors_address" autocomplete="street-address" placeholder="City or full address" value="<?php echo e(service_form_value('sponsors_address')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="additional_sponsors">Additional sponsor(s)</label>
                    <input id="additional_sponsors" class="paper-line-input" type="text" name="additional_sponsors" placeholder="Optional additional sponsors" value="<?php echo e(service_form_value('additional_sponsors')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="additional_sponsors_address">Additional sponsor address</label>
                    <input id="additional_sponsors_address" class="paper-line-input" type="text" name="additional_sponsors_address" placeholder="Optional address" value="<?php echo e(service_form_value('additional_sponsors_address')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
            </div>

            <fieldset class="service-choice-group">
                <legend class="service-label">Requirements on hand</legend>
                <p class="form-helper">Pick any documents you already have available. Leave unchecked if you still need to complete them.</p>
                <div class="paper-checklist service-check-grid">
                    <label><input type="checkbox" name="requirements[]" value="Certificate of Live Birth" <?php echo service_form_checked('requirements', 'Certificate of Live Birth'); ?>> Certificate of Live Birth</label>
                    <label><input type="checkbox" name="requirements[]" value="Marriage Certificate of Parents" <?php echo service_form_checked('requirements', 'Marriage Certificate of Parents'); ?>> Marriage Certificate of Parents</label>
                    <label><input type="checkbox" name="requirements[]" value="Permit for Baptism" <?php echo service_form_checked('requirements', 'Permit for Baptism'); ?>> Permit for Baptism</label>
                    <label><input type="checkbox" name="requirements[]" value="Certificate of No Record" <?php echo service_form_checked('requirements', 'Certificate of No Record'); ?>> Certificate of No Record</label>
                </div>
            </fieldset>

            <div class="service-field service-field-full" data-field>
                <label class="service-label" for="notes">Notes for the parish office</label>
                <textarea id="notes" class="paper-line-input" name="notes" rows="4" placeholder="Anything the parish team should know about this request"><?php echo e(service_form_value('notes')); ?></textarea>
                <p class="form-error" aria-live="polite"></p>
            </div>
        </section>

        <details class="service-optional-panel">
            <summary>Parish office fields and optional internal notes</summary>
            <p class="paper-subhead">Leave these blank unless the parish office asked you to complete them.</p>
            <div class="service-grid service-grid-2">
                <div class="service-field" data-field>
                    <label class="service-label" for="date_registered">Date registered</label>
                    <input id="date_registered" class="paper-line-input paper-date" type="date" name="date_registered" value="<?php echo e(service_form_value('date_registered')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="amount">Amount</label>
                    <input id="amount" class="paper-line-input" type="number" step="0.01" min="0" name="amount" inputmode="decimal" placeholder="0.00" value="<?php echo e(service_form_value('amount')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="book_no">Book number</label>
                    <input id="book_no" class="paper-line-input" type="text" name="book_no" value="<?php echo e(service_form_value('book_no')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="page_no">Page number</label>
                    <input id="page_no" class="paper-line-input" type="text" name="page_no" value="<?php echo e(service_form_value('page_no')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="line_no">Line number</label>
                    <input id="line_no" class="paper-line-input" type="text" name="line_no" value="<?php echo e(service_form_value('line_no')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="received_by">Received by</label>
                    <input id="received_by" class="paper-line-input" type="text" name="received_by" autocomplete="name" value="<?php echo e(service_form_value('received_by')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field service-field-full" data-field>
                    <label class="service-label" for="minister_of_baptism">Minister of baptism</label>
                    <input id="minister_of_baptism" class="paper-line-input" type="text" name="minister_of_baptism" autocomplete="name" value="<?php echo e(service_form_value('minister_of_baptism')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
            </div>
        </details>

        <div class="paper-submit">
            <button class="btn btn-warning" type="submit">Submit baptism request</button>
        </div>
    </form>
</div>
<?php render_footer(); ?>
