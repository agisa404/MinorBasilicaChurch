<?php
require_once __DIR__ . '/request_helpers.php';
require_once __DIR__ . '/layout.php';
$user = require_login();
$logoPath = '612401184_4348220988792023_5812589285034246497_n.jpg';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    redirect_if_invalid_future_datetime_rules([
        ['date' => trim($_POST['confirm_date'] ?? ''), 'time' => trim($_POST['confirm_time'] ?? ''), 'allow_blank' => false],
    ], 'form_confirmation_registration.php');

    $data = [
        'name_of_candidate' => trim($_POST['name_of_candidate'] ?? ''),
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
        'name_of_sponsor' => trim($_POST['name_of_sponsor'] ?? ''),
        'sponsor_address' => trim($_POST['sponsor_address'] ?? ''),
        'requirements' => $_POST['requirements'] ?? [],
        'others' => trim($_POST['others'] ?? ''),
        'notes' => trim($_POST['notes'] ?? ''),
        'date_registered' => trim($_POST['date_registered'] ?? ''),
        'book_no' => trim($_POST['book_no'] ?? ''),
        'page_no' => trim($_POST['page_no'] ?? ''),
        'line_no' => trim($_POST['line_no'] ?? ''),
        'amount' => trim($_POST['amount'] ?? ''),
        'received_by' => trim($_POST['received_by'] ?? ''),
        'minister_of_confirmation' => trim($_POST['minister_of_confirmation'] ?? '')
    ];

    $requestId = create_service_request(
        (int)$user['id'],
        'Confirmation Registration',
        'Registration Form for Confirmation',
        $data,
        $_POST['confirm_date'] ?? null,
        $_POST['confirm_time'] ?? null
    );

    notify_user((int)$user['id'], 'Confirmation registration submitted. Reference #' . $requestId . '.');
    set_flash('success', 'Confirmation registration submitted.');
    header('Location: request_success.php?id=' . $requestId);
    exit();
}

render_header('Confirmation Registration Form', 'services_confirmation_registration');
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
                <h1 class="paper-title">Confirmation Registration</h1>
                <p class="paper-subhead">This version groups the important details first so families can finish the request without digging through office-only fields.</p>
            </div>
        </div>

        <div class="service-form-banner">
            <strong>Helpful tip:</strong> prepare the candidate’s baptism details and sponsor information if available.
        </div>

        <section class="service-section">
            <div class="service-section-heading">
                <p class="service-step">Step 1</p>
                <h2>Confirmation Schedule</h2>
                <p>Choose a preferred date and available time for confirmation.</p>
            </div>
            <div class="service-grid service-grid-2">
                <div class="service-field" data-field>
                    <label class="service-label" for="confirm_date">Date of confirmation <span class="service-required">Required</span></label>
                    <input
                        id="confirm_date"
                        class="paper-line-input paper-date"
                        type="date"
                        name="confirm_date"
                        data-datetime-future="true"
                        data-datetime-pair="confirmation-request-schedule"
                        data-datetime-role="date"
                        value="<?php echo e(service_form_value('confirm_date')); ?>"
                        required
                        data-required-message="Please choose a preferred confirmation date.">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="confirm_time">Preferred time <span class="service-required">Required</span></label>
                    <select
                        id="confirm_time"
                        class="paper-line-input"
                        name="confirm_time"
                        data-datetime-future="true"
                        data-datetime-pair="confirmation-request-schedule"
                        data-datetime-role="time"
                        required
                        data-required-message="Please choose a preferred confirmation time.">
                        <?php render_service_time_select('confirm_time', 'Choose a time'); ?>
                    </select>
                    <p class="form-helper">Using a dropdown keeps the time format consistent for the parish office.</p>
                    <p class="form-error" aria-live="polite"></p>
                </div>
            </div>
        </section>

        <section class="service-section">
            <div class="service-section-heading">
                <p class="service-step">Step 2</p>
                <h2>Candidate Information</h2>
                <p>Enter the candidate details as accurately as possible to match church records.</p>
            </div>
            <div class="service-grid">
                <div class="service-field service-field-full" data-field>
                    <label class="service-label" for="name_of_candidate">Candidate's full name <span class="service-required">Required</span></label>
                    <input
                        id="name_of_candidate"
                        class="paper-line-input"
                        type="text"
                        name="name_of_candidate"
                        autocomplete="name"
                        placeholder="e.g. Maria Clara Cruz"
                        value="<?php echo e(service_form_value('name_of_candidate')); ?>"
                        required
                        minlength="3"
                        data-required-message="Please enter the candidate's full name."
                        data-length-message="Please enter the candidate's complete name.">
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
                <p>These details help connect the request to prior sacramental records.</p>
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
                <h2>Sponsor And Requirement Notes</h2>
                <p>You can record the sponsor and any documents already prepared. Missing items can be completed later.</p>
            </div>
            <div class="service-grid service-grid-2">
                <div class="service-field" data-field>
                    <label class="service-label" for="name_of_sponsor">Sponsor's name</label>
                    <input id="name_of_sponsor" class="paper-line-input" type="text" name="name_of_sponsor" autocomplete="name" value="<?php echo e(service_form_value('name_of_sponsor')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field" data-field>
                    <label class="service-label" for="sponsor_address">Sponsor address</label>
                    <input id="sponsor_address" class="paper-line-input" type="text" name="sponsor_address" autocomplete="street-address" value="<?php echo e(service_form_value('sponsor_address')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
            </div>

            <fieldset class="service-choice-group">
                <legend class="service-label">Requirements already available</legend>
                <p class="form-helper">Optional: check any items already prepared to help the office review your request faster.</p>
                <div class="paper-checklist service-check-grid">
                    <label><input type="checkbox" name="requirements[]" value="Baptismal Certificate" <?php echo service_form_checked('requirements', 'Baptismal Certificate'); ?>> Baptismal Certificate</label>
                    <label><input type="checkbox" name="requirements[]" value="Birth Certificate" <?php echo service_form_checked('requirements', 'Birth Certificate'); ?>> Birth Certificate</label>
                    <label><input type="checkbox" name="requirements[]" value="Seminar Certificate" <?php echo service_form_checked('requirements', 'Seminar Certificate'); ?>> Seminar Certificate</label>
                    <label><input type="checkbox" name="requirements[]" value="Sponsor Certificate" <?php echo service_form_checked('requirements', 'Sponsor Certificate'); ?>> Sponsor Certificate</label>
                </div>
            </fieldset>

            <div class="service-grid service-grid-2">
                <div class="service-field" data-field>
                    <label class="service-label" for="others">Other requirements</label>
                    <input id="others" class="paper-line-input" type="text" name="others" placeholder="Anything else requested by the parish" value="<?php echo e(service_form_value('others')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
                <div class="service-field service-field-full" data-field>
                    <label class="service-label" for="notes">Notes</label>
                    <textarea id="notes" class="paper-line-input" name="notes" rows="4" placeholder="Add any scheduling or record notes"><?php echo e(service_form_value('notes')); ?></textarea>
                    <p class="form-error" aria-live="polite"></p>
                </div>
            </div>
        </section>

        <details class="service-optional-panel">
            <summary>Parish office fields and internal notes</summary>
            <p class="paper-subhead">These fields are preserved for office processing but can usually be left blank by parishioners.</p>
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
                    <label class="service-label" for="minister_of_confirmation">Minister of confirmation</label>
                    <input id="minister_of_confirmation" class="paper-line-input" type="text" name="minister_of_confirmation" autocomplete="name" value="<?php echo e(service_form_value('minister_of_confirmation')); ?>">
                    <p class="form-error" aria-live="polite"></p>
                </div>
            </div>
        </details>

        <div class="paper-submit">
            <button class="btn btn-warning" type="submit">Submit confirmation request</button>
        </div>
    </form>
</div>
<?php render_footer(); ?>
