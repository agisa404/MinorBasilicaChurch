<?php
require_once __DIR__ . '/request_helpers.php';
require_once __DIR__ . '/layout.php';
$user = require_login();
$logoPath = '612401184_4348220988792023_5812589285034246497_n.jpg';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    redirect_if_invalid_future_datetime_rules([
        ['date' => trim($_POST['date_of_holy_communion'] ?? ''), 'time' => trim($_POST['time_of_holy_communion'] ?? ''), 'allow_blank' => true],
    ], 'form_holy_communion.php');

    $data = [
        'name_of_child' => trim($_POST['name_of_child'] ?? ''),
        'date_of_birth' => trim($_POST['date_of_birth'] ?? ''),
        'place_of_birth' => trim($_POST['place_of_birth'] ?? ''),
        'gender' => trim($_POST['gender'] ?? ''),
        'current_age' => trim($_POST['current_age'] ?? ''),
        'date_of_baptism' => trim($_POST['date_of_baptism'] ?? ''),
        'church_parish_of_baptism' => trim($_POST['church_parish_of_baptism'] ?? ''),
        'father_name' => trim($_POST['father_name'] ?? ''),
        'mother_maiden_name' => trim($_POST['mother_maiden_name'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'parish' => trim($_POST['parish'] ?? ''),
        'contact_nos' => trim($_POST['contact_nos'] ?? ''),
        'requirements' => $_POST['requirements'] ?? [],
        'others' => trim($_POST['others'] ?? ''),
        'notes' => trim($_POST['notes'] ?? ''),
        'book_no' => trim($_POST['book_no'] ?? ''),
        'page_no' => trim($_POST['page_no'] ?? ''),
        'line_no' => trim($_POST['line_no'] ?? ''),
        'signature_of_catechist' => trim($_POST['signature_of_catechist'] ?? ''),
    ];

    $holyDate = trim($_POST['date_of_holy_communion'] ?? '');
    $holyTime = trim($_POST['time_of_holy_communion'] ?? '');

    $requestId = create_service_request(
        (int)$user['id'],
        'Holy Communion Registration',
        'Registration Form for Holy Communion',
        $data,
        $holyDate !== '' ? $holyDate : null,
        $holyTime !== '' ? $holyTime : null
    );

    notify_user((int)$user['id'], 'Holy Communion registration submitted. Reference #' . $requestId . '.');
    set_flash('success', 'Holy Communion registration submitted.');
    header('Location: request_success.php?id=' . $requestId);
    exit();
}

render_header('Holy Communion Registration Form', 'services_holy_communion');
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
                <h1 class="paper-title">Holy Communion Registration</h1>
                <p class="paper-subhead">The essentials are grouped first, and internal record fields are kept separate so families can focus on the actual request.</p>
            </div>
        </div>

        <section class="service-section">
            <div class="service-section-heading">
                <p class="service-step">Step 1</p>
                <h2>Preferred Schedule</h2>
                <p>If a schedule is already known, add it here. Otherwise, you can leave these fields blank and the parish office can help confirm later.</p>
            </div>
            <div class="service-grid service-grid-2">
                <div class="service-field" data-field>
                    <label class="service-label" for="date_of_holy_communion">Date of Holy Communion</label>
                    <input id="date_of_holy_communion" class="paper-line-input paper-date" type="date" name="date_of_holy_communion" data-datetime-future="true" data-datetime-pair="holy-communion-schedule" data-datetime-role="date" value="<?php echo e(service_form_value('date_of_holy_communion')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="time_of_holy_communion">Preferred time</label>
                    <select id="time_of_holy_communion" class="paper-line-input" name="time_of_holy_communion" data-datetime-future="true" data-datetime-pair="holy-communion-schedule" data-datetime-role="time">
                        <?php render_service_time_select('time_of_holy_communion', 'Choose a time'); ?>
                    </select>
                    <p class="form-error" aria-live="polite"></p>
                </div>
            </div>
        </section>

        <section class="service-section">
            <div class="service-section-heading">
                <p class="service-step">Step 2</p>
                <h2>Child Information</h2>
                <p>Fill in the child’s basic information. The age can auto-fill once the birth date is entered.</p>
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
                        placeholder="e.g. Ana Dela Cruz"
                        value="<?php echo e(service_form_value('name_of_child')); ?>"
                        required
                        minlength="3"
                        data-required-message="Please enter the child's full name."
                        data-length-message="Please enter the child's complete name.">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="date_of_birth">Date of birth</label>
                    <input id="date_of_birth" class="paper-line-input paper-date" type="date" name="date_of_birth" value="<?php echo e(service_form_value('date_of_birth')); ?>" data-age-target="current_age">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="place_of_birth">Place of birth</label>
                    <input id="place_of_birth" class="paper-line-input" type="text" name="place_of_birth" placeholder="City / Municipality" value="<?php echo e(service_form_value('place_of_birth')); ?>">
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
                    <input id="current_age" class="paper-line-input" type="number" min="0" max="120" name="current_age" inputmode="numeric" value="<?php echo e(service_form_value('current_age')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
            </div>
        </section>

        <section class="service-section">
            <div class="service-section-heading">
                <p class="service-step">Step 3</p>
                <h2>Baptism And Family Details</h2>
                <p>These fields help the parish confirm sacramental eligibility and contact the family if follow-up is needed.</p>
            </div>
            <div class="service-grid service-grid-2">
                <div class="service-field" data-field>
                    <label class="service-label" for="date_of_baptism">Date of baptism</label>
                    <input id="date_of_baptism" class="paper-line-input paper-date" type="date" name="date_of_baptism" value="<?php echo e(service_form_value('date_of_baptism')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="church_parish_of_baptism">Church or parish of baptism</label>
                    <input id="church_parish_of_baptism" class="paper-line-input" type="text" name="church_parish_of_baptism" placeholder="Parish name" value="<?php echo e(service_form_value('church_parish_of_baptism')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="father_name">Father's name</label>
                    <input id="father_name" class="paper-line-input" type="text" name="father_name" autocomplete="name" value="<?php echo e(service_form_value('father_name')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="mother_maiden_name">Mother's maiden name</label>
                    <input id="mother_maiden_name" class="paper-line-input" type="text" name="mother_maiden_name" autocomplete="name" value="<?php echo e(service_form_value('mother_maiden_name')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field service-field-full" data-field>
                    <label class="service-label" for="address">Home address</label>
                    <textarea id="address" class="paper-line-input" name="address" rows="3" autocomplete="street-address" placeholder="Street, barangay, city"><?php echo e(service_form_value('address')); ?></textarea>
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="parish">Current parish</label>
                    <input id="parish" class="paper-line-input" type="text" name="parish" placeholder="Parish name" value="<?php echo e(service_form_value('parish')); ?>">
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
                    <p class="form-error" aria-live="polite"></p>
                </div>
            </div>
        </section>

        <section class="service-section">
            <div class="service-section-heading">
                <p class="service-step">Step 4</p>
                <h2>Prepared Requirements</h2>
                <p>Optional: mark anything already completed and leave a short note for the office if needed.</p>
            </div>
            <fieldset class="service-choice-group">
                <legend class="service-label">Requirements already available</legend>
                <div class="paper-checklist service-check-grid">
                    <label><input type="checkbox" name="requirements[]" value="Baptismal Certificate" <?php echo service_form_checked('requirements', 'Baptismal Certificate'); ?>> Baptismal Certificate with annotation</label>
                    <label><input type="checkbox" name="requirements[]" value="Parents Seminar" <?php echo service_form_checked('requirements', 'Parents Seminar'); ?>> Parents seminar completed</label>
                    <label><input type="checkbox" name="requirements[]" value="Practices" <?php echo service_form_checked('requirements', 'Practices'); ?>> Practices attended</label>
                    <label><input type="checkbox" name="requirements[]" value="Confession" <?php echo service_form_checked('requirements', 'Confession'); ?>> Confession completed</label>
                </div>
            </fieldset>
            <div class="service-grid service-grid-2">
                <div class="service-field" data-field>
                    <label class="service-label" for="others">Other requirement notes</label>
                    <input id="others" class="paper-line-input" type="text" name="others" placeholder="Anything else required by the parish" value="<?php echo e(service_form_value('others')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field service-field-full" data-field>
                    <label class="service-label" for="notes">Notes</label>
                    <textarea id="notes" class="paper-line-input" name="notes" rows="4" placeholder="Add reminders or clarifications for the parish office"><?php echo e(service_form_value('notes')); ?></textarea>
                    <p class="form-error" aria-live="polite"></p>
                </div>
            </div>
        </section>

        <details class="service-optional-panel">
            <summary>Parish office fields and catechist notes</summary>
            <p class="paper-subhead">Leave these blank unless your catechist or the parish office asked you to complete them.</p>
            <div class="service-grid service-grid-2">
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
                <div class="service-field service-field-full" data-field>
                    <label class="service-label" for="signature_of_catechist">Signature of catechist</label>
                    <input id="signature_of_catechist" class="paper-line-input" type="text" name="signature_of_catechist" autocomplete="name" value="<?php echo e(service_form_value('signature_of_catechist')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
            </div>
        </details>

        <div class="paper-submit">
            <button class="btn btn-warning" type="submit">Submit Holy Communion request</button>
        </div>
    </form>
</div>
<?php render_footer(); ?>
